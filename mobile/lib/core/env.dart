import 'dart:io' show Platform;

import 'package:flutter/foundation.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';

/// Typed access to environment variables. Fails fast at startup if a required
/// key is missing so we don't discover the problem at the first network call.
class Env {
  Env._();

  static String get apiBaseUrl {
    final raw = _required('API_BASE_URL');
    // The Android emulator can't reach the host machine's `localhost`
    // (that name resolves to the emulator itself). Rewrite to 10.0.2.2,
    // which the emulator maps to the host's loopback. Debug builds only —
    // production builds should be pointing at a real hostname anyway.
    if (kDebugMode && !kIsWeb && Platform.isAndroid) {
      return raw
          .replaceFirst('://localhost', '://10.0.2.2')
          .replaceFirst('://127.0.0.1', '://10.0.2.2');
    }
    return raw;
  }

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
