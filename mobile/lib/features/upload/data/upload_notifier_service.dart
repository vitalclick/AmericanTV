import 'dart:async';
import 'dart:io' show Platform;

import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:wakelock_plus/wakelock_plus.dart';

final uploadNotifierServiceProvider = Provider<UploadNotifierService>((_) {
  return UploadNotifierService();
});

/// Keeps an in-flight upload alive when the user navigates away from the
/// upload screen, by:
///
/// 1. Acquiring a wakelock so the screen doesn't sleep / the OS doesn't
///    aggressively throttle the network thread mid-flight.
/// 2. Posting a sticky local notification with a progress bar so the user
///    knows the upload is still running.
///
/// What this does NOT do: true background execution after the app is killed.
/// On Android the OS may continue to schedule the process while the
/// foreground notification is up, but only briefly; on iOS, URLSession
/// background tasks would be the right tool but require native code we
/// haven't shipped. Resumable uploads (see UploadRepository.resume) cover
/// the case where the OS does kill us mid-upload.
class UploadNotifierService {
  static const _notificationId = 1001;
  static const _channelId = 'uploads';
  static const _channelName = 'Video uploads';

  final FlutterLocalNotificationsPlugin _plugin = FlutterLocalNotificationsPlugin();
  bool _initialized = false;

  Future<void> _ensureInit() async {
    if (_initialized) return;
    try {
      const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
      const iosSettings = DarwinInitializationSettings(
        requestAlertPermission: false, // upload progress isn't important enough
        requestBadgePermission: false, // to ask for the permission prompt
        requestSoundPermission: false,
      );
      await _plugin.initialize(const InitializationSettings(
        android: androidSettings,
        iOS: iosSettings,
      ));
      _initialized = true;
    } catch (e) {
      if (kDebugMode) {
        debugPrint('UploadNotifierService init failed: $e');
      }
    }
  }

  Future<void> begin({required String title}) async {
    await _ensureInit();
    unawaited(WakelockPlus.enable());
    await _notify(title: title, progress: 0);
  }

  Future<void> update({required String title, required double progress}) async {
    await _notify(title: title, progress: progress);
  }

  Future<void> finish({required bool success, String? finalMessage}) async {
    unawaited(WakelockPlus.disable());
    if (!_initialized) return;
    if (success) {
      await _plugin.show(
        _notificationId,
        'Upload complete',
        finalMessage ?? 'Your video is processing.',
        _details(progress: 100, ongoing: false),
      );
    } else {
      await _plugin.show(
        _notificationId,
        'Upload failed',
        finalMessage ?? 'Open the app to retry or resume.',
        _details(progress: 0, ongoing: false),
      );
    }
  }

  Future<void> cancel() async {
    unawaited(WakelockPlus.disable());
    if (!_initialized) return;
    await _plugin.cancel(_notificationId);
  }

  Future<void> _notify({required String title, required double progress}) async {
    if (!_initialized) return;
    final pct = (progress * 100).round();
    try {
      await _plugin.show(
        _notificationId,
        'Uploading $title',
        pct > 0 ? '$pct%' : 'Preparing…',
        _details(progress: pct, ongoing: true),
      );
    } catch (e) {
      if (kDebugMode) debugPrint('notify failed: $e');
    }
  }

  NotificationDetails _details({required int progress, required bool ongoing}) {
    final android = AndroidNotificationDetails(
      _channelId,
      _channelName,
      channelDescription: 'Progress for in-flight video uploads.',
      importance: Importance.low,
      priority: Priority.low,
      ongoing: ongoing,
      showProgress: ongoing,
      maxProgress: 100,
      progress: progress,
      onlyAlertOnce: true,
    );
    final iosDetails = Platform.isIOS
        ? const DarwinNotificationDetails(presentSound: false)
        : null;
    return NotificationDetails(android: android, iOS: iosDetails);
  }
}
