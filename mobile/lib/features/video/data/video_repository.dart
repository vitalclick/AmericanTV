import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../domain/video_detail.dart';
import '../domain/video_source.dart';

final videoRepositoryProvider = Provider<VideoRepository>((ref) {
  return VideoRepository(ref.read(dioProvider));
});

class VideoRepository {
  VideoRepository(this._dio);
  final Dio _dio;

  Future<VideoDetail> show(String slug) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>('/videos/$slug');
      return VideoDetail.fromJson(
        (response.data!['data'] as Map<String, dynamic>),
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<VideoSource> source(int id) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>('/videos/$id/source');
      return VideoSource.fromJson(response.data!);
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> recordView(int id) async {
    try {
      await _dio.post<void>('/videos/$id/view');
    } on DioException {
      // Best-effort; never block playback on a missed analytics POST.
    }
  }
}
