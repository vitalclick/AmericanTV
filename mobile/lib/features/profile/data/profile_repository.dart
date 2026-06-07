import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../../auth/domain/user.dart';

final profileRepositoryProvider = Provider<ProfileRepository>((ref) {
  return ProfileRepository(ref.read(dioProvider));
});

class ProfileRepository {
  ProfileRepository(this._dio);
  final Dio _dio;

  Future<User> updateProfile({
    String? firstname,
    String? lastname,
    String? username,
    String? bio,
  }) async {
    try {
      final response = await _dio.patch<Map<String, dynamic>>(
        '/me/profile',
        data: {
          if (firstname != null) 'firstname': firstname,
          if (lastname != null) 'lastname': lastname,
          if (username != null) 'username': username,
          if (bio != null) 'bio': bio,
        },
      );
      return User.fromJson(response.data!['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> changePassword({required String current, required String newPassword}) async {
    try {
      await _dio.patch<void>(
        '/me/security/password',
        data: {'current': current, 'new': newPassword},
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}
