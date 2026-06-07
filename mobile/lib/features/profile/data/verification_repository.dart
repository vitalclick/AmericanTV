import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';

final verificationRepositoryProvider = Provider<VerificationRepository>((ref) {
  return VerificationRepository(ref.read(dioProvider));
});

class VerificationRepository {
  VerificationRepository(this._dio);
  final Dio _dio;

  Future<void> sendEmailCode() => _send('/auth/email/send');
  Future<void> sendMobileCode() => _send('/auth/mobile/send');

  Future<void> verifyEmail(String code) => _verify('/auth/email/verify', code);
  Future<void> verifyMobile(String code) => _verify('/auth/mobile/verify', code);

  Future<void> _send(String path) async {
    try {
      await _dio.post<void>(path);
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> _verify(String path, String code) async {
    try {
      await _dio.post<void>(path, data: {'code': code});
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}
