import 'dart:io' show Platform;

import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:purchases_flutter/purchases_flutter.dart';

import '../env.dart';
import '../../features/auth/application/auth_controller.dart';
import '../../features/auth/domain/auth_state.dart';

final purchasesServiceProvider = Provider<PurchasesService>((ref) {
  final service = PurchasesService();

  // Identify / log out the RevenueCat user alongside Sanctum sessions so the
  // entitlement record is keyed to the same Laravel user id.
  ref.listen<AuthState>(authControllerProvider, (prev, next) {
    final id = next.user?.id;
    if (id != null && prev?.user?.id != id) {
      service.identify(id);
    }
    if (next.status == AuthStatus.unauthenticated &&
        prev?.status == AuthStatus.authenticated) {
      service.logOut();
    }
  });

  return service;
});

/// Thin wrapper over the RevenueCat SDK. Initialization is one-shot and runs
/// from main(); the rest of the app talks through this class.
class PurchasesService {
  bool _initialized = false;

  Future<void> initialize() async {
    if (_initialized) return;

    final key = Platform.isIOS ? Env.revenueCatIosKey : Env.revenueCatAndroidKey;
    if (key == null || key.isEmpty) {
      if (kDebugMode) {
        debugPrint('RevenueCat key missing — purchases disabled.');
      }
      return;
    }

    try {
      await Purchases.setLogLevel(kDebugMode ? LogLevel.debug : LogLevel.warn);
      await Purchases.configure(PurchasesConfiguration(key));
      _initialized = true;
    } catch (e, st) {
      if (kDebugMode) {
        debugPrint('Purchases.configure failed: $e\n$st');
      }
    }
  }

  Future<void> identify(int userId) async {
    if (!_initialized) return;
    try {
      await Purchases.logIn(userId.toString());
    } catch (_) {
      // Best-effort.
    }
  }

  Future<void> logOut() async {
    if (!_initialized) return;
    try {
      await Purchases.logOut();
    } catch (_) {
      // Best-effort. Throws if already anonymous, which we don't care about.
    }
  }
}
