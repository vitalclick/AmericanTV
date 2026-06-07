import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';

final publishRepositoryProvider = Provider<PublishRepository>((ref) {
  return PublishRepository(ref.read(dioProvider));
});

class PublishRepository {
  PublishRepository(this._dio);
  final Dio _dio;

  Future<List<PublishCategory>> categories() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>('/categories');
      return (response.data!['data'] as List)
          .map((e) => PublishCategory.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> publish({
    required int videoId,
    required int categoryId,
    required bool isPublic,
    List<String> tags = const [],
  }) async {
    try {
      await _dio.post<void>(
        '/me/videos/$videoId/publish',
        data: {
          'category_id': categoryId,
          'visibility': isPublic ? 0 : 1, // 0 = public, 1 = private (web convention).
          'tags': tags,
        },
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  /// Replaces the auto-generated thumbnail. The new URL is what /videos/{id}
  /// will return on next fetch.
  Future<String> uploadThumbnail({required int videoId, required String localPath}) async {
    try {
      final form = FormData.fromMap({
        'thumbnail': await MultipartFile.fromFile(localPath),
      });
      final response = await _dio.post<Map<String, dynamic>>(
        '/me/videos/$videoId/thumbnail',
        data: form,
      );
      return (response.data!['data'] as Map<String, dynamic>)['thumbnail'] as String;
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}

class PublishCategory {
  const PublishCategory({required this.id, required this.name, this.slug});

  factory PublishCategory.fromJson(Map<String, dynamic> json) {
    return PublishCategory(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String?,
    );
  }

  final int id;
  final String name;
  final String? slug;
}
