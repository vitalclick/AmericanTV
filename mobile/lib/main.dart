import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_crashlytics/firebase_crashlytics.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app.dart';
import 'core/env.dart';
import 'core/firebase_options.dart';
import 'core/services/purchases_service.dart';
import 'core/services/push_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await dotenv.load();

  // Each of these init blocks is intentionally guarded — the app must boot
  // even if Firebase / RevenueCat are mis-configured locally.
  await _initFirebase();

  final container = ProviderContainer();
  await container.read(purchasesServiceProvider).initialize();

  // Mounting PushService here (even though it's lazy via the provider) wires
  // the auth-state listener immediately, so a freshly-signed-in user gets
  // their FCM token registered without us needing to remember to watch it
  // from a widget.
  container.read(pushServiceProvider);

  runApp(
    UncontrolledProviderScope(container: container, child: const App()),
  );
}

Future<void> _initFirebase() async {
  if (!DefaultFirebaseOptions.isConfigured) {
    if (kDebugMode) {
      debugPrint('Firebase not configured — push + analytics disabled.');
    }
    return;
  }

  try {
    await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);

    if (Env.crashlyticsEnabled) {
      FlutterError.onError = FirebaseCrashlytics.instance.recordFlutterFatalError;
      PlatformDispatcher.instance.onError = (error, stack) {
        FirebaseCrashlytics.instance.recordError(error, stack, fatal: true);
        return true;
      };
    }
  } catch (e, st) {
    if (kDebugMode) {
      debugPrint('Firebase.initializeApp failed: $e\n$st');
    }
  }
}
