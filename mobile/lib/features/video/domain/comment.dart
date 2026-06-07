import '../../feed/domain/video_summary.dart';

/// Mirrors CommentResource on the backend.
class Comment {
  const Comment({
    required this.id,
    required this.body,
    required this.createdAt,
    this.parentId,
    this.author,
    this.replyCount = 0,
    this.likes = 0,
    this.userReaction = 0,
    this.replies = const [],
  });

  factory Comment.fromJson(Map<String, dynamic> json) {
    return Comment(
      id: json['id'] as int,
      body: json['body'] as String? ?? '',
      parentId: json['parent_id'] as int?,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String) ?? DateTime.now()
          : DateTime.now(),
      author: json['author'] is Map<String, dynamic>
          ? Channel.fromJson(json['author'] as Map<String, dynamic>)
          : null,
      replyCount: (json['reply_count'] as num?)?.toInt() ?? 0,
      likes: (json['likes'] as num?)?.toInt() ?? 0,
      userReaction: (json['user_reaction'] as num?)?.toInt() ?? 0,
      replies: ((json['replies'] as List?) ?? const [])
          .map((e) => Comment.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  final int id;
  final String body;
  final int? parentId;
  final DateTime createdAt;
  final Channel? author;
  final int replyCount;
  final int likes;
  final int userReaction;
  final List<Comment> replies;

  Comment copyWith({
    int? likes,
    int? userReaction,
    List<Comment>? replies,
    int? replyCount,
  }) {
    return Comment(
      id: id,
      body: body,
      parentId: parentId,
      createdAt: createdAt,
      author: author,
      replyCount: replyCount ?? this.replyCount,
      likes: likes ?? this.likes,
      userReaction: userReaction ?? this.userReaction,
      replies: replies ?? this.replies,
    );
  }

  /// Round-trips back through fromJson so the cache layer doesn't need a
  /// separate serializer per relationship.
  Map<String, dynamic> toJsonForCache() => {
        'id': id,
        'body': body,
        'parent_id': parentId,
        'created_at': createdAt.toIso8601String(),
        'author': author == null
            ? null
            : {
                'id': author!.id,
                'slug': author!.slug,
                'name': author!.name,
                'avatar': author!.avatar,
              },
        'reply_count': replyCount,
        'likes': likes,
        'user_reaction': userReaction,
        'replies': replies.map((r) => r.toJsonForCache()).toList(),
      };
}
