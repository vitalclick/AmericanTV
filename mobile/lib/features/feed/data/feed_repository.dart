import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../../../core/services/cache_service.dart';
import '../domain/video_summary.dart';

final feedRepositoryProvider = Provider<FeedRepository>((ref) {
  return FeedRepository(ref.read(dioProvider), ref.read(cacheServiceProvider));
});

class FeedRepository {
  FeedRepository(this._dio, this._cache);
  final Dio _dio;
  final CacheService _cache;

  static const _firstPageCacheKey = 'cache:feed:page-1';

  /// Read the cached first-page feed if any, without making a network call.
  /// Used by the feed controller for instant cold-start render.
  Future<PaginatedVideos?> cachedFirstPage() async {
    final raw = await _cache.readJson(_firstPageCacheKey);
    if (raw == null) return null;
    return _hydrate(raw);
  }

  Future<PaginatedVideos> feed({int page = 1}) async {
    final result = await _fetchVideoList('/feed', {'page': page});
    if (page == 1) {
      // Persist a slim copy of page 1 — every page would bloat
      // SharedPreferences and most users don't scroll past the first screen
      // anyway. lastPage is kept so cachedFirstPage knows whether more exists.
      await _cache.writeJson(_firstPageCacheKey, {
        'data': result.videos.map(_serialize).toList(),
        'meta': {'current_page': result.page, 'last_page': result.lastPage},
      });
    }
    return result;
  }

  Future<PaginatedVideos> searchVideos({
    String? query,
    String? category,
    String sort = 'recent',
    int page = 1,
  }) =>
      _fetchVideoList('/videos', {
        if (query != null && query.isNotEmpty) 'q': query,
        if (category != null) 'category': category,
        'sort': sort,
        'page': page,
      });

  Future<PaginatedVideos> _fetchVideoList(
    String path,
    Map<String, dynamic> query,
  ) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        path,
        queryParameters: query,
      );
      return _hydrate(response.data!);
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  PaginatedVideos _hydrate(Map<String, dynamic> body) {
    final list = (body['data'] as List)
        .map((e) => VideoSummary.fromJson(e as Map<String, dynamic>))
        .toList();
    final meta = (body['meta'] as Map?)?.cast<String, dynamic>() ?? const {};
    return PaginatedVideos(
      videos: list,
      page: meta['current_page'] as int? ?? 1,
      lastPage: meta['last_page'] as int? ?? 1,
    );
  }

  Map<String, dynamic> _serialize(VideoSummary v) => {
        'id': v.id,
        'slug': v.slug,
        'title': v.title,
        'thumbnail': v.thumbnail,
        'duration_seconds': v.durationSeconds,
        'views': v.views,
        'is_paid': v.isPaid,
        'price': v.price,
        'created_at': v.createdAt?.toIso8601String(),
        'channel': v.channel == null
            ? null
            : {
                'id': v.channel!.id,
                'slug': v.channel!.slug,
                'name': v.channel!.name,
                'avatar': v.channel!.avatar,
              },
      };
}

class PaginatedVideos {
  const PaginatedVideos({
    required this.videos,
    required this.page,
    required this.lastPage,
  });

  final List<VideoSummary> videos;
  final int page;
  final int lastPage;

  bool get hasMore => page < lastPage;
}
