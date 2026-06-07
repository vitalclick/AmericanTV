import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../feed/domain/video_summary.dart';
import '../application/search_controller.dart';

class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key});

  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  final _focusNode = FocusNode();

  @override
  void dispose() {
    _focusNode.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(searchControllerProvider);

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
          child: TextField(
            focusNode: _focusNode,
            autocorrect: false,
            textInputAction: TextInputAction.search,
            onChanged: (v) =>
                ref.read(searchControllerProvider.notifier).setQuery(v),
            decoration: const InputDecoration(
              hintText: 'Search videos…',
              prefixIcon: Icon(Icons.search),
            ),
          ),
        ),
        Expanded(child: _Results(state: state)),
      ],
    );
  }
}

class _Results extends StatelessWidget {
  const _Results({required this.state});
  final SearchState state;

  @override
  Widget build(BuildContext context) {
    if (state.query.trim().isEmpty) {
      return const _SearchEmpty(
        icon: Icons.search,
        text: 'What do you want to watch?',
      );
    }
    if (state.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (state.errorMessage != null) {
      return Center(child: Text(state.errorMessage!, textAlign: TextAlign.center));
    }
    if (state.videos.isEmpty) {
      return _SearchEmpty(
        icon: Icons.sentiment_dissatisfied,
        text: 'No results for "${state.query}".',
      );
    }
    return ListView.separated(
      itemCount: state.videos.length,
      separatorBuilder: (_, __) => const Divider(height: 1),
      itemBuilder: (_, i) => _SearchResultTile(video: state.videos[i]),
    );
  }
}

class _SearchResultTile extends StatelessWidget {
  const _SearchResultTile({required this.video});
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
        [video.channel?.name, '${video.views} views']
            .where((s) => s != null && s.isNotEmpty)
            .join(' • '),
        style: Theme.of(context).textTheme.bodySmall,
      ),
    );
  }
}

class _SearchEmpty extends StatelessWidget {
  const _SearchEmpty({required this.icon, required this.text});
  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 56),
            const SizedBox(height: 12),
            Text(text, textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}
