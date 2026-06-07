/// Mirrors the VideoSummary schema in core/docs/api/openapi-v1.yaml.
class VideoSummary {
  const VideoSummary({
    required this.id,
    required this.slug,
    required this.title,
    required this.views,
    required this.isPaid,
    this.thumbnail,
    this.durationSeconds,
    this.price,
    this.channel,
    this.createdAt,
  });

  factory VideoSummary.fromJson(Map<String, dynamic> json) {
    final channel = json['channel'] as Map<String, dynamic>?;
    return VideoSummary(
      id: json['id'] as int,
      slug: json['slug'] as String,
      title: json['title'] as String? ?? '',
      thumbnail: json['thumbnail'] as String?,
      durationSeconds: json['duration_seconds'] as int?,
      views: (json['views'] as num?)?.toInt() ?? 0,
      isPaid: json['is_paid'] as bool? ?? false,
      price: (json['price'] as num?)?.toDouble(),
      channel: channel == null ? null : Channel.fromJson(channel),
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
    );
  }

  final int id;
  final String slug;
  final String title;
  final String? thumbnail;
  final int? durationSeconds;
  final int views;
  final bool isPaid;
  final double? price;
  final Channel? channel;
  final DateTime? createdAt;
}

class Channel {
  const Channel({required this.id, this.slug, this.name, this.avatar});

  factory Channel.fromJson(Map<String, dynamic> json) {
    return Channel(
      id: json['id'] as int,
      slug: json['slug'] as String?,
      name: json['name'] as String?,
      avatar: json['avatar'] as String?,
    );
  }

  final int id;
  final String? slug;
  final String? name;
  final String? avatar;
}
