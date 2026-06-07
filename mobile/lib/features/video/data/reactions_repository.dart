import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';

final reactionsRepositoryProvider = Provider<ReactionsRepository>((ref) {
  return ReactionsRepository(ref.read(dioProvider));
});

class ReactionsRepository {
  ReactionsRepository(this._dio);
  final Dio _dio;

  Future<ReactionState> reactToVideo({required int videoId, required int isLike}) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/videos/$videoId/reaction',
        data: {'is_like': isLike},
      );
      return ReactionState.fromJson(response.data!['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<bool> subscribeChannel(int channelId) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/channels/$channelId/subscribe',
      );
      return (response.data!['data'] as Map<String, dynamic>)['subscribed'] as bool;
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}

class ReactionState {
  const ReactionState({required this.userReaction, required this.likes, required this.dislikes});

  factory ReactionState.fromJson(Map<String, dynamic> json) {
    return ReactionState(
      userReaction: (json['user_reaction'] as num?)?.toInt() ?? 0,
      likes: (json['likes'] as num?)?.toInt() ?? 0,
      dislikes: (json['dislikes'] as num?)?.toInt() ?? 0,
    );
  }

  final int userReaction; // 1 = like, -1 = dislike, 0 = none
  final int likes;
  final int dislikes;
}
