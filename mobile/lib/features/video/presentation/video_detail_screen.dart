import 'dart:async';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../core/services/analytics_service.dart';
import '../../iap/presentation/plan_paywall_screen.dart';
import '../data/reactions_repository.dart';
import '../data/video_repository.dart';
import '../domain/video_detail.dart';
import '../domain/video_source.dart';
import 'comments_section.dart';
import 'video_player_screen.dart';

/// Read-through over the cache. If we've ever fetched this slug before we
/// emit the cached payload immediately, then race the network. The new
/// payload supersedes the cached one when it lands; failures keep the
/// cached version on screen.
final _videoDetailProvider =
    StreamProvider.autoDispose.family<VideoDetail, String>((ref, slug) async* {
  final repo = ref.read(videoRepositoryProvider);
  final cached = await repo.cachedShow(slug);
  if (cached != null) yield cached;
  yield await repo.show(slug);
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
                  child: _PaywallBadge(
                    price: summary.price ?? 0,
                    onTap: () => _openPaywall(context, ref),
                  ),
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
              if (detail.summary.channel?.id != null) ...[
                const SizedBox(height: 12),
                _SubscribeButton(channelId: detail.summary.channel!.id),
              ],
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
      ref.read(analyticsServiceProvider).track(
        'video_play_started',
        videoId: detail.summary.id,
        payload: {'is_hls': source.isHls},
      );
      await navigator.push(
        MaterialPageRoute<void>(
          builder: (_) => VideoPlayerScreen(
            source: source,
            title: detail.summary.title,
            videoId: detail.summary.id,
          ),
        ),
      );
      ref.read(analyticsServiceProvider).track(
        'video_play_completed',
        videoId: detail.summary.id,
      );
    } on ApiException catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  /// Surfaces the cheapest plan that contains this video and is registered
  /// on the relevant store. If none qualify (e.g. video is sold a la carte
  /// only, or no IAP product is configured yet), we send the user to the
  /// existing paid-video flow on web.
  Future<void> _openPaywall(BuildContext context, WidgetRef ref) async {
    final candidates = detail.accessPlans.where((p) => p.isMobileAvailable).toList()
      ..sort((a, b) => (a.mobilePriceUsd ?? a.priceUsd)
          .compareTo(b.mobilePriceUsd ?? b.priceUsd));

    ref.read(analyticsServiceProvider).track(
      'paywall_impression',
      videoId: detail.summary.id,
      payload: {'plans_available': candidates.length},
    );

    if (candidates.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'This video isn\'t available for purchase in the app yet — '
            'visit americantv.vip to unlock it.',
          ),
        ),
      );
      return;
    }

    if (candidates.length == 1) {
      await _pushPaywall(context, ref, candidates.first);
      return;
    }

    final picked = await showModalBottomSheet<AccessPlan>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _PlanPicker(plans: candidates),
    );
    if (picked != null && context.mounted) {
      await _pushPaywall(context, ref, picked);
    }
  }

  Future<void> _pushPaywall(BuildContext context, WidgetRef ref, AccessPlan plan) async {
    final didSubscribe = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => PlanPaywallScreen(slug: plan.slug)),
    );
    if (didSubscribe == true && context.mounted) {
      // Re-fetch the detail so userHasAccess flips and the play button
      // replaces the paywall badge.
      ref.invalidate(_videoDetailProvider(detail.summary.slug));
    }
  }
}

class _PlanPicker extends StatelessWidget {
  const _PlanPicker({required this.plans});
  final List<AccessPlan> plans;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('Choose a plan', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 12),
            for (final p in plans)
              Card(
                child: ListTile(
                  onTap: () => Navigator.of(context).pop(p),
                  title: Text(p.name),
                  subtitle: Text(
                    '\$${(p.mobilePriceUsd ?? p.priceUsd).toStringAsFixed(2)} / month',
                  ),
                  trailing: const Icon(Icons.chevron_right),
                ),
              ),
          ],
        ),
      ),
    );
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
/// same direction twice clears the reaction. Optimistic — UI flips first,
/// server confirms after; we roll back to the previous state on error.
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

    // Mutate locally first using the toggle/swap/remove semantics we share
    // with the server, so the tap is instant.
    final previousReaction = _userReaction;
    final previousLikes = _likes;
    final previousDislikes = _dislikes;

    setState(() {
      _busy = true;
      _applyLocalToggle(direction);
    });

    try {
      final state = await ref
          .read(reactionsRepositoryProvider)
          .reactToVideo(videoId: widget.detail.summary.id, isLike: isLike);
      // Authoritative server counts replace our optimistic guess.
      setState(() {
        _userReaction = state.userReaction;
        _likes = state.likes;
        _dislikes = state.dislikes;
      });
    } on ApiException catch (e) {
      // Roll back to the pre-tap state.
      setState(() {
        _userReaction = previousReaction;
        _likes = previousLikes;
        _dislikes = previousDislikes;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _applyLocalToggle(int direction) {
    if (_userReaction == direction) {
      // Same direction twice -> clear.
      _userReaction = 0;
      if (direction == 1) _likes -= 1; else _dislikes -= 1;
    } else if (_userReaction == -direction) {
      // Other direction -> swap.
      _userReaction = direction;
      if (direction == 1) { _likes += 1; _dislikes -= 1; }
      else                { _dislikes += 1; _likes -= 1; }
    } else {
      // None -> add.
      _userReaction = direction;
      if (direction == 1) _likes += 1; else _dislikes += 1;
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
  const _PaywallBadge({required this.price, required this.onTap});
  final double price;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Container(
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
        ),
      ),
    );
  }
}

class _SubscribeButton extends ConsumerStatefulWidget {
  const _SubscribeButton({required this.channelId});
  final int channelId;

  @override
  ConsumerState<_SubscribeButton> createState() => _SubscribeButtonState();
}

class _SubscribeButtonState extends ConsumerState<_SubscribeButton> {
  bool _subscribed = false; // Server hasn't told us yet — assume not.
  bool _busy = false;

  Future<void> _toggle() async {
    if (_busy) return;
    final previous = _subscribed;
    setState(() {
      _busy = true;
      _subscribed = !previous;
    });

    try {
      final actual = await ref
          .read(reactionsRepositoryProvider)
          .subscribeChannel(widget.channelId);
      // Authoritative server value replaces our optimistic guess.
      setState(() => _subscribed = actual);
    } on ApiException catch (e) {
      setState(() => _subscribed = previous);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.centerLeft,
      child: FilledButton.tonalIcon(
        onPressed: _busy ? null : _toggle,
        icon: Icon(_subscribed ? Icons.notifications_active : Icons.notifications_none),
        label: Text(_subscribed ? 'Subscribed' : 'Subscribe'),
      ),
    );
  }
}
