/// Mirrors the `User` schema in core/docs/api/openapi-v1.yaml.
///
/// Hand-written rather than imported from the generated client so the auth
/// flow has zero codegen dependencies — useful when bootstrapping.
class User {
  const User({
    required this.id,
    required this.username,
    required this.email,
    required this.firstname,
    required this.lastname,
    this.avatar,
    this.balance = 0,
    this.emailVerified = false,
    this.mobileVerified = false,
    this.twoFactorEnabled = false,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int,
      username: json['username'] as String? ?? '',
      email: json['email'] as String,
      firstname: json['firstname'] as String? ?? '',
      lastname: json['lastname'] as String? ?? '',
      avatar: json['avatar'] as String?,
      balance: (json['balance'] as num?)?.toDouble() ?? 0,
      emailVerified: json['email_verified'] as bool? ?? false,
      mobileVerified: json['mobile_verified'] as bool? ?? false,
      twoFactorEnabled: json['two_factor_enabled'] as bool? ?? false,
    );
  }

  final int id;
  final String username;
  final String email;
  final String firstname;
  final String lastname;
  final String? avatar;
  final double balance;
  final bool emailVerified;
  final bool mobileVerified;
  final bool twoFactorEnabled;

  String get displayName =>
      [firstname, lastname].where((s) => s.isNotEmpty).join(' ').trim().isNotEmpty
          ? [firstname, lastname].join(' ').trim()
          : username;
}
