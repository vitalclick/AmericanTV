import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../../../core/services/cache_service.dart';
import '../../feed/data/feed_repository.dart';
import '../../feed/domain/video_summary.dart';

final libraryRepositoryProvider = Provider<LibraryRepository>((ref) {
  return LibraryRepository(ref.read(dioProvider), ref.read(cacheServiceProvider));
});

class LibraryRepository {
  LibraryRepository(this._dio, this._cache);
  final Dio _dio;
  final CacheService _cache;

  Future<PaginatedVideos?> cachedWatchLater() => _cachedList('cache:watch-later:page-1');
  Future<PaginatedVideos?> cachedHistory()    => _cachedList('cache:history:page-1');

  Future<PurchasedLibrary?> cachedPurchased() async {
    final raw = await _cache.readJson('cache:purchased:page-1');
    if (raw == null) return null;
    return _hydratePurchased(raw);
  }

  Future<PaginatedVideos?> _cachedList(String key) async {
    final raw = await _cache.readJson(key);
    if (raw == null) return null;
    final videos = (raw['data'] as List)
        .map((e) => VideoSummary.fromJson(e as Map<String, dynamic>))
        .toList();
    return PaginatedVideos(
      videos: videos,
      page: raw['page'] as int? ?? 1,
      lastPage: raw['last_page'] as int? ?? 1,
    );
  }

