import 'dart:io';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../data/upload_repository.dart';

class UploadVideoScreen extends ConsumerStatefulWidget {
  const UploadVideoScreen({super.key});

  @override
  ConsumerState<UploadVideoScreen> createState() => _UploadVideoScreenState();
}

class _UploadVideoScreenState extends ConsumerState<UploadVideoScreen> {
  File? _file;
  final _title = TextEditingController();
  bool _busy = false;
  double _progress = 0;
  String? _error;
  int? _resultVideoId;

  @override
  void dispose() {
    _title.dispose();
    super.dispose();
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.video,
      allowMultiple: false,
    );
    if (result == null || result.files.single.path == null) return;
    setState(() {
      _file = File(result.files.single.path!);
      _error = null;
      _resultVideoId = null;
    });
  }

  Future<void> _upload() async {
    if (_file == null) return;
    setState(() {
      _busy = true;
      _progress = 0;
      _error = null;
    });

    try {
      final id = await ref.read(uploadRepositoryProvider).uploadVideo(
            file: _file!,
            title: _title.text.trim().isEmpty ? null : _title.text.trim(),
            onProgress: (p) {
              if (mounted) setState(() => _progress = p);
            },
          );
      if (mounted) {
        setState(() => _resultVideoId = id);
      }
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Upload video')),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (_file == null)
                _PickerArea(onTap: _pickFile)
              else
                _SelectedFile(
                  file: _file!,
                  onClear: _busy ? null : () => setState(() => _file = null),
                ),
              const SizedBox(height: 16),
              TextField(
                controller: _title,
                enabled: !_busy,
                decoration: const InputDecoration(
                  labelText: 'Title (optional)',
                  helperText: 'You can edit details + visibility on the web.',
                ),
                textCapitalization: TextCapitalization.sentences,
              ),
              const SizedBox(height: 24),
              if (_busy) ...[
                LinearProgressIndicator(value: _progress > 0 ? _progress : null),
                const SizedBox(height: 8),
                Text(
                  _progress > 0
                      ? '${(_progress * 100).toStringAsFixed(0)}% uploaded'
                      : 'Merging on server…',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ],
              if (_error != null)
                Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: Text(
                    _error!,
                    style: TextStyle(color: Theme.of(context).colorScheme.error),
                  ),
                ),
              if (_resultVideoId != null)
                Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: Text(
                    'Upload complete. Visit the web dashboard to add a description, set the category, and publish.',
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.primary,
                    ),
                  ),
                ),
              const Spacer(),
              FilledButton(
                onPressed: (_file == null || _busy) ? null : _upload,
                child: const Text('Upload'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _PickerArea extends StatelessWidget {
  const _PickerArea({required this.onTap});
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        height: 180,
        decoration: BoxDecoration(
          border: Border.all(
            color: Theme.of(context).colorScheme.outlineVariant,
            style: BorderStyle.solid,
          ),
          borderRadius: BorderRadius.circular(12),
        ),
        child: const Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.video_file_outlined, size: 48),
              SizedBox(height: 8),
              Text('Pick a video'),
            ],
          ),
        ),
      ),
    );
  }
}

class _SelectedFile extends StatelessWidget {
  const _SelectedFile({required this.file, required this.onClear});
  final File file;
  final VoidCallback? onClear;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: const Icon(Icons.movie_outlined, size: 36),
      title: Text(
        file.uri.pathSegments.last,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
      subtitle: FutureBuilder<int>(
        future: file.length(),
        builder: (_, snap) {
          final bytes = snap.data ?? 0;
          final mb = bytes / (1024 * 1024);
          return Text('${mb.toStringAsFixed(1)} MiB');
        },
      ),
      trailing: onClear == null
          ? null
          : IconButton(
              icon: const Icon(Icons.close),
              onPressed: onClear,
            ),
    );
  }
}
