import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../api/api_exception.dart';
import '../data/notifications_repository.dart';
import '../domain/notification.dart';

final _notificationsProvider =
    FutureProvider.autoDispose<NotificationsPage>((ref) {
  return ref.read(notificationsRepositoryProvider).list();
});

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(_notificationsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          if (async.hasValue && async.value!.unreadCount > 0)
            TextButton(
              onPressed: () async {
                await ref.read(notificationsRepositoryProvider).markAllRead();
                ref
                  ..invalidate(_notificationsProvider)
                  ..invalidate(unreadCountProvider);
              },
              child: const Text('Mark all read'),
            ),
        ],
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Text(e is ApiException ? e.message : 'Could not load notifications.'),
        ),
        data: (page) {
          if (page.items.isEmpty) {
            return const Center(child: Text('Nothing here yet.'));
          }
          return RefreshIndicator(
            onRefresh: () async {
              ref
                ..invalidate(_notificationsProvider)
                ..invalidate(unreadCountProvider);
              await ref.read(_notificationsProvider.future);
            },
            child: ListView.separated(
              itemCount: page.items.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (_, i) => _NotificationTile(
                notification: page.items[i],
                onTap: () async {
                  await ref
                      .read(notificationsRepositoryProvider)
                      .markRead(page.items[i].id);
                  ref
                    ..invalidate(_notificationsProvider)
                    ..invalidate(unreadCountProvider);
                },
              ),
            ),
          );
        },
      ),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  const _NotificationTile({required this.notification, required this.onTap});
  final UserNotification notification;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      onTap: onTap,
      leading: Icon(
        notification.isRead ? Icons.notifications_none : Icons.notifications_active,
        color: notification.isRead
            ? Theme.of(context).disabledColor
            : Theme.of(context).colorScheme.primary,
      ),
      title: Text(
        notification.title,
        style: TextStyle(
          fontWeight: notification.isRead ? FontWeight.normal : FontWeight.w600,
        ),
      ),
      subtitle: Text(
        DateFormat.yMMMd().add_jm().format(notification.createdAt),
        style: Theme.of(context).textTheme.bodySmall,
      ),
    );
  }
}
