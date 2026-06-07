import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../api/api_exception.dart';
import '../../feed/domain/video_summary.dart';
import '../data/library_repository.dart';

final _watchLaterProvider =
    FutureProvider.autoDispose<List<VideoSummary>>((ref) async {
  final page = await ref.read(libraryRepositoryProvider).watchLater();
  return page.videos;
});

final _historyProvider =
    FutureProvider.autoDispose<List<VideoSummary>>((ref) async {
  final page = await ref.read(libraryRepositoryProvider).history();
  return page.videos;
});

class LibraryScreen extends ConsumerWidget {
  const LibraryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return DefaultTabController(
      length: 2,
      child: Column(
        children: [
          const TabBar(
            tabs: [
              Tab(icon: Icon(Icons.watch_later_outlined), text: 'Watch Later'),
              Tab(icon: Icon(Icons.history), text: 'History'),
            ],
          ),
          Expanded(
            child: TabBarView(
              children: [
                _LibraryList(
                  provider: _watchLaterProvider,
                  emptyMessage: 'No videos saved for later.',
                  onRemove: (videoId) async {
                    await ref.read(libraryRepositoryProvider).removeWatchLater(videoId);
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

  final AutoDisposeFutureProvider<List<VideoSummary>> provider;
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
