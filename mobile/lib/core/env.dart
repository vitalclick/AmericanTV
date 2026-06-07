import 'package:flutter_dotenv/flutter_dotenv.dart';

/// Typed access to environment variables. Fails fast at startup if a required
/// key is missing so we don't discover the problem at the first network call.
class Env {
  Env._();

  static String get apiBaseUrl => _required('API_BASE_URL');

  static String? get revenueCatIosKey => dotenv.maybeGet('REVENUECAT_IOS_KEY');

  static String? get revenueCatAndroidKey =>
      dotenv.maybeGet('REVENUECAT_ANDROID_KEY');

  static String? get googleOauthClientIdAndroid =>
      dotenv.maybeGet('GOOGLE_OAUTH_CLIENT_ID_ANDROID');

  static bool get crashlyticsEnabled =>
      (dotenv.maybeGet('ENABLE_CRASHLYTICS') ?? 'false').toLowerCase() ==
      'true';

  static String _required(String key) {
    final value = dotenv.maybeGet(key);
    if (value == null || value.isEmpty) {
      throw StateError('Missing required env var: $key');
    }
    return value;
  }
}
