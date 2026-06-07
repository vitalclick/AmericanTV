import 'dart:io';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../data/publish_repository.dart';

final _categoriesProvider =
    FutureProvider.autoDispose<List<PublishCategory>>((ref) {
  return ref.read(publishRepositoryProvider).categories();
});

/// Post-upload publish form. The upload step has already minted the Video
/// row; this fills in category + visibility + tags and sets status =
/// PUBLISHED. Mirrors VideoManager::visibilitySubmit but mobile-native.
class PublishVideoScreen extends ConsumerStatefulWidget {
  const PublishVideoScreen({required this.videoId, super.key});
  final int videoId;

  @override
  ConsumerState<PublishVideoScreen> createState() => _PublishVideoScreenState();
}

class _PublishVideoScreenState extends ConsumerState<PublishVideoScreen> {
  int? _categoryId;
  bool _isPublic = true;
  final _tagInput = TextEditingController();
  final List<String> _tags = [];
  bool _busy = false;
  String? _error;
  File? _thumbnailFile;
  String? _uploadedThumbnailUrl;

  @override
  void dispose() {
    _tagInput.dispose();
    super.dispose();
  }

  void _addTag() {
    final t = _tagInput.text.trim();
    if (t.isEmpty || _tags.contains(t) || t.length > 32) {
      _tagInput.clear();
      return;
    }
    setState(() {
      _tags.add(t);
      _tagInput.clear();
    });
  }

  Future<void> _pickThumbnail() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['jpg', 'jpeg', 'png'],
    );
    if (result == null || result.files.single.path == null) return;
    setState(() {
      _thumbnailFile = File(result.files.single.path!);
      _uploadedThumbnailUrl = null;
    });
  }

  Future<void> _submit() async {
    if (_categoryId == null) {
      setState(() => _error = 'Pick a category first.');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      // Upload the custom thumbnail first if any — separate endpoint, so the
      // user can iterate on the thumbnail without re-publishing.
      if (_thumbnailFile != null && _uploadedThumbnailUrl == null) {
        _uploadedThumbnailUrl = await ref
            .read(publishRepositoryProvider)
            .uploadThumbnail(videoId: widget.videoId, localPath: _thumbnailFile!.path);
      }

      await ref.read(publishRepositoryProvider).publish(
            videoId: widget.videoId,
            categoryId: _categoryId!,
            isPublic: _isPublic,
            tags: _tags,
          );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Video published.')),
        );
        Navigator.of(context).pop(true);
      }
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final categoriesAsync = ref.watch(_categoriesProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Publish')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text('Thumbnail', style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 8),
              AspectRatio(
                aspectRatio: 16 / 9,
                child: InkWell(
                  onTap: _busy ? null : _pickThumbnail,
                  borderRadius: BorderRadius.circular(8),
                  child: Container(
                    decoration: BoxDecoration(
                      border: Border.all(
                        color: Theme.of(context).colorScheme.outlineVariant,
                      ),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    clipBehavior: Clip.antiAlias,
                    child: _thumbnailFile != null
                        ? Image.file(_thumbnailFile!, fit: BoxFit.cover)
                        : _uploadedThumbnailUrl != null
                            ? CachedNetworkImage(
                                imageUrl: _uploadedThumbnailUrl!,
                                fit: BoxFit.cover,
                              )
                            : Center(
                                child: Column(
                                  mainAxisSize: MainAxisSize.min,
                                  children: const [
                                    Icon(Icons.image_outlined, size: 32),
                                    SizedBox(height: 4),
                                    Text('Pick a custom thumbnail (optional)'),
                                  ],
                                ),
                              ),
                  ),
                ),
              ),
              const SizedBox(height: 24),
              Text('Category', style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 8),
              categoriesAsync.when(
                loading: () => const LinearProgressIndicator(),
                error: (e, _) => Text(
                  e is ApiException ? e.message : 'Could not load categories.',
                ),
                data: (cats) => DropdownButtonFormField<int>(
                  value: _categoryId,
                  hint: const Text('Choose a category'),
                  items: cats
                      .map((c) => DropdownMenuItem(value: c.id, child: Text(c.name)))
                      .toList(),
                  onChanged: (v) => setState(() => _categoryId = v),
                ),
              ),
              const SizedBox(height: 24),
              Text('Visibility', style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 4),
              SegmentedButton<bool>(
                segments: const [
                  ButtonSegment(value: true, label: Text('Public'), icon: Icon(Icons.public)),
                  ButtonSegment(value: false, label: Text('Private'), icon: Icon(Icons.lock_outline)),
                ],
                selected: {_isPublic},
                onSelectionChanged: (s) => setState(() => _isPublic = s.first),
              ),
              const SizedBox(height: 24),
              Text('Tags', style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 4),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _tagInput,
                      textInputAction: TextInputAction.done,
                      onSubmitted: (_) => _addTag(),
                      decoration: const InputDecoration(
                        hintText: 'Add a tag and press enter',
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    onPressed: _addTag,
                    icon: const Icon(Icons.add),
                  ),
                ],
              ),
              if (_tags.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(top: 8),
                  child: Wrap(
                    spacing: 6,
                    runSpacing: 4,
                    children: _tags
                        .map((t) => Chip(
                              label: Text(t),
                              onDeleted: () => setState(() => _tags.remove(t)),
                            ))
                        .toList(),
                  ),
                ),
              if (_error != null) ...[
                const SizedBox(height: 12),
                Text(_error!,
                    style: TextStyle(color: Theme.of(context).colorScheme.error)),
              ],
              const SizedBox(height: 32),
              FilledButton(
                onPressed: _busy ? null : _submit,
                child: _busy
                    ? const SizedBox.square(
                        dimension: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Publish'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
