import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';

import 'package:americantv/features/feed/application/feed_controller.dart';
import 'package:americantv/features/feed/domain/video_summary.dart';
import 'package:americantv/features/feed/presentation/feed_screen.dart';

class _SeededFeedController extends FeedController {
  _SeededFeedController(List<VideoSummary> videos) : super(_StubRepo()) {
    state = FeedState(videos: videos, page: 1, lastPage: 1);
  }

  @override
  Future<void> loadFirstPage() async {}

  @override
  Future<void> loadNextPage() async {}
}

class _StubRepo implements dynamic {
  @override
  dynamic noSuchMethod(Invocation invocation) => null;
}

void main() {
  final sample = [
    const VideoSummary(
      id: 1,
      slug: 'first',
      title: 'A first video',
      views: 1500,
      isPaid: false,
      channel: Channel(id: 9, name: 'Channel Nine'),
    ),
    const VideoSummary(
      id: 2,
      slug: 'paid-one',
      title: 'A paid one',
      views: 42,
      isPaid: true,
      price: 4.99,
    ),
  ];

  testWidgets('feed renders one tile per video with title + channel + views', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          feedControllerProvider.overrideWith((_) => _SeededFeedController(sample)),
        ],
        child: MaterialApp.router(
          routerConfig: GoRouter(
            routes: [GoRoute(path: '/', builder: (_, __) => const FeedScreen())],
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('A first video'), findsOneWidget);
    expect(find.text('A paid one'), findsOneWidget);
    expect(find.text('Channel Nine'), findsOneWidget);
    expect(find.textContaining('views'), findsAtLeastNWidgets(2));
  });

  testWidgets('paid videos show a price badge', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          feedControllerProvider.overrideWith((_) => _SeededFeedController(sample)),
        ],
        child: MaterialApp.router(
          routerConfig: GoRouter(
            routes: [GoRoute(path: '/', builder: (_, __) => const FeedScreen())],
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('\$4.99'), findsOneWidget);
  });
}
