import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../../feed/data/feed_repository.dart';
import '../../feed/domain/video_summary.dart';

final libraryRepositoryProvider = Provider<LibraryRepository>((ref) {
  return LibraryRepository(ref.read(dioProvider));
});

class LibraryRepository {
  LibraryRepository(this._dio);
  final Dio _dio;

  Future<PaginatedVideos> watchLater({int page = 1}) =>
      _list('/watch-later', page);

  Future<PaginatedVideos> history({int page = 1}) =>
      _list('/history', page);

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
      return PurchasedLibrary(
        videos: videos,
        page: meta['current_page'] as int? ?? 1,
        lastPage: meta['last_page'] as int? ?? 1,
        activePlans: plans,
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> addWatchLater(int videoId) async {
    try {
      await _dio.post<void>('/watch-later/$videoId');
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> removeWatchLater(int videoId) async {
    try {
      await _dio.delete<void>('/watch-later/$videoId');
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
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
