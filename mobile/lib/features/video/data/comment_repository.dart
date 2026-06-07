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
  static const _pendingCommentsKey = 'cache:comments:pending';

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
    if (page == 1) {
      // Replay offline-queued comments before the read so the server-side
      // list already reflects the user's pending intent. Fire-and-forget
      // so flush latency doesn't gate the read.
      // ignore: discarded_futures
      flushPending();
    }
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
      if (_isNetwork(e)) {
        await _enqueuePending(kind: 'comment', videoId: videoId, body: body);
        return Comment(
          id: -1, // sentinel: local-only until replay succeeds.
          body: body,
          createdAt: DateTime.now(),
        );
      }
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
      if (_isNetwork(e)) {
        await _enqueuePending(kind: 'reply', parentId: parentId, body: body);
        return Comment(
          id: -1,
          body: body,
          parentId: parentId,
          createdAt: DateTime.now(),
        );
      }
      throw ApiException.fromDio(e);
    }
  }

  bool _isNetwork(DioException e) =>
      e.type == DioExceptionType.connectionError ||
      e.type == DioExceptionType.connectionTimeout;

  Future<void> _enqueuePending({
    required String kind,
    int? videoId,
    int? parentId,
    required String body,
  }) async {
    final raw = await _cache.readJson(_pendingCommentsKey);
    final ops = ((raw?['ops'] as List?) ?? const []).cast<Map<String, dynamic>>().toList();
    ops.add({
      'kind': kind,
      if (videoId != null) 'video_id': videoId,
      if (parentId != null) 'parent_id': parentId,
      'body': body,
      'queued_at': DateTime.now().toIso8601String(),
    });
    await _cache.writeJson(_pendingCommentsKey, {'ops': ops});
  }

  /// Replays queued offline comments. Called from list(page: 1) so a
  /// successful read also flushes pending writes — same trick we use
  /// for watch-later.
  Future<void> flushPending() async {
    final raw = await _cache.readJson(_pendingCommentsKey);
    final ops = ((raw?['ops'] as List?) ?? const []).cast<Map<String, dynamic>>();
    if (ops.isEmpty) return;

    final survived = <Map<String, dynamic>>[];
    for (final op in ops) {
      try {
        if (op['kind'] == 'comment') {
          await _dio.post<void>(
            '/videos/${op['video_id']}/comments',
            data: {'body': op['body']},
          );
        } else if (op['kind'] == 'reply') {
          await _dio.post<void>(
            '/comments/${op['parent_id']}/reply',
            data: {'body': op['body']},
          );
        }
      } on DioException catch (e) {
        if (_isNetwork(e)) {
          survived.add(op);
        }
        // 4xx (e.g. video deleted while offline) drops the op.
      }
    }
    if (survived.isEmpty) {
      await _cache.remove(_pendingCommentsKey);
    } else {
      await _cache.writeJson(_pendingCommentsKey, {'ops': survived});
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
