import 'dart:async';

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
    final repo = ref.read(commentRepositoryProvider);
    // Read-through: render the cached list immediately if any, then race
    // the live fetch. Failures keep the cached payload on screen via the
    // try/catch — same pattern the feed + video detail use.
    final cached = await repo.cachedFirstPage(videoId);
    if (cached != null && cached.comments.isNotEmpty) {
      // Kick off the live fetch in the background; let the controller
      // overwrite state via the awaited path below if/when it lands.
      unawaited(_refreshInBackground(videoId));
      return cached.comments;
    }
    final page = await repo.list(videoId);
    return page.comments;
  }

  Future<void> _refreshInBackground(int videoId) async {
    try {
      final page = await ref.read(commentRepositoryProvider).list(videoId);
      state = AsyncData(page.comments);
    } catch (_) {
      // Leave the cached state on screen.
    }
  }

  Future<void> post(String body) async {
    final created = await ref.read(commentRepositoryProvider).post(arg, body);
    state = AsyncData([created, ...(state.valueOrNull ?? const [])]);
  }

  Future<void> reply(int parentId, String body) async {
    final created = await ref.read(commentRepositoryProvider).reply(parentId, body);
    final current = state.valueOrNull ?? const <Comment>[];
    state = AsyncData([
      for (final c in current)
        c.id == parentId
            ? c.copyWith(
                replyCount: c.replyCount + 1,
                replies: [...c.replies, created],
              )
            : c,
    ]);
  }

  Future<void> react(int commentId, int isLike) async {
    // Optimistic: apply the toggle to local state immediately, then send.
    // Same toggle / swap / remove semantics as the video reaction bar.
    final current = state.valueOrNull ?? const <Comment>[];
    final direction = isLike == 1 ? 1 : -1;
    final after = _applyOptimisticToggle(current, commentId, direction);
    if (after != null) state = AsyncData(after);

    try {
      final res = await ref
          .read(commentRepositoryProvider)
          .reactToComment(commentId: commentId, isLike: isLike);
      state = AsyncData(_apply(state.valueOrNull ?? const [], commentId, res));
    } catch (_) {
      // Roll back to whatever state had before our optimistic mutation.
      if (after != null) state = AsyncData(current);
    }
  }

  /// Walks the list recursively; returns null if the comment is missing
  /// (in which case we leave state untouched and let the server response
  /// repair it).
  List<Comment>? _applyOptimisticToggle(List<Comment> list, int id, int direction) {
    var found = false;
    final next = list.map((c) {
      if (c.id == id) {
        found = true;
        return _toggleOne(c, direction);
      }
      if (c.replies.isNotEmpty) {
        final repliesAfter = _applyOptimisticToggle(c.replies, id, direction);
        if (repliesAfter != null) {
          found = true;
          return c.copyWith(replies: repliesAfter);
        }
      }
      return c;
    }).toList();
    return found ? next : null;
  }

  Comment _toggleOne(Comment c, int direction) {
    if (c.userReaction == direction) {
      return c.copyWith(
        userReaction: 0,
        likes: direction == 1 ? c.likes - 1 : c.likes,
      );
    }
    if (c.userReaction == -direction) {
      return c.copyWith(
        userReaction: direction,
        likes: direction == 1 ? c.likes + 1 : c.likes - 1,
      );
    }
    return c.copyWith(
      userReaction: direction,
      likes: direction == 1 ? c.likes + 1 : c.likes,
    );
  }

  // Recurses into replies so a reaction on a reply also updates state.
  List<Comment> _apply(List<Comment> list, int id, CommentReactionState res) {
    return [
      for (final c in list)
        if (c.id == id)
          c.copyWith(likes: res.likes, userReaction: res.userReaction)
        else if (c.replies.isNotEmpty)
          c.copyWith(replies: _apply(c.replies, id, res))
        else
          c,
    ];
  }
}

