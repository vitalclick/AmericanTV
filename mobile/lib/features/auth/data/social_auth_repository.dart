import 'dart:io' show Platform;

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:sign_in_with_apple/sign_in_with_apple.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../../../core/env.dart';
import '../domain/user.dart';
import 'token_storage.dart';

final socialAuthRepositoryProvider = Provider<SocialAuthRepository>((ref) {
  return SocialAuthRepository(
    dio: ref.read(dioProvider),
    tokenStorage: ref.read(tokenStorageProvider),
  );
});

/// Drives the native social sign-in SDKs and exchanges the resulting ID token
/// for a Sanctum token via /auth/social/{provider}.
class SocialAuthRepository {
  SocialAuthRepository({required Dio dio, required TokenStorage tokenStorage})
      : _dio = dio,
        _tokenStorage = tokenStorage;

  final Dio _dio;
  final TokenStorage _tokenStorage;

  Future<User> signInWithApple() async {
    if (!Platform.isIOS) {
      // Sign in with Apple is also available on Android via the JS flow, but
      // the native experience is iOS-only. For v1 we only expose the button
      // on iOS.
      throw const ApiException(message: 'Sign in with Apple is only available on iOS.');
    }

    final credential = await SignInWithApple.getAppleIDCredential(
      scopes: const [AppleIDAuthorizationScopes.email, AppleIDAuthorizationScopes.fullName],
    );
    final token = credential.identityToken;
    if (token == null) {
      throw const ApiException(message: 'Apple did not return an identity token.');
    }

    return _exchange('apple', token, nonce: credential.state);
  }

  Future<User> signInWithGoogle() async {
    final google = GoogleSignIn(
      clientId: Platform.isAndroid ? Env.googleOauthClientIdAndroid : null,
      scopes: const ['email', 'profile'],
    );
    final account = await google.signIn();
    if (account == null) {
      throw const ApiException(message: 'Sign-in cancelled.');
    }
    final auth = await account.authentication;
    final token = auth.idToken;
    if (token == null) {
      throw const ApiException(message: 'Google did not return an ID token.');
    }

    return _exchange('google', token);
  }

  Future<User> _exchange(String provider, String idToken, {String? nonce}) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/auth/social/$provider',
        data: {
          'id_token': idToken,
          if (nonce != null) 'nonce': nonce,
          'device_name': Platform.isIOS ? 'iOS' : 'Android',
        },
      );
      final body = response.data!;
      final token = body['token'] as String?;
      final userJson = body['user'] as Map<String, dynamic>?;
      if (token == null || userJson == null) {
        throw const ApiException(message: 'Malformed social login response.');
      }
      await _tokenStorage.write(token);
      return User.fromJson(userJson);
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}
