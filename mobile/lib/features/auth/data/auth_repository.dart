import 'dart:io' show Platform;

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../domain/user.dart';
import 'token_storage.dart';

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(
    dio: ref.read(dioProvider),
    tokenStorage: ref.read(tokenStorageProvider),
  );
});

/// Thin wrapper around the `/auth/*` endpoints documented in
/// core/docs/api/openapi-v1.yaml. Persists the Sanctum token on success.
class AuthRepository {
  AuthRepository({required Dio dio, required TokenStorage tokenStorage})
      : _dio = dio,
        _tokenStorage = tokenStorage;

  final Dio _dio;
  final TokenStorage _tokenStorage;

  Future<User> login({
    required String identifier,
    required String password,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/auth/login',
        data: {
          'identifier': identifier,
          'password': password,
          'device_name': _deviceLabel(),
        },
      );
      return _consumeAuthResponse(response.data!);
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<User> register({
    required String email,
    required String password,
    required String firstname,
    required String lastname,
    String? username,
    String? mobile,
    String? country,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/auth/register',
        data: {
          'email': email,
          'password': password,
          'firstname': firstname,
          'lastname': lastname,
          if (username != null) 'username': username,
          if (mobile != null) 'mobile': mobile,
          if (country != null) 'country': country,
        },
      );
      return _consumeAuthResponse(response.data!);
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> forgotPassword({required String email}) async {
    try {
      await _dio.post<void>('/auth/forgot-password', data: {'email': email});
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  /// Returns the User if a stored token is still valid, else null.
  Future<User?> restoreSession() async {
    final token = await _tokenStorage.read();
    if (token == null || token.isEmpty) return null;

    try {
      final response = await _dio.get<Map<String, dynamic>>('/me');
      final data = response.data?['data'] as Map<String, dynamic>?;
      if (data == null) return null;
      return User.fromJson(data);
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) return null;
      // Network blip — leave the token in place and treat as unknown by
      // re-throwing. The caller decides whether to surface it.
      throw ApiException.fromDio(e);
    }
  }

  Future<void> logout() async {
    try {
      await _dio.post<void>('/auth/logout');
    } on DioException {
      // Best-effort — even if the server call fails we still clear locally.
    } finally {
      await _tokenStorage.clear();
    }
  }

  Future<User> _consumeAuthResponse(Map<String, dynamic> body) async {
    final token = body['token'] as String?;
    final userJson = body['user'] as Map<String, dynamic>?;
    if (token == null || userJson == null) {
      throw const ApiException(
        message: 'Malformed login response from server.',
      );
    }
    await _tokenStorage.write(token);
    return User.fromJson(userJson);
  }

  String _deviceLabel() {
    // Used by Sanctum to label the personal access token row, so a user can
    // see "iPhone" vs "Pixel 8" in their session list later.
    if (Platform.isIOS) return 'iOS';
    if (Platform.isAndroid) return 'Android';
    return 'Mobile';
  }
}
