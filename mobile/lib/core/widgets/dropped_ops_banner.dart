import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../library/data/library_repository.dart';
import '../../video/data/comment_repository.dart';

/// Single global banner that aggregates dropped comments + dropped
/// watch-later ops. Replaces the two separate banners we had before so
/// users only ever see one red bar regardless of which queue dropped.
///
/// The detail sheet splits the entries into two sections so it's still
/// obvious which queue needs attention.
class DroppedOpsBanner extends ConsumerStatefulWidget {
  const DroppedOpsBanner({super.key});

  @override
  ConsumerState<DroppedOpsBanner> createState() => _DroppedOpsBannerState();
}

class _DroppedOpsBannerState extends ConsumerState<DroppedOpsBanner>
    with WidgetsBindingObserver {
  List<DroppedComment> _comments = const [];
  List<DroppedWatchLaterOp> _watchLater = const [];
  bool _loaded = false;
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
    // Read both caches in parallel — they're independent.
    final results = await Future.wait([
      ref.read(commentRepositoryProvider).droppedOps(),
      ref.read(libraryRepositoryProvider).droppedOps(),
    ]);
    if (!mounted) return;
    setState(() {
      _comments = results[0] as List<DroppedComment>;
      _watchLater = results[1] as List<DroppedWatchLaterOp>;
      _loaded = true;
    });
  }

  Future<void> _dismissAll() async {
    await Future.wait([
      ref.read(commentRepositoryProvider).acknowledgeDroppedOps(),
      ref.read(libraryRepositoryProvider).acknowledgeDroppedOps(),
    ]);
    if (mounted) {
      setState(() {
        _comments = const [];
        _watchLater = const [];
      });
    }
  }

  int get _total => _comments.length + _watchLater.length;

  String get _headline {
    final c = _comments.length;
    final w = _watchLater.length;
    if (c > 0 && w > 0) {
      return '${c + w} actions couldn\'t sync ($c comment${c == 1 ? '' : 's'}, $w watch-later) — tap to view.';
    }
    if (c > 0) {
      return '$c offline comment${c == 1 ? '' : 's'} couldn\'t be sent — tap to view.';
    }
    return '$w watch-later change${w == 1 ? '' : 's'} couldn\'t be saved — tap to view.';
  }

  Future<void> _showDetails() async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (_) => SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                "Couldn't sync",
                style: Theme.of(context).textTheme.titleLarge,
              ),
              if (_comments.isNotEmpty) ...[
                const SizedBox(height: 16),
                Text('Comments', style: Theme.of(context).textTheme.titleMedium),
                for (final op in _comments)
                  Card(
                    child: ListTile(
                      leading: const Icon(Icons.comment_outlined),
                      title: Text(op.body, maxLines: 3, overflow: TextOverflow.ellipsis),
                      subtitle: Text(
                        '${op.kind} • ${DateFormat.yMMMd().add_jm().format(op.droppedAt)}'
                        '${op.status != null ? ' • HTTP ${op.status}' : ''}',
                      ),
                    ),
                  ),
              ],
              if (_watchLater.isNotEmpty) ...[
                const SizedBox(height: 16),
                Text('Watch later', style: Theme.of(context).textTheme.titleMedium),
                for (final op in _watchLater)
                  Card(
                    child: ListTile(
                      leading: Icon(op.action == 'add' ? Icons.bookmark_add_outlined : Icons.bookmark_remove_outlined),
                      title: Text('${op.action == 'add' ? 'Save' : 'Remove'} video ${op.videoId}'),
                      subtitle: Text(
                        'Tried ${DateFormat.yMMMd().add_jm().format(op.droppedAt)}'
                        '${op.status != null ? ' • HTTP ${op.status}' : ''}',
                      ),
                    ),
                  ),
              ],
              const SizedBox(height: 12),
              Align(
                alignment: Alignment.centerRight,
                child: TextButton(
                  onPressed: () async {
                    await _dismissAll();
                    if (context.mounted) Navigator.of(context).pop();
                  },
                  child: const Text('Dismiss all'),
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
    if (!_loaded || _total == 0) return const SizedBox.shrink();

    return Material(
      color: Theme.of(context).colorScheme.errorContainer,
      child: InkWell(
        onTap: _showDetails,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
          child: Row(
            children: [
              const Icon(Icons.cloud_off_outlined),
              const SizedBox(width: 8),
              Expanded(child: Text(_headline)),
              TextButton(
                onPressed: _dismissAll,
                child: const Text('Dismiss'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
