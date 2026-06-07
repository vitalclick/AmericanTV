import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';

final twoFactorRepositoryProvider = Provider<TwoFactorRepository>((ref) {
  return TwoFactorRepository(ref.read(dioProvider));
});

class TwoFactorRepository {
  TwoFactorRepository(this._dio);
  final Dio _dio;

  Future<TwoFactorEnrollment> init() async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/me/security/2fa/init',
      );
      return TwoFactorEnrollment.fromJson(
        response.data!['data'] as Map<String, dynamic>,
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> enable({required String secret, required String code}) async {
    try {
      await _dio.post<void>(
        '/me/security/2fa/enable',
        data: {'secret': secret, 'code': code},
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> disable({required String code}) async {
    try {
      await _dio.post<void>(
        '/me/security/2fa/disable',
        data: {'code': code},
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}

class TwoFactorEnrollment {
  const TwoFactorEnrollment({
    required this.secret,
    required this.otpauth,
    required this.issuer,
    required this.label,
  });

  factory TwoFactorEnrollment.fromJson(Map<String, dynamic> json) {
    return TwoFactorEnrollment(
      secret: json['secret'] as String,
      otpauth: json['otpauth'] as String,
      issuer: json['issuer'] as String? ?? 'AmericanTV',
      label: json['label'] as String? ?? '',
    );
  }

  final String secret;
  final String otpauth;
  final String issuer;
  final String label;
}
