import 'dart:async';
import 'dart:io' show Platform;

import 'package:dio/dio.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../api/dio_client.dart';
import '../../features/auth/application/auth_controller.dart';
import '../../features/auth/domain/auth_state.dart';

final pushServiceProvider = Provider<PushService>((ref) {
  final service = PushService(dio: ref.read(dioProvider));

  // Re-register the FCM token whenever the user signs in (and clear on sign-out).
  ref.listen<AuthState>(authControllerProvider, (prev, next) {
    if (next.status == AuthStatus.authenticated &&
        prev?.status != AuthStatus.authenticated) {
      service.bind();
    }
    if (next.status == AuthStatus.unauthenticated &&
        prev?.status == AuthStatus.authenticated) {
      service.unbind();
    }
  });

  return service;
});

class PushService {
  PushService({required Dio dio}) : _dio = dio;
  final Dio _dio;
  String? _cachedToken;
  StreamSubscription<String>? _refreshSub;

  /// Requests notification permission, fetches the FCM token, and POSTs it
  /// to /me/device-tokens. Idempotent — calling repeatedly is safe.
  Future<void> bind() async {
    try {
      final messaging = FirebaseMessaging.instance;
      final settings  = await messaging.requestPermission(alert: true, badge: true, sound: true);
      if (settings.authorizationStatus == AuthorizationStatus.denied) return;

      final token = await messaging.getToken();
      if (token == null) return;

      await _register(token);
      _cachedToken = token;

      _refreshSub ??= messaging.onTokenRefresh.listen(_register);
    } catch (e, st) {
      // Swallow — push registration must never crash the app.
      if (kDebugMode) {
        debugPrint('PushService.bind failed: $e\n$st');
      }
    }
  }

  Future<void> unbind() async {
    final token = _cachedToken;
    _cachedToken = null;
    await _refreshSub?.cancel();
    _refreshSub = null;

    if (token == null) return;
    try {
      await _dio.delete<void>('/me/device-tokens', data: {'token': token});
    } on DioException {
      // Best-effort.
    }
  }

  Future<void> _register(String token) async {
    try {
      await _dio.post<void>('/me/device-tokens', data: {
        'token': token,
        'platform': Platform.isIOS ? 'ios' : 'android',
      });
    } on DioException catch (e) {
      if (kDebugMode) {
        debugPrint('PushService._register failed: ${e.message}');
      }
    }
  }
}
