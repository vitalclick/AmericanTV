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
    );
  }

  final int id;
  final String body;
  final int? parentId;
  final DateTime createdAt;
  final Channel? author;
  final int replyCount;
}
