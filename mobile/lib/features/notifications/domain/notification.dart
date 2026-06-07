class UserNotification {
  const UserNotification({
    required this.id,
    required this.title,
    required this.isRead,
    required this.createdAt,
    this.clickUrl,
  });

  factory UserNotification.fromJson(Map<String, dynamic> json) {
    return UserNotification(
      id: json['id'] as int,
      title: json['title'] as String? ?? '',
      clickUrl: json['click_url'] as String?,
      isRead: json['is_read'] as bool? ?? false,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String) ?? DateTime.now()
          : DateTime.now(),
    );
  }

  final int id;
  final String title;
  final String? clickUrl;
  final bool isRead;
  final DateTime createdAt;
}