  PurchasedLibrary _hydratePurchased(Map<String, dynamic> raw) {
    return PurchasedLibrary(
      videos: ((raw['data'] as List?) ?? const [])
          .map((e) => VideoSummary.fromJson(e as Map<String, dynamic>))
          .toList(),
      page: raw['page'] as int? ?? 1,
      lastPage: raw['last_page'] as int? ?? 1,
      activePlans: ((raw['active_plans'] as List?) ?? const [])
          .map((e) => ActivePlan.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  Future<PaginatedVideos> watchLater({int page = 1}) async {
    if (page == 1) {
      // Flush any offline-queued add/removes before the read so the
      // server's response is already consistent with the user's intent.
      // Fire-and-forget so it doesn't block the read on a slow operator.
      // ignore: discarded_futures
      _flushPendingOps();
    }
    final result = await _list('/watch-later', page);
    if (page == 1) await _persistList('cache:watch-later:page-1', result);
    return result;
  }

  Future<PaginatedVideos> history({int page = 1}) async {
    final result = await _list('/history', page);
    if (page == 1) await _persistList('cache:history:page-1', result);
    return result;
  }

  Future<void> _persistList(String key, PaginatedVideos result) async {
    await _cache.writeJson(key, {
      'data': result.videos.map(_serialize).toList(),
      'page': result.page,
      'last_page': result.lastPage,
    });
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

  Future<PurchasedLibrary> purchased({int page = 1}) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        '/library/purchased',
        queryParameters: {'page': page},
      );
      final body = response.data!;
      final videos = (body['data'] as List)
          .map((e) => VideoSummary.fromJson(e as Map<String, dynamic>))
          .toList();
      final meta = (body['meta'] as Map?)?.cast<String, dynamic>() ?? const {};
      final plans = ((body['active_plans'] as List?) ?? const [])
          .map((e) => ActivePlan.fromJson(e as Map<String, dynamic>))
          .toList();
      final library = PurchasedLibrary(
        videos: videos,
        page: meta['current_page'] as int? ?? 1,
        lastPage: meta['last_page'] as int? ?? 1,
        activePlans: plans,
      );
      if (page == 1) {
        await _cache.writeJson('cache:purchased:page-1', {
          'data': library.videos.map(_serialize).toList(),
          'page': library.page,
          'last_page': library.lastPage,
          'active_plans': library.activePlans
              .map((p) => {
                    'plan_id': p.planId,
                    'slug': p.slug,
                    'name': p.name,
                    'expires_at': p.expiresAt.toIso8601String(),
                  })
              .toList(),
        });
      }
      return library;
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  static const _pendingOpsKey = 'cache:watch-later:pending-ops';

  /// Add to watch-later with offline queueing: if the network is down we
  /// still record the intent locally and replay on next successful fetch.
  Future<void> addWatchLater(int videoId) async {
    try {
      await _dio.post<void>('/watch-later/$videoId');
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout) {
        await _enqueuePending('add', videoId);
        return;
      }
      throw ApiException.fromDio(e);
    }
  }

  Future<void> removeWatchLater(int videoId) async {
    try {
      await _dio.delete<void>('/watch-later/$videoId');
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout) {
        await _enqueuePending('remove', videoId);
        return;
      }
      throw ApiException.fromDio(e);
    }
  }

  /// Replay any add/remove ops that were queued while the user was offline.
  /// Called from watchLater(page: 1) so any successful fetch also flushes
  /// the local intent — no separate background timer needed.
  Future<void> _flushPendingOps() async {
    final raw = await _cache.readJson(_pendingOpsKey);
    final ops = ((raw?['ops'] as List?) ?? const []).cast<Map<String, dynamic>>();
    if (ops.isEmpty) return;

    final survived = <Map<String, dynamic>>[];
    for (final op in ops) {
      final action = op['action'] as String;
      final videoId = op['video_id'] as int;
      try {
        if (action == 'add') {
          await _dio.post<void>('/watch-later/$videoId');
        } else {
          await _dio.delete<void>('/watch-later/$videoId');
        }
      } on DioException {
        survived.add(op);
      }
    }
    if (survived.isEmpty) {
      await _cache.remove(_pendingOpsKey);
    } else {
      await _cache.writeJson(_pendingOpsKey, {'ops': survived});
    }
  }

  Future<void> _enqueuePending(String action, int videoId) async {
    final raw = await _cache.readJson(_pendingOpsKey);
    final ops = ((raw?['ops'] as List?) ?? const []).cast<Map<String, dynamic>>().toList();
    // Compact: if the user added then removed (or vice versa) the latest
    // op wins. Same operation twice collapses to one.
    ops.removeWhere((existing) => existing['video_id'] == videoId);
    ops.add({'action': action, 'video_id': videoId});
    await _cache.writeJson(_pendingOpsKey, {'ops': ops});
  }

  Future<void> removeHistory(int videoId) async {
    try {
      await _dio.delete<void>('/history/$videoId');
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> clearHistory() async {
    try {
      await _dio.delete<void>('/history');
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<PaginatedVideos> _list(String path, int page) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        path,
        queryParameters: {'page': page},
      );
      final body = response.data!;
      final videos = (body['data'] as List)
          .map((e) => VideoSummary.fromJson(e as Map<String, dynamic>))
          .toList();
      final meta = (body['meta'] as Map?)?.cast<String, dynamic>() ?? const {};
      return PaginatedVideos(
        videos: videos,
        page: meta['current_page'] as int? ?? 1,
        lastPage: meta['last_page'] as int? ?? 1,
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}

class PurchasedLibrary {
  const PurchasedLibrary({
    required this.videos,
    required this.page,
    required this.lastPage,
    required this.activePlans,
  });

  final List<VideoSummary> videos;
  final int page;
  final int lastPage;
  final List<ActivePlan> activePlans;

  bool get hasMore => page < lastPage;
}

class ActivePlan {
  const ActivePlan({
    required this.planId,
    required this.slug,
    required this.name,
    required this.expiresAt,
  });

  factory ActivePlan.fromJson(Map<String, dynamic> json) {
    return ActivePlan(
      planId: json['plan_id'] as int,
      slug: json['slug'] as String? ?? '',
      name: json['name'] as String? ?? '',
      expiresAt: DateTime.tryParse(json['expires_at'] as String? ?? '') ??
          DateTime.now(),
    );
  }

  final int planId;
  final String slug;
  final String name;
  final DateTime expiresAt;
}
