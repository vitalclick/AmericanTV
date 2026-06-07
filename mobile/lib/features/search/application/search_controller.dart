import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../feed/data/feed_repository.dart';
import '../../feed/domain/video_summary.dart';

final searchControllerProvider =
    StateNotifierProvider<SearchController, SearchState>((ref) {
  return SearchController(ref.read(feedRepositoryProvider));
});

class SearchController extends StateNotifier<SearchState> {
  SearchController(this._repo) : super(const SearchState());

  final FeedRepository _repo;
  Timer? _debounce;
  int _queryGeneration = 0;

  /// Debounces input by 350ms and races the in-flight request: if a newer
  /// query arrives we discard the older one's result rather than letting it
  /// overwrite state out of order.
  void setQuery(String query) {
    _debounce?.cancel();
    state = state.copyWith(query: query);

    if (query.trim().isEmpty) {
      state = state.copyWith(videos: const [], isLoading: false, clearError: true);
      return;
    }

    _debounce = Timer(const Duration(milliseconds: 350), () {
      unawaited(_runSearch(query));
    });
  }

  Future<void> _runSearch(String query) async {
    final mine = ++_queryGeneration;
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final page = await _repo.searchVideos(query: query);
      if (mine != _queryGeneration) return; // stale response
      state = SearchState(
        query: query,
        videos: page.videos,
        page: page.page,
        lastPage: page.lastPage,
      );
    } on ApiException catch (e) {
      if (mine != _queryGeneration) return;
      state = state.copyWith(isLoading: false, errorMessage: e.message);
    }
  }

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }
}

class SearchState {
  const SearchState({
    this.query = '',
    this.videos = const [],
    this.page = 1,
    this.lastPage = 1,
    this.isLoading = false,
    this.errorMessage,
  });

  final String query;
  final List<VideoSummary> videos;
  final int page;
  final int lastPage;
  final bool isLoading;
  final String? errorMessage;

  SearchState copyWith({
    String? query,
    List<VideoSummary>? videos,
    int? page,
    int? lastPage,
    bool? isLoading,
    String? errorMessage,
    bool clearError = false,
  }) {
    return SearchState(
      query: query ?? this.query,
      videos: videos ?? this.videos,
      page: page ?? this.page,
      lastPage: lastPage ?? this.lastPage,
      isLoading: isLoading ?? this.isLoading,
      errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
    );
  }
}
