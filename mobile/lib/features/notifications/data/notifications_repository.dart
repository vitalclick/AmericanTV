import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../domain/notification.dart';

final notificationsRepositoryProvider = Provider<NotificationsRepository>((ref) {
  return NotificationsRepository(ref.read(dioProvider));
});

class NotificationsRepository {
  NotificationsRepository(this._dio);
  final Dio _dio;

  Future<NotificationsPage> list({int page = 1}) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        '/me/notifications',
        queryParameters: {'page': page},
      );
      final body = response.data!;
      final items = (body['data'] as List)
          .map((e) => UserNotification.fromJson(e as Map<String, dynamic>))
          .toList();
      final meta = (body['meta'] as Map?)?.cast<String, dynamic>() ?? const {};
      return NotificationsPage(
        items: items,
        page: meta['current_page'] as int? ?? 1,
        lastPage: meta['last_page'] as int? ?? 1,
        unreadCount: meta['unread_count'] as int? ?? 0,
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> markRead(int id) async {
    try {
      await _dio.post<void>('/me/notifications/$id/read');
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> markAllRead() async {
    try {
      await _dio.post<void>('/me/notifications/read-all');
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}

class NotificationsPage {
  const NotificationsPage({
    required this.items,
    required this.page,
    required this.lastPage,
    required this.unreadCount,
  });

  final List<UserNotification> items;
  final int page;
  final int lastPage;
  final int unreadCount;
}

/// Lightweight provider used by the home shell's bell badge. Auto-disposes
/// once nothing reads it, so we don't keep polling state hot when the user
/// navigates away.
final unreadCountProvider = FutureProvider.autoDispose<int>((ref) async {
  final page = await ref.read(notificationsRepositoryProvider).list();
  return page.unreadCount;
});
