import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../video/data/comment_repository.dart';

/// One-time banner shown above the feed when offline comments couldn't be
/// replayed (e.g. the video got deleted while the user was offline). Tapping
/// "Dismiss" acknowledges the queue and the banner disappears for good.
class DroppedOpsBanner extends ConsumerStatefulWidget {
  const DroppedOpsBanner({super.key});

  @override
  ConsumerState<DroppedOpsBanner> createState() => _DroppedOpsBannerState();
}

class _DroppedOpsBannerState extends ConsumerState<DroppedOpsBanner> {
  List<DroppedComment>? _ops;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final ops = await ref.read(commentRepositoryProvider).droppedOps();
    if (mounted) setState(() => _ops = ops);
  }

  Future<void> _dismiss() async {
    await ref.read(commentRepositoryProvider).acknowledgeDroppedOps();
    if (mounted) setState(() => _ops = const []);
  }

  Future<void> _showDetails() async {
    final ops = _ops ?? const <DroppedComment>[];
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
                "Comments that couldn't be sent",
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 12),
              ...ops.map((op) => Card(
                    child: ListTile(
                      leading: const Icon(Icons.error_outline),
                      title: Text(op.body, maxLines: 3, overflow: TextOverflow.ellipsis),
                      subtitle: Text(
                        '${op.kind} • ${DateFormat.yMMMd().add_jm().format(op.droppedAt)}'
                        '${op.status != null ? ' • HTTP ${op.status}' : ''}',
                      ),
                    ),
                  )),
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
              const Icon(Icons.cloud_off_outlined),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  '${ops.length} offline comment${ops.length == 1 ? '' : 's'} couldn\'t be sent — tap to view.',
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
