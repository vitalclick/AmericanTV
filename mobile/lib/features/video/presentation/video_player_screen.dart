import 'dart:async';

import 'package:better_player_plus/better_player_plus.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/services/analytics_service.dart';
import '../domain/video_source.dart';

class VideoPlayerScreen extends ConsumerStatefulWidget {
  const VideoPlayerScreen({
    required this.source,
    required this.title,
    required this.videoId,
    super.key,
  });
  final VideoSource source;
  final String title;
  final int videoId;

  @override
  ConsumerState<VideoPlayerScreen> createState() => _VideoPlayerScreenState();
}

class _VideoPlayerScreenState extends ConsumerState<VideoPlayerScreen> {
  late final BetterPlayerController _controller;

  // Cumulative seconds actually watched — only counts wall-clock time while
  // the player is playing, so scrubbing and pauses don't inflate it.
  Duration _watched = Duration.zero;
  DateTime? _lastPlayingAt;
  Timer? _progressTimer;
  double? _lastReportedSeconds;
  bool _completed = false;

  @override
  void initState() {
    super.initState();

    final url = widget.source.playbackUrl!;
    final isHls = widget.source.isHls;

    final dataSource = BetterPlayerDataSource(
      BetterPlayerDataSourceType.network,
      url,
      videoFormat: isHls ? BetterPlayerVideoFormat.hls : BetterPlayerVideoFormat.other,
      resolutions: isHls
          ? null
          : {
              for (final s in widget.source.mp4Sources)
                (s.quality ?? 'auto'): s.url,
            },
      subtitles: const [],
    );

    _controller = BetterPlayerController(
      BetterPlayerConfiguration(
        autoPlay: true,
        aspectRatio: 16 / 9,
        fit: BoxFit.contain,
        allowedScreenSleep: false,
        placeholder: widget.source.poster != null
            ? Image.network(widget.source.poster!, fit: BoxFit.cover)
            : null,
      ),
      betterPlayerDataSource: dataSource,
    );

    _controller.addEventsListener(_onPlayerEvent);
    _progressTimer = Timer.periodic(
      const Duration(seconds: 10),
      (_) => _emitProgress(),
    );
  }

  void _onPlayerEvent(BetterPlayerEvent event) {
    switch (event.betterPlayerEventType) {
      case BetterPlayerEventType.play:
        _lastPlayingAt = DateTime.now();
        break;
      case BetterPlayerEventType.pause:
      case BetterPlayerEventType.seekTo:
        _accumulateAndReset();
        break;
      case BetterPlayerEventType.finished:
        _accumulateAndReset();
        _markCompleted();
        break;
      default:
        break;
    }
  }

  void _accumulateAndReset() {
    final last = _lastPlayingAt;
    if (last != null) {
      _watched += DateTime.now().difference(last);
    }
    _lastPlayingAt = null;
  }

  void _emitProgress() {
    final live = _watched + (
      _lastPlayingAt == null ? Duration.zero : DateTime.now().difference(_lastPlayingAt!)
    );
    final seconds = live.inMilliseconds / 1000.0;
    if (seconds <= 0) return;

    // Skip emits where progress hasn't moved much — e.g. the screen is open
    // but the user paused.
    if (_lastReportedSeconds != null && (seconds - _lastReportedSeconds!) < 1) {
      return;
    }
    _lastReportedSeconds = seconds;

    ref.read(analyticsServiceProvider).track(
      'video_progress',
      videoId: widget.videoId,
      payload: {'watched_seconds': seconds.round()},
    );
  }

  void _markCompleted() {
    if (_completed) return;
    _completed = true;
    final seconds = (_watched.inMilliseconds / 1000.0).round();
    ref.read(analyticsServiceProvider).track(
      'video_finished',
      videoId: widget.videoId,
      payload: {'watched_seconds': seconds},
    );
  }

  @override
  void dispose() {
    _accumulateAndReset();
    _progressTimer?.cancel();
    _controller.removeEventsListener(_onPlayerEvent);
    // Final session-end event so we always have a watched_seconds row even
    // for users who back out before the video finishes.
    if (!_completed) {
      final seconds = (_watched.inMilliseconds / 1000.0).round();
      if (seconds > 0) {
        ref.read(analyticsServiceProvider).track(
          'video_play_session_ended',
          videoId: widget.videoId,
          payload: {'watched_seconds': seconds, 'completed': false},
        );
      }
    }
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text(widget.title, maxLines: 1, overflow: TextOverflow.ellipsis),
      ),
      body: SafeArea(
        child: AspectRatio(
          aspectRatio: 16 / 9,
          child: BetterPlayer(controller: _controller),
        ),
      ),
    );
  }
}
