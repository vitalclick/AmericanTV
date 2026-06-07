import 'package:better_player_plus/better_player_plus.dart';
import 'package:flutter/material.dart';

import '../domain/video_source.dart';

class VideoPlayerScreen extends StatefulWidget {
  const VideoPlayerScreen({required this.source, required this.title, super.key});
  final VideoSource source;
  final String title;

  @override
  State<VideoPlayerScreen> createState() => _VideoPlayerScreenState();
}

class _VideoPlayerScreenState extends State<VideoPlayerScreen> {
  late final BetterPlayerController _controller;

  @override
  void initState() {
    super.initState();

    final url = widget.source.playbackUrl!;
    final isHls = widget.source.isHls;

    final dataSource = BetterPlayerDataSource(
      BetterPlayerDataSourceType.network,
      url,
      videoFormat: isHls ? BetterPlayerVideoFormat.hls : BetterPlayerVideoFormat.other,
      // For non-HLS, build a quality picker from the mp4_sources array so the
      // player can switch between resolutions client-side.
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
  }

  @override
  void dispose() {
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
