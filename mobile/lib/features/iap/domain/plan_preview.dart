class PlanPreview {
  const PlanPreview({
    required this.id,
    required this.slug,
    required this.name,
    required this.priceUsd,
    required this.videoCount,
    required this.playlistCount,
    this.creatorName,
    this.iap,
  });

  factory PlanPreview.fromJson(Map<String, dynamic> json) {
    return PlanPreview(
      id: json['id'] as int,
      slug: json['slug'] as String,
      name: json['name'] as String? ?? '',
      priceUsd: (json['price'] as num?)?.toDouble() ?? 0,
      videoCount: (json['video_count'] as num?)?.toInt() ?? 0,
      playlistCount: (json['playlist_count'] as num?)?.toInt() ?? 0,
      creatorName: (json['creator'] as Map?)?['name'] as String?,
      iap: json['iap'] is Map<String, dynamic>
          ? IapMapping.fromJson(json['iap'] as Map<String, dynamic>)
          : null,
    );
  }

  final int id;
  final String slug;
  final String name;
  final double priceUsd;
  final int videoCount;
  final int playlistCount;
  final String? creatorName;
  final IapMapping? iap;

  String? get storeProductId =>
      iap?.googleProductId ?? iap?.appleProductId; // Either works as the
  // RevenueCat package identifier.
}

class IapMapping {
  const IapMapping({this.appleProductId, this.googleProductId, this.mobilePriceUsd = 0});

  factory IapMapping.fromJson(Map<String, dynamic> json) {
    return IapMapping(
      appleProductId: json['apple_product_id'] as String?,
      googleProductId: json['google_product_id'] as String?,
      mobilePriceUsd: (json['mobile_price_usd'] as num?)?.toDouble() ?? 0,
    );
  }

  final String? appleProductId;
  final String? googleProductId;
  final double mobilePriceUsd;
}
