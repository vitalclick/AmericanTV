import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../data/library_repository.dart';

/// One-time banner above the Library tabs when watch-later add/remove ops
/// were rejected by the server during replay (the target video was deleted
/// while the user was offline). Mirrors the dropped-comment banner in
/// shape and dismissal semantics.
class DroppedWatchLaterBanner extends ConsumerStatefulWidget {
  const DroppedWatchLaterBanner({super.key});

  @override
  ConsumerState<DroppedWatchLaterBanner> createState() => _DroppedWatchLaterBannerState();
}

class _DroppedWatchLaterBannerState extends ConsumerState<DroppedWatchLaterBanner>
    with WidgetsBindingObserver {
  List<DroppedWatchLaterOp>? _ops;
  DateTime _lastLoadedAt = DateTime.fromMicrosecondsSinceEpoch(0);
  static const _resumeDebounce = Duration(seconds: 1);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      if (DateTime.now().difference(_lastLoadedAt) < _resumeDebounce) return;
      _load();
    }
  }

  Future<void> _load() async {
    _lastLoadedAt = DateTime.now();
    final ops = await ref.read(libraryRepositoryProvider).droppedOps();
    if (mounted) setState(() => _ops = ops);
  }

  Future<void> _dismiss() async {
    await ref.read(libraryRepositoryProvider).acknowledgeDroppedOps();
    if (mounted) setState(() => _ops = const []);
  }

  Future<void> _showDetails() async {
    final ops = _ops ?? const <DroppedWatchLaterOp>[];
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (_) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                "Watch-later changes that couldn't sync",
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 12),
              ...ops.map(
                (op) => Card(
                  child: ListTile(
                    leading: Icon(op.action == 'add' ? Icons.bookmark_add_outlined : Icons.bookmark_remove_outlined),
                    title: Text('${op.action == 'add' ? 'Save' : 'Remove'} video ${op.videoId}'),
                    subtitle: Text(
                      'Tried ${DateFormat.yMMMd().add_jm().format(op.droppedAt)}'
                      '${op.status != null ? ' • HTTP ${op.status}' : ''}',
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              Align(
                alignment: Alignment.centerRight,
                child: TextButton(
                  onPressed: () async {
                    await _dismiss();
                    if (context.mounted) Navigator.of(context).pop();
                  },
                  child: const Text('Dismiss'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final ops = _ops;
    if (ops == null || ops.isEmpty) return const SizedBox.shrink();

    return Material(
      color: Theme.of(context).colorScheme.errorContainer,
      child: InkWell(
        onTap: _showDetails,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
          child: Row(
            children: [
              const Icon(Icons.bookmark_outline),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  '${ops.length} watch-later change${ops.length == 1 ? '' : 's'} couldn\'t be saved — tap to view.',
                ),
              ),
              TextButton(
                onPressed: _dismiss,
                child: const Text('Dismiss'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
