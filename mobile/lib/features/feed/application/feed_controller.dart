import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../data/feed_repository.dart';
import '../domain/video_summary.dart';

final feedControllerProvider =
    StateNotifierProvider<FeedController, FeedState>((ref) {
  return FeedController(ref.read(feedRepositoryProvider))..loadFirstPage();
});

class FeedController extends StateNotifier<FeedState> {
  FeedController(this._repo) : super(const FeedState());

  final FeedRepository _repo;

  /// Render whatever the cache had immediately, then refresh from the
  /// network. The cached render lets cold launches show content instantly
  /// even on slow / no connectivity.
  Future<void> loadFirstPage() async {
    final cached = await _repo.cachedFirstPage();
    if (cached != null && cached.videos.isNotEmpty) {
      state = FeedState(
        videos: cached.videos,
        page: cached.page,
        lastPage: cached.lastPage,
        isLoading: true, // still loading fresh data in the background.
      );
    } else {
      state = state.copyWith(isLoading: true, clearError: true);
    }

    try {
      final page = await _repo.feed();
      state = FeedState(
        videos: page.videos,
        page: page.page,
        lastPage: page.lastPage,
      );
    } on ApiException catch (e) {
      // If we already rendered from cache, surface the error as a chip in
      // the existing list rather than blanking the screen.
      state = state.copyWith(isLoading: false, errorMessage: e.message);
    }
  }

  Future<void> loadNextPage() async {
    if (state.isLoadingMore || !state.hasMore) return;
    state = state.copyWith(isLoadingMore: true);
    try {
      final next = await _repo.feed(page: state.page + 1);
      state = state.copyWith(
        videos: [...state.videos, ...next.videos],
        page: next.page,
        lastPage: next.lastPage,
        isLoadingMore: false,
      );
    } on ApiException catch (e) {
      state = state.copyWith(isLoadingMore: false, errorMessage: e.message);
    }
  }

  Future<void> refresh() async {
    await loadFirstPage();
  }
}

class FeedState {
  const FeedState({
    this.videos = const [],
    this.page = 1,
    this.lastPage = 1,
    this.isLoading = false,
    this.isLoadingMore = false,
    this.errorMessage,
  });

  final List<VideoSummary> videos;
  final int page;
  final int lastPage;
  final bool isLoading;
  final bool isLoadingMore;
  final String? errorMessage;

  bool get hasMore => page < lastPage;

  FeedState copyWith({
    List<VideoSummary>? videos,
    int? page,
    int? lastPage,
    bool? isLoading,
    bool? isLoadingMore,
    String? errorMessage,
    bool clearError = false,
  }) {
    return FeedState(
      videos: videos ?? this.videos,
      page: page ?? this.page,
      lastPage: lastPage ?? this.lastPage,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
    );
  }
}
