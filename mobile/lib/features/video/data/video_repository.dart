import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../../../core/services/cache_service.dart';
import '../domain/video_detail.dart';
import '../domain/video_source.dart';

final videoRepositoryProvider = Provider<VideoRepository>((ref) {
  return VideoRepository(ref.read(dioProvider), ref.read(cacheServiceProvider));
});

class VideoRepository {
  VideoRepository(this._dio, this._cache);
  final Dio _dio;
  final CacheService _cache;

  String _detailKey(String slug) => 'cache:video:$slug';

  /// Read whatever the last successful fetch of this slug looked like,
  /// without making a network call. Returns null if we've never seen it.
  Future<VideoDetail?> cachedShow(String slug) async {
    final raw = await _cache.readJson(_detailKey(slug));
    if (raw == null) return null;
    return VideoDetail.fromJson(raw);
  }

  Future<VideoDetail> show(String slug) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>('/videos/$slug');
      final data = response.data!['data'] as Map<String, dynamic>;
      // Cache the raw payload so VideoDetail.fromJson stays the single
      // source of truth for hydration.
      await _cache.writeJson(_detailKey(slug), data);
      return VideoDetail.fromJson(data);
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
