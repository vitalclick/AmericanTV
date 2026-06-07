import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../api/api_exception.dart';
import '../data/comment_repository.dart';
import '../domain/comment.dart';

final _commentsProvider =
    AsyncNotifierProvider.autoDispose.family<_CommentsNotifier, List<Comment>, int>(
  _CommentsNotifier.new,
);

class _CommentsNotifier extends AutoDisposeFamilyAsyncNotifier<List<Comment>, int> {
  @override
  Future<List<Comment>> build(int videoId) async {
    final page = await ref.read(commentRepositoryProvider).list(videoId);
    return page.comments;
  }

  Future<void> post(String body) async {
    final videoId = arg;
    final created = await ref.read(commentRepositoryProvider).post(videoId, body);
    final current = state.valueOrNull ?? const <Comment>[];
    state = AsyncData([created, ...current]);
  }
}

class CommentsSection extends ConsumerWidget {
  const CommentsSection({required this.videoId, super.key});
  final int videoId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final commentsAsync = ref.watch(_commentsProvider(videoId));

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
          child: Row(
            children: [
              Text('Comments', style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(width: 6),
              if (commentsAsync.hasValue)
                Text('(${commentsAsync.value!.length})',
                    style: Theme.of(context).textTheme.bodySmall),
            ],
          ),
        ),
        const SizedBox(height: 8),
        _CommentComposer(videoId: videoId),
        const Divider(height: 24),
        commentsAsync.when(
          loading: () => const Padding(
            padding: EdgeInsets.symmetric(vertical: 24),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (e, _) => Padding(
            padding: const EdgeInsets.all(16),
            child: Text(
              e is ApiException ? e.message : 'Could not load comments.',
              style: const TextStyle(color: Colors.redAccent),
            ),
          ),
          data: (comments) {
            if (comments.isEmpty) {
              return const Padding(
                padding: EdgeInsets.symmetric(vertical: 32),
                child: Center(child: Text('Be the first to comment.')),
              );
            }
            return Column(
              children: [
                for (final c in comments) _CommentTile(comment: c),
              ],
            );
          },
        ),
      ],
    );
  }
}

class _CommentComposer extends ConsumerStatefulWidget {
  const _CommentComposer({required this.videoId});
  final int videoId;

  @override
  ConsumerState<_CommentComposer> createState() => _CommentComposerState();
}

class _CommentComposerState extends ConsumerState<_CommentComposer> {
  final _controller = TextEditingController();
  bool _busy = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final body = _controller.text.trim();
    if (body.isEmpty || _busy) return;

    setState(() => _busy = true);
    try {
      await ref
          .read(_commentsProvider(widget.videoId).notifier)
          .post(body);
      _controller.clear();
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
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Expanded(
            child: TextField(
              controller: _controller,
              minLines: 1,
              maxLines: 4,
              textInputAction: TextInputAction.send,
              onSubmitted: (_) => _submit(),
              decoration: const InputDecoration(
                hintText: 'Add a comment…',
              ),
            ),
          ),
          const SizedBox(width: 8),
          IconButton.filled(
            onPressed: _busy ? null : _submit,
            icon: _busy
                ? const SizedBox.square(
                    dimension: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.send),
          ),
        ],
      ),
    );
  }
}

class _CommentTile extends StatelessWidget {
  const _CommentTile({required this.comment});
  final Comment comment;

  @override
  Widget build(BuildContext context) {
    final author = comment.author?.name ?? 'Anonymous';
    final ago = _relativeTime(comment.createdAt);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(author, style: Theme.of(context).textTheme.bodyMedium),
              const SizedBox(width: 6),
              Text(ago,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Theme.of(context).disabledColor,
                      )),
            ],
          ),
          const SizedBox(height: 4),
          Text(comment.body),
        ],
      ),
    );
  }

  String _relativeTime(DateTime when) {
    final diff = DateTime.now().difference(when);
    if (diff.inMinutes < 1) return 'just now';
    if (diff.inHours < 1) return '${diff.inMinutes}m ago';
    if (diff.inDays < 1) return '${diff.inHours}h ago';
    if (diff.inDays < 7) return '${diff.inDays}d ago';
    return DateFormat.yMMMd().format(when);
  }
}
