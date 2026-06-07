/// Response shape for GET /videos/{id}/source.
///
/// `hlsUrl` is set when the video has been migrated to Cloudflare Stream;
/// otherwise `mp4Url` (and `mp4Sources` for quality switching) is set against
/// the existing video library.
class VideoSource {
  const VideoSource({
    this.hlsUrl,
    this.mp4Url,
    this.mp4Sources = const [],
    this.poster,
    this.expiresAt,
    this.durationSeconds,
  });

  factory VideoSource.fromJson(Map<String, dynamic> json) {
    return VideoSource(
      hlsUrl: json['hls_url'] as String?,
      mp4Url: json['mp4_url'] as String?,
      mp4Sources: ((json['mp4_sources'] as List?) ?? const [])
          .map((e) => Mp4Source.fromJson(e as Map<String, dynamic>))
          .toList(),
      poster: json['poster'] as String?,
      expiresAt: json['expires_at'] != null
          ? DateTime.tryParse(json['expires_at'] as String)
          : null,
      durationSeconds: (json['duration_seconds'] as num?)?.toInt(),
    );
  }

  final String? hlsUrl;
  final String? mp4Url;
  final List<Mp4Source> mp4Sources;
  final String? poster;
  final DateTime? expiresAt;
  final int? durationSeconds;

  String? get playbackUrl => hlsUrl ?? mp4Url;
  bool get isHls => hlsUrl != null;
}

class Mp4Source {
  const Mp4Source({required this.url, this.quality, this.mimeType});

  factory Mp4Source.fromJson(Map<String, dynamic> json) {
    return Mp4Source(
      url: json['url'] as String,
      quality: json['quality']?.toString(),
      mimeType: json['mime_type'] as String?,
    );
  }

  final String url;
  final String? quality;
  final String? mimeType;
}
