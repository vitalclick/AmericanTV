import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../api/api_exception.dart';
import '../../feed/domain/video_summary.dart';
import '../data/library_repository.dart';

final _watchLaterProvider =
    StreamProvider.autoDispose<List<VideoSummary>>((ref) async* {
  final repo = ref.read(libraryRepositoryProvider);
  final cached = await repo.cachedWatchLater();
  if (cached != null) yield cached.videos;
  yield (await repo.watchLater()).videos;
});

final _historyProvider =
    StreamProvider.autoDispose<List<VideoSummary>>((ref) async* {
  final repo = ref.read(libraryRepositoryProvider);
  final cached = await repo.cachedHistory();
  if (cached != null) yield cached.videos;
  yield (await repo.history()).videos;
});

final _purchasedProvider =
    StreamProvider.autoDispose<PurchasedLibrary>((ref) async* {
  final repo = ref.read(libraryRepositoryProvider);
  final cached = await repo.cachedPurchased();
  if (cached != null) yield cached;
  yield await repo.purchased();
});

class LibraryScreen extends ConsumerWidget {
  const LibraryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return DefaultTabController(
      length: 3,
      child: Column(
        children: [
          const TabBar(
            tabs: [
              Tab(icon: Icon(Icons.watch_later_outlined), text: 'Watch Later'),
              Tab(icon: Icon(Icons.history), text: 'History'),
              Tab(icon: Icon(Icons.shopping_bag_outlined), text: 'Purchased'),
            ],
          ),
          Expanded(
            child: TabBarView(
              children: [
                _LibraryList(
                  provider: _watchLaterProvider,
                  emptyMessage: 'No videos saved for later.',
                  onRemove: (videoId) async {
                    // Optimistic: invalidate after we've already issued the
                    // request, so the user doesn't watch the tile snap to a
                    // spinner. removeWatchLater handles offline queueing
                    // internally — see LibraryRepository.
                    final removal = ref
                        .read(libraryRepositoryProvider)
                        .removeWatchLater(videoId);
                    await removal;
                    ref.invalidate(_watchLaterProvider);
                  },
                ),
                _LibraryList(
                  provider: _historyProvider,
                  emptyMessage: 'Your watch history is empty.',
                  trailingAction: TextButton(
                    onPressed: () async {
                      await ref.read(libraryRepositoryProvider).clearHistory();
                      ref.invalidate(_historyProvider);
                    },
                    child: const Text('Clear all'),
                  ),
                  onRemove: (videoId) async {
                    await ref.read(libraryRepositoryProvider).removeHistory(videoId);
                    ref.invalidate(_historyProvider);
                  },
                ),
                const _PurchasedTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _LibraryList extends ConsumerWidget {
  const _LibraryList({
    required this.provider,
    required this.emptyMessage,
    required this.onRemove,
    this.trailingAction,
  });

  final AutoDisposeStreamProvider<List<VideoSummary>> provider;
  final String emptyMessage;
  final Future<void> Function(int videoId) onRemove;
  final Widget? trailingAction;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(provider);
    return async.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => Center(
        child: Text(e is ApiException ? e.message : 'Could not load library.'),
      ),
      data: (videos) {
        if (videos.isEmpty) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Text(emptyMessage, textAlign: TextAlign.center),
            ),
          );
        }
        return Column(
          children: [
            if (trailingAction != null)
              Align(
                alignment: Alignment.centerRight,
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 8),
                  child: trailingAction,
                ),
              ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () async => ref.invalidate(provider),
                child: ListView.separated(
                  itemCount: videos.length,
                  separatorBuilder: (_, __) => const Divider(height: 1),
                  itemBuilder: (_, i) => _Tile(
                    video: videos[i],
                    onRemove: () => onRemove(videos[i].id),
                  ),
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

class _Tile extends StatelessWidget {
  const _Tile({required this.video, required this.onRemove});
  final VideoSummary video;
  final Future<void> Function() onRemove;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      onTap: () => context.push('/video/${video.slug}'),
      leading: SizedBox(
        width: 120,
        height: 68,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(6),
          child: video.thumbnail != null
              ? CachedNetworkImage(imageUrl: video.thumbnail!, fit: BoxFit.cover)
              : Container(color: Theme.of(context).colorScheme.surfaceContainerHighest),
        ),
      ),
      title: Text(video.title, maxLines: 2, overflow: TextOverflow.ellipsis),
      subtitle: Text(
        [video.channel?.name, '${video.views} views']
            .where((s) => s != null && s.isNotEmpty)
            .join(' • '),
        style: Theme.of(context).textTheme.bodySmall,
      ),
      trailing: IconButton(
        icon: const Icon(Icons.close),
        tooltip: 'Remove',
        onPressed: onRemove,
      ),
    );
  }
}

class _PurchasedTab extends ConsumerWidget {
  const _PurchasedTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(_purchasedProvider);
    return async.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => Center(
        child: Text(e is ApiException ? e.message : 'Could not load purchases.'),
      ),
      data: (library) {
        if (library.videos.isEmpty && library.activePlans.isEmpty) {
          return const Center(
            child: Padding(
              padding: EdgeInsets.all(24),
              child: Text(
                "You haven't purchased anything yet.",
                textAlign: TextAlign.center,
              ),
            ),
          );
        }
        return RefreshIndicator(
          onRefresh: () async => ref.invalidate(_purchasedProvider),
          child: ListView(
            children: [
              if (library.activePlans.isNotEmpty) ...[
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                  child: Text(
                    'ACTIVE SUBSCRIPTIONS',
                    style: Theme.of(context).textTheme.labelSmall?.copyWith(
                          letterSpacing: 1.2,
                          color: Theme.of(context).disabledColor,
                        ),
                  ),
                ),
                for (final plan in library.activePlans)
                  ListTile(
                    leading: const Icon(Icons.workspace_premium_outlined),
                    title: Text(plan.name),
                    subtitle: Text(
                      'Renews ${_relative(plan.expiresAt)}',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ),
                const Divider(height: 1),
              ],
              if (library.videos.isNotEmpty) ...[
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                  child: Text(
                    'VIDEOS',
                    style: Theme.of(context).textTheme.labelSmall?.copyWith(
                          letterSpacing: 1.2,
                          color: Theme.of(context).disabledColor,
                        ),
                  ),
                ),
                for (final video in library.videos)
                  _PurchasedVideoTile(video: video),
              ],
            ],
          ),
        );
      },
    );
  }

  String _relative(DateTime when) {
    final diff = when.difference(DateTime.now());
    if (diff.isNegative) return 'expired';
    if (diff.inDays >= 1) return 'in ${diff.inDays} days';
    return 'in ${diff.inHours} hours';
  }
}

class _PurchasedVideoTile extends StatelessWidget {
  const _PurchasedVideoTile({required this.video});
  final VideoSummary video;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      onTap: () => context.push('/video/${video.slug}'),
      leading: SizedBox(
        width: 120,
        height: 68,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(6),
          child: video.thumbnail != null
              ? CachedNetworkImage(imageUrl: video.thumbnail!, fit: BoxFit.cover)
              : Container(color: Theme.of(context).colorScheme.surfaceContainerHighest),
        ),
      ),
      title: Text(video.title, maxLines: 2, overflow: TextOverflow.ellipsis),
      subtitle: Text(
        video.channel?.name ?? '',
        style: Theme.of(context).textTheme.bodySmall,
      ),
    );
  }
}
