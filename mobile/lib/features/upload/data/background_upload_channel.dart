import 'dart:async';
import 'dart:io' show Platform;

import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

final backgroundUploadChannelProvider = Provider<BackgroundUploadChannel>((_) {
  return BackgroundUploadChannel();
});

/// Dart-side facade over the `americantv/background_upload` method channel.
///
/// On iOS this routes through `BackgroundUploadHandler.swift` which uses
/// `URLSession(configuration: .background(withIdentifier:))` to keep upload
/// tasks alive when the app is suspended or killed — the only way to upload
/// reliably in iOS's background. The Swift side fires `progress` and
/// `completed` / `failed` events back through this channel.
///
/// On Android we fall back to the in-process upload driven by
/// UploadRepository plus the existing wakelock + foreground notification
/// (UploadNotifierService) — Android's process-while-notification-up rules
/// keep that loop alive long enough for most uploads, and resume covers
/// the rest. A custom WorkManager-backed plugin would be the next step.
class BackgroundUploadChannel {
  static const _channel = MethodChannel('americantv/background_upload');
  static const _events  = EventChannel('americantv/background_upload/events');

  bool get isSupported => Platform.isIOS;

  /// Hand a single chunk request off to the native scheduler. Returns the
  /// scheduler-assigned task id so the caller can correlate progress events.
  /// Falls through to a Dart-driven null result on Android (use the
  /// in-process repository there).
  Future<String?> scheduleChunk({
    required String uniqueId,
    required int index,
    required String filePath,
    required int offset,
    required int length,
    required String endpointUrl,
    required Map<String, String> headers,
    required Map<String, String> formFields,
  }) async {
    if (!isSupported) return null;
    try {
      return await _channel.invokeMethod<String>('scheduleChunk', {
        'uniqueId':    uniqueId,
        'index':       index,
        'filePath':    filePath,
        'offset':      offset,
        'length':      length,
        'endpointUrl': endpointUrl,
        'headers':     headers,
        'formFields':  formFields,
      });
    } on PlatformException catch (e) {
      if (kDebugMode) debugPrint('scheduleChunk failed: ${e.message}');
      return null;
    }
  }

  Future<void> cancel({required String uniqueId}) async {
    if (!isSupported) return;
    try {
      await _channel.invokeMethod<void>('cancel', {'uniqueId': uniqueId});
    } on PlatformException catch (_) {
      // Best-effort.
    }
  }

  /// Events: { uniqueId, index, kind: 'progress'|'completed'|'failed',
  ///           bytesSent?, error? }
  Stream<BackgroundUploadEvent> events() {
    if (!isSupported) return const Stream<BackgroundUploadEvent>.empty();
    return _events
        .receiveBroadcastStream()
        .map((dynamic raw) => BackgroundUploadEvent.fromMap(
              Map<String, dynamic>.from(raw as Map),
            ));
  }
}

class BackgroundUploadEvent {
  const BackgroundUploadEvent({
    required this.uniqueId,
    required this.index,
    required this.kind,
    this.bytesSent,
    this.error,
  });

  factory BackgroundUploadEvent.fromMap(Map<String, dynamic> map) {
    return BackgroundUploadEvent(
      uniqueId: map['uniqueId'] as String,
      index: (map['index'] as num).toInt(),
      kind: map['kind'] as String,
      bytesSent: (map['bytesSent'] as num?)?.toInt(),
      error: map['error'] as String?,
    );
  }

  final String uniqueId;
  final int index;
  final String kind;
  final int? bytesSent;
  final String? error;
}
