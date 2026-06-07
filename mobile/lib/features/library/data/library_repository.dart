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
