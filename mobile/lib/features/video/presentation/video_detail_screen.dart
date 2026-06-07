import 'dart:async';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../data/video_repository.dart';
import '../domain/video_detail.dart';
import '../domain/video_source.dart';
import 'video_player_screen.dart';

final _videoDetailProvider =
    FutureProvider.autoDispose.family<VideoDetail, String>((ref, slug) {
  return ref.read(videoRepositoryProvider).show(slug);
});

class VideoDetailScreen extends ConsumerWidget {
  const VideoDetailScreen({required this.slug, super.key});
  final String slug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detailAsync = ref.watch(_videoDetailProvider(slug));

    return Scaffold(
      appBar: AppBar(),
      body: detailAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(
              e is ApiException
                  ? e.message
                  : 'Could not load this video.',
              textAlign: TextAlign.center,
            ),
          ),
        ),
        data: (detail) => _DetailBody(detail: detail),
      ),
    );
  }
}

class _DetailBody extends ConsumerWidget {
  const _DetailBody({required this.detail});
  final VideoDetail detail;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final summary = detail.summary;

    return ListView(
      padding: EdgeInsets.zero,
      children: [
        AspectRatio(
          aspectRatio: 16 / 9,
          child: Stack(
            fit: StackFit.expand,
            children: [
              if (summary.thumbnail != null)
                CachedNetworkImage(imageUrl: summary.thumbnail!, fit: BoxFit.cover)
              else
                Container(color: Theme.of(context).colorScheme.surfaceContainerHighest),
              if (detail.userHasAccess)
                Center(
                  child: Material(
                    color: Colors.black54,
                    shape: const CircleBorder(),
                    child: IconButton(
                      iconSize: 64,
                      icon: const Icon(Icons.play_arrow, color: Colors.white),
                      onPressed: () => _play(context, ref),
                    ),
                  ),
                )
              else
                Center(
                  child: _PaywallBadge(price: summary.price ?? 0),
                ),
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(summary.title, style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 4,
                children: [
                  if (summary.channel?.name != null)
                    Text(
                      summary.channel!.name!,
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  Text('${summary.views} views',
                      style: Theme.of(context).textTheme.bodySmall),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  _Stat(icon: Icons.thumb_up_outlined, value: detail.likes),
                  const SizedBox(width: 16),
                  _Stat(icon: Icons.thumb_down_outlined, value: detail.dislikes),
                  const SizedBox(width: 16),
                  _Stat(icon: Icons.comment_outlined, value: detail.comments),
                ],
              ),
              const Divider(height: 32),
              if (detail.description.isNotEmpty) ...[
                Text(detail.description),
                const SizedBox(height: 16),
              ],
              if (detail.tags.isNotEmpty)
                Wrap(
                  spacing: 8,
                  children: detail.tags
                      .map((t) => Chip(label: Text(t), visualDensity: VisualDensity.compact))
                      .toList(),
                ),
            ],
          ),
        ),
      ],
    );
  }

  Future<void> _play(BuildContext context, WidgetRef ref) async {
    final repo = ref.read(videoRepositoryProvider);
    final messenger = ScaffoldMessenger.of(context);
    final navigator = Navigator.of(context);
    try {
      final source = await repo.source(detail.summary.id);
      if (source.playbackUrl == null) {
        messenger.showSnackBar(
          const SnackBar(content: Text('No playable source for this video.')),
        );
        return;
      }
      unawaited(repo.recordView(detail.summary.id));
      await navigator.push(
        MaterialPageRoute<void>(
          builder: (_) => VideoPlayerScreen(source: source, title: detail.summary.title),
        ),
      );
    } on ApiException catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(e.message)));
    }
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.icon, required this.value});
  final IconData icon;
  final int value;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 20),
        const SizedBox(width: 4),
        Text(value.toString()),
      ],
    );
  }
}

class _PaywallBadge extends StatelessWidget {
  const _PaywallBadge({required this.price});
  final double price;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.black87,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.lock_outline, color: Colors.white),
          const SizedBox(width: 8),
          Text(
            'Unlock for \$${price.toStringAsFixed(2)}',
            style: const TextStyle(color: Colors.white),
          ),
        ],
      ),
    );
  }
}

