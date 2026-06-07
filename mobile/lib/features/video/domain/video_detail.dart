import '../../feed/domain/video_summary.dart';

/// Mirrors the VideoDetail schema in core/docs/api/openapi-v1.yaml.
class VideoDetail {
  const VideoDetail({
    required this.summary,
    required this.description,
    required this.tags,
    required this.likes,
    required this.dislikes,
    required this.comments,
    required this.userReaction,
    required this.userHasAccess,
    required this.subtitles,
    this.category,
    this.accessPlans = const [],
  });

  factory VideoDetail.fromJson(Map<String, dynamic> json) {
    return VideoDetail(
      summary: VideoSummary.fromJson(json),
      description: json['description'] as String? ?? '',
      category: json['category'] is Map<String, dynamic>
          ? Category.fromJson(json['category'] as Map<String, dynamic>)
          : null,
      tags: ((json['tags'] as List?) ?? const [])
          .map((e) => e.toString())
          .toList(),
      likes: (json['likes'] as num?)?.toInt() ?? 0,
      dislikes: (json['dislikes'] as num?)?.toInt() ?? 0,
      comments: (json['comments'] as num?)?.toInt() ?? 0,
      userReaction: (json['user_reaction'] as num?)?.toInt() ?? 0,
      userHasAccess: json['user_has_access'] as bool? ?? true,
      subtitles: ((json['subtitles'] as List?) ?? const [])
          .map((e) => Subtitle.fromJson(e as Map<String, dynamic>))
          .toList(),
      accessPlans: ((json['access_plans'] as List?) ?? const [])
          .map((e) => AccessPlan.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  final VideoSummary summary;
  final String description;
  final Category? category;
  final List<String> tags;
  final int likes;
  final int dislikes;
  final int comments;
  final int userReaction;
  final bool userHasAccess;
  final List<Subtitle> subtitles;
  final List<AccessPlan> accessPlans;
}

class AccessPlan {
  const AccessPlan({
    required this.id,
    required this.slug,
    required this.name,
    required this.priceUsd,
    this.appleProductId,
    this.googleProductId,
    this.mobilePriceUsd,
  });

  factory AccessPlan.fromJson(Map<String, dynamic> json) {
    final iap = (json['iap'] as Map<String, dynamic>?) ?? const {};
    return AccessPlan(
      id: json['id'] as int,
      slug: json['slug'] as String,
      name: json['name'] as String? ?? '',
      priceUsd: (json['price'] as num?)?.toDouble() ?? 0,
      appleProductId: iap['apple_product_id'] as String?,
      googleProductId: iap['google_product_id'] as String?,
      mobilePriceUsd: (iap['mobile_price_usd'] as num?)?.toDouble(),
    );
  }

  final int id;
  final String slug;
  final String name;
  final double priceUsd;
  final String? appleProductId;
  final String? googleProductId;
  final double? mobilePriceUsd;

  bool get isMobileAvailable =>
      appleProductId != null || googleProductId != null;
}

class Category {
  const Category({required this.id, this.slug, this.name});

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: json['id'] as int,
      slug: json['slug'] as String?,
      name: json['name'] as String?,
    );
  }

  final int id;
  final String? slug;
  final String? name;
}

class Subtitle {
  const Subtitle({this.language, this.url});

  factory Subtitle.fromJson(Map<String, dynamic> json) {
    return Subtitle(
      language: json['language'] as String?,
      url: json['url'] as String?,
    );
  }

  final String? language;
  final String? url;
}
