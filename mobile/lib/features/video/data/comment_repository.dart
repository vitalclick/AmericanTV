import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../../../core/services/cache_service.dart';
import '../domain/comment.dart';

final commentRepositoryProvider = Provider<CommentRepository>((ref) {
  return CommentRepository(ref.read(dioProvider), ref.read(cacheServiceProvider));
});

class CommentRepository {
  CommentRepository(this._dio, this._cache);
  final Dio _dio;
  final CacheService _cache;

  String _cacheKey(int videoId) => 'cache:comments:$videoId:page-1';

  Future<PaginatedComments?> cachedFirstPage(int videoId) async {
    final raw = await _cache.readJson(_cacheKey(videoId));
    if (raw == null) return null;
    final comments = (raw['data'] as List)
        .map((e) => Comment.fromJson(e as Map<String, dynamic>))
        .toList();
    return PaginatedComments(
      comments: comments,
      page: raw['page'] as int? ?? 1,
      lastPage: raw['last_page'] as int? ?? 1,
    );
  }

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
      final result = PaginatedComments(
        comments: items,
        page: meta['current_page'] as int? ?? 1,
        lastPage: meta['last_page'] as int? ?? 1,
      );

      if (page == 1) {
        // Stash only the first page — older pages bloat SharedPreferences
        // and infinite-scroll users rarely cold-launch past page 1 anyway.
        await _cache.writeJson(_cacheKey(videoId), {
          'data': items.map((c) => c.toJsonForCache()).toList(),
          'page': result.page,
          'last_page': result.lastPage,
        });
      }

      return result;
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

  Future<Comment> reply(int parentId, String body) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/comments/$parentId/reply',
        data: {'body': body},
      );
      return Comment.fromJson(
        (response.data!['data'] as Map<String, dynamic>),
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<CommentReactionState> reactToComment({required int commentId, required int isLike}) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/comments/$commentId/reaction',
        data: {'is_like': isLike},
      );
      return CommentReactionState.fromJson(response.data!['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}

class CommentReactionState {
  const CommentReactionState({required this.userReaction, required this.likes});

  factory CommentReactionState.fromJson(Map<String, dynamic> json) {
    return CommentReactionState(
      userReaction: (json['user_reaction'] as num?)?.toInt() ?? 0,
      likes: (json['likes'] as num?)?.toInt() ?? 0,
    );
  }

  final int userReaction;
  final int likes;
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
