import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../domain/video_summary.dart';

final feedRepositoryProvider = Provider<FeedRepository>((ref) {
  return FeedRepository(ref.read(dioProvider));
});

class FeedRepository {
  FeedRepository(this._dio);
  final Dio _dio;

  Future<PaginatedVideos> feed({int page = 1}) =>
      _fetchVideoList('/feed', {'page': page});

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
      final body = response.data!;
      final list = (body['data'] as List)
          .map((e) => VideoSummary.fromJson(e as Map<String, dynamic>))
          .toList();
      final meta = (body['meta'] as Map?)?.cast<String, dynamic>() ?? const {};
      return PaginatedVideos(
        videos: list,
        page: meta['current_page'] as int? ?? 1,
        lastPage: meta['last_page'] as int? ?? 1,
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
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
