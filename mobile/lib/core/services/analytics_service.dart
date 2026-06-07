import 'dart:async';
import 'dart:io' show Platform;
import 'dart:math';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../api/dio_client.dart';

final analyticsServiceProvider = Provider<AnalyticsService>((ref) {
  return AnalyticsService(ref.read(dioProvider));
});

/// Lightweight in-memory event queue with periodic flush.
///
/// Why not a battery of Firebase events directly: we already record ad
/// engagement server-side (AdvertisementAnalytics, AdvertisementReached) —
/// this surface captures the rest (tile taps, watch durations, paywall
/// impressions) into the same database so product + finance queries can
/// join across without two analytics warehouses.
class AnalyticsService {
  AnalyticsService(this._dio) : _sessionId = _newSessionId() {
    _timer = Timer.periodic(const Duration(seconds: 20), (_) => _flush());
  }

  static const _flushSize = 25;
  static const _maxQueueSize = 200; // drop oldest beyond this to bound memory.

  final Dio _dio;
  final String _sessionId;
  final List<Map<String, dynamic>> _queue = [];
  Timer? _timer;

  void track(
    String name, {
    int? videoId,
    Map<String, dynamic>? payload,
  }) {
    final event = {
      'name': name,
      'occurred_at': DateTime.now().toUtc().toIso8601String(),
      'platform': Platform.isIOS ? 'ios' : 'android',
      'session_id': _sessionId,
      if (videoId != null) 'video_id': videoId,
      if (payload != null) 'payload': payload,
    };
    _queue.add(event);
    if (_queue.length > _maxQueueSize) {
      _queue.removeRange(0, _queue.length - _maxQueueSize);
    }
    if (_queue.length >= _flushSize) {
      // Fire-and-forget; we don't await so the call site stays sync.
      unawaited(_flush());
    }
  }

  /// Forces a flush — call before navigating to a screen that might
  /// background the app, e.g. opening a system view.
  Future<void> flush() => _flush();

  Future<void> _flush() async {
    if (_queue.isEmpty) return;
    final batch = List<Map<String, dynamic>>.from(_queue);
    _queue.clear();
    try {
      await _dio.post<void>('/analytics/events', data: {'events': batch});
    } catch (e) {
      // Re-queue but cap so a persistent failure can't grow unbounded.
      _queue.insertAll(0, batch.take(_maxQueueSize - _queue.length));
      if (kDebugMode) debugPrint('analytics flush failed: $e');
    }
  }

  static String _newSessionId() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    final rand = Random.secure();
    return List.generate(16, (_) => chars[rand.nextInt(chars.length)]).join();
  }

  void dispose() {
    _timer?.cancel();
    unawaited(flush());
  }
}