class CommentsSection extends ConsumerWidget {
  const CommentsSection({required this.videoId, super.key});
  final int videoId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(_commentsProvider(videoId));

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
          child: Row(
            children: [
              Text('Comments', style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(width: 6),
              if (async.hasValue)
                Text('(${async.value!.length})',
                    style: Theme.of(context).textTheme.bodySmall),
            ],
          ),
        ),
        const SizedBox(height: 8),
        _Composer(
          onSubmit: (body) =>
              ref.read(_commentsProvider(videoId).notifier).post(body),
        ),
        const Divider(height: 24),
        async.when(
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
            final notifier = ref.read(_commentsProvider(videoId).notifier);
            return Column(
              children: [
                for (final c in comments)
                  _CommentTile(comment: c, notifier: notifier),
              ],
            );
          },
        ),
      ],
    );
  }
}

class _Composer extends StatefulWidget {
  const _Composer({required this.onSubmit, this.hint = 'Add a comment…', this.autofocus = false});
  final Future<void> Function(String body) onSubmit;
  final String hint;
  final bool autofocus;

  @override
  State<_Composer> createState() => _ComposerState();
}

class _ComposerState extends State<_Composer> {
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
      await widget.onSubmit(body);
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
              autofocus: widget.autofocus,
              minLines: 1,
              maxLines: 4,
              textInputAction: TextInputAction.send,
              onSubmitted: (_) => _submit(),
              decoration: InputDecoration(hintText: widget.hint),
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

class _CommentTile extends StatefulWidget {
  const _CommentTile({
    required this.comment,
    required this.notifier,
    this.indent = 0,
  });

  final Comment comment;
  final _CommentsNotifier notifier;
  final double indent;

  @override
  State<_CommentTile> createState() => _CommentTileState();
}

class _CommentTileState extends State<_CommentTile> {
  bool _showReplyBox = false;
  bool _expandReplies = false;

  @override
  Widget build(BuildContext context) {
    final c = widget.comment;
    final author = c.author?.name ?? 'Anonymous';

    return Padding(
      padding: EdgeInsets.only(left: 16 + widget.indent, right: 16, top: 8, bottom: 4),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(author, style: Theme.of(context).textTheme.bodyMedium),
              const SizedBox(width: 6),
              Text(_relativeTime(c.createdAt),
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Theme.of(context).disabledColor,
                      )),
            ],
          ),
          const SizedBox(height: 4),
          Text(c.body),
          const SizedBox(height: 4),
          Row(
            children: [
              IconButton(
                visualDensity: VisualDensity.compact,
                icon: Icon(
                  c.userReaction == 1 ? Icons.thumb_up : Icons.thumb_up_outlined,
                  size: 16,
                  color: c.userReaction == 1
                      ? Theme.of(context).colorScheme.primary
                      : null,
                ),
                onPressed: () => widget.notifier.react(c.id, 1),
              ),
              Text('${c.likes}', style: Theme.of(context).textTheme.bodySmall),
              const SizedBox(width: 8),
              IconButton(
                visualDensity: VisualDensity.compact,
                icon: Icon(
                  c.userReaction == -1 ? Icons.thumb_down : Icons.thumb_down_outlined,
                  size: 16,
                  color: c.userReaction == -1
                      ? Theme.of(context).colorScheme.primary
                      : null,
                ),
                onPressed: () => widget.notifier.react(c.id, 0),
              ),
              const SizedBox(width: 8),
              if (widget.indent == 0)
                TextButton(
                  onPressed: () => setState(() => _showReplyBox = !_showReplyBox),
                  child: Text(_showReplyBox ? 'Cancel' : 'Reply'),
                ),
            ],
          ),
          if (_showReplyBox)
            _Composer(
              hint: 'Reply to $author…',
              autofocus: true,
              onSubmit: (body) async {
                // Reply target is always the root comment so replies stay
                // one level deep, matching how the existing web flow nests.
                final rootId = widget.indent == 0 ? c.id : (c.parentId ?? c.id);
                await widget.notifier.reply(rootId, body);
                setState(() {
                  _showReplyBox = false;
                  _expandReplies = true;
                });
              },
            ),
          if (c.replies.isNotEmpty) ...[
            TextButton(
              onPressed: () => setState(() => _expandReplies = !_expandReplies),
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 4),
                visualDensity: VisualDensity.compact,
              ),
              child: Text(_expandReplies
                  ? 'Hide ${c.replies.length} replies'
                  : 'View ${c.replies.length} replies'),
            ),
            if (_expandReplies)
              for (final r in c.replies)
                _CommentTile(comment: r, notifier: widget.notifier, indent: 24),
          ],
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
