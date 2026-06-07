import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../domain/comment.dart';

final commentRepositoryProvider = Provider<CommentRepository>((ref) {
  return CommentRepository(ref.read(dioProvider));
});

class CommentRepository {
  CommentRepository(this._dio);
  final Dio _dio;

  Future<PaginatedComments> list(int videoId, {int page = 1}) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        '/videos/$videoId/comments',
        queryParameters: {'page': page},
      );
      final body = response.data!;
      final items = (body['data'] as List)
          .map((e) => Comment.fromJson(e as Map<String, dynamic>))
          .toList();
      final meta = (body['meta'] as Map?)?.cast<String, dynamic>() ?? const {};
      return PaginatedComments(
        comments: items,
        page: meta['current_page'] as int? ?? 1,
        lastPage: meta['last_page'] as int? ?? 1,
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<Comment> post(int videoId, String body) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/videos/$videoId/comments',
        data: {'body': body},
      );
      return Comment.fromJson(
        (response.data!['data'] as Map<String, dynamic>),
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}

class PaginatedComments {
  const PaginatedComments({
    required this.comments,
    required this.page,
    required this.lastPage,
  });

  final List<Comment> comments;
  final int page;
  final int lastPage;

  bool get hasMore => page < lastPage;
}
