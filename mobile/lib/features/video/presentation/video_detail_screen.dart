import 'dart:async';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../data/reactions_repository.dart';
import '../data/video_repository.dart';
import '../domain/video_detail.dart';
import '../domain/video_source.dart';
import 'comments_section.dart';
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
              _ReactionBar(detail: detail),
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
        const Divider(height: 32),
        CommentsSection(videoId: summary.id),
        const SizedBox(height: 24),
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
  const _Stat({required this.icon, required this.value, this.active = false, this.onTap});
  final IconData icon;
  final int value;
  final bool active;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final color = active
        ? Theme.of(context).colorScheme.primary
        : Theme.of(context).colorScheme.onSurface;
    final child = Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 20, color: color),
        const SizedBox(width: 4),
        Text(value.toString(), style: TextStyle(color: color)),
      ],
    );
    if (onTap == null) return child;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
        child: child,
      ),
    );
  }
}

/// Like / dislike / comment counter row that mutates state locally on tap.
/// Mirrors the toggle/swap/remove semantics on the backend: tapping the
/// same direction twice clears the reaction.
class _ReactionBar extends ConsumerStatefulWidget {
  const _ReactionBar({required this.detail});
  final VideoDetail detail;

  @override
  ConsumerState<_ReactionBar> createState() => _ReactionBarState();
}

class _ReactionBarState extends ConsumerState<_ReactionBar> {
  late int _userReaction = widget.detail.userReaction;
  late int _likes = widget.detail.likes;
  late int _dislikes = widget.detail.dislikes;
  bool _busy = false;

  Future<void> _react(int direction) async {
    if (_busy) return;
    final isLike = direction == 1 ? 1 : 0;
    setState(() => _busy = true);
    try {
      final state = await ref
          .read(reactionsRepositoryProvider)
          .reactToVideo(videoId: widget.detail.summary.id, isLike: isLike);
      setState(() {
        _userReaction = state.userReaction;
        _likes = state.likes;
        _dislikes = state.dislikes;
      });
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        _Stat(
          icon: _userReaction == 1 ? Icons.thumb_up : Icons.thumb_up_outlined,
          value: _likes,
          active: _userReaction == 1,
          onTap: () => _react(1),
        ),
        const SizedBox(width: 8),
        _Stat(
          icon: _userReaction == -1 ? Icons.thumb_down : Icons.thumb_down_outlined,
          value: _dislikes,
          active: _userReaction == -1,
          onTap: () => _react(-1),
        ),
        const SizedBox(width: 8),
        _Stat(
          icon: Icons.comment_outlined,
          value: widget.detail.comments,
        ),
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

