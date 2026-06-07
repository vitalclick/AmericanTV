import 'dart:async';
import 'dart:io';
import 'dart:math';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../../../core/services/cache_service.dart';

final uploadRepositoryProvider = Provider<UploadRepository>((ref) {
  return UploadRepository(ref.read(dioProvider), ref.read(cacheServiceProvider));
});

/// State for one in-flight upload. Persisted to the cache between resumes
/// so the client knows which uniqueId to ask the server about and where
/// the source file lives.
class UploadJob {
  const UploadJob({
    required this.uniqueId,
    required this.localPath,
    required this.fileName,
    required this.extension,
    required this.totalChunks,
    required this.isShorts,
    this.title,
  });

  factory UploadJob.fromJson(Map<String, dynamic> json) => UploadJob(
        uniqueId: json['unique_id'] as String,
        localPath: json['local_path'] as String,
        fileName: json['file_name'] as String,
        extension: json['extension'] as String,
        totalChunks: json['total_chunks'] as int,
        isShorts: json['is_shorts'] as bool? ?? false,
        title: json['title'] as String?,
      );

  final String uniqueId;
  final String localPath;
  final String fileName;
  final String extension;
  final int totalChunks;
  final bool isShorts;
  final String? title;

  Map<String, dynamic> toJson() => {
        'unique_id': uniqueId,
        'local_path': localPath,
        'file_name': fileName,
        'extension': extension,
        'total_chunks': totalChunks,
        'is_shorts': isShorts,
        if (title != null) 'title': title,
      };
}

class UploadRepository {
  UploadRepository(this._dio, this._cache);
  final Dio _dio;
  final CacheService _cache;

  static const _chunkSize = 5 * 1024 * 1024;
  static const _activeJobKey = 'cache:upload:active';

  /// Returns the most recent unfinished upload, if any. Lets the UI prompt
  /// "resume your upload?" on next launch.
  Future<UploadJob?> activeJob() async {
    final raw = await _cache.readJson(_activeJobKey);
    return raw == null ? null : UploadJob.fromJson(raw);
  }

  Future<void> clearActiveJob() => _cache.remove(_activeJobKey);

  /// Picks up where the upload left off — queries the server for which
  /// chunks already arrived and re-uploads only the gaps.
  Future<int> resume(
    UploadJob job, {
    void Function(double progress)? onProgress,
  }) async {
    final file = File(job.localPath);
    if (!await file.exists()) {
      await clearActiveJob();
      throw const ApiException(
        message: 'The source file is no longer on this device — please pick again.',
      );
    }

    final received = await _inventory(job);
    return _drive(job, file, received, onProgress);
  }

  /// Starts a fresh upload. Persists the job state before the first chunk
  /// goes out so a crash between chunks 0 and 1 is still resumable.
  Future<int> startUpload({
    required File file,
    String? title,
    bool isShorts = false,
    void Function(double progress)? onProgress,
  }) async {
    final filename = file.uri.pathSegments.last;
    final extension = filename.contains('.')
        ? filename.substring(filename.lastIndexOf('.') + 1).toLowerCase()
        : 'mp4';
    final size = await file.length();
    final totalChunks = (size / _chunkSize).ceil();

    final job = UploadJob(
      uniqueId: _newUniqueId(),
      localPath: file.path,
      fileName: filename,
      extension: extension,
      totalChunks: totalChunks,
      isShorts: isShorts,
      title: title,
    );

    await _cache.writeJson(_activeJobKey, job.toJson());
    return _drive(job, file, const <int>{}, onProgress);
  }

  Future<Set<int>> _inventory(UploadJob job) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        '/me/videos/chunks/${job.uniqueId}',
        queryParameters: {'file_name': job.fileName},
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      return ((data['chunks_present'] as List?) ?? const [])
          .map((e) => (e as num).toInt())
          .toSet();
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<int> _drive(
    UploadJob job,
    File file,
    Set<int> alreadyPresent,
    void Function(double progress)? onProgress,
  ) async {
    final raf = await file.open();
    final size = await file.length();
    try {
      onProgress?.call(alreadyPresent.length / job.totalChunks);

      for (var index = 0; index < job.totalChunks; index++) {
        if (alreadyPresent.contains(index)) continue;

        final start = index * _chunkSize;
        final end = ((index + 1) * _chunkSize).clamp(0, size);
        final length = end - start;
        await raf.setPosition(start);
        final bytes = await raf.read(length);

        final form = FormData.fromMap({
          'extension': job.extension,
          'fileName':  job.fileName,
          'uniqueId':  job.uniqueId,
          'index':     index,
          'chunk':     MultipartFile.fromBytes(
            bytes,
            filename: '${job.fileName}.part$index',
          ),
          if (job.isShorts) 'shorts': '1',
        });

        try {
          await _dio.post<void>('/me/videos/chunk', data: form);
        } on DioException catch (e) {
          throw ApiException.fromDio(e);
        }

        onProgress?.call((index + 1) / job.totalChunks);
      }

      final videoId = await _merge(job);
      if (job.title != null && job.title!.isNotEmpty) {
        await _submitDetails(videoId: videoId, title: job.title!);
      }
      await clearActiveJob();
      return videoId;
    } finally {
      await raf.close();
    }
  }

  Future<int> _merge(UploadJob job) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/me/videos/merge',
        data: {
          'fileName':    job.fileName,
          'uniqueId':    job.uniqueId,
          'totalChunks': job.totalChunks,
          'extension':   job.extension,
          if (job.isShorts) 'shorts': '1',
        },
      );
      final video = (response.data?['data']?['video']) as Map<String, dynamic>?;
      final id = video?['id'] as int?;
      if (id == null) {
        throw const ApiException(message: 'Server merged the upload but returned no video id.');
      }
      return id;
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<void> _submitDetails({required int videoId, required String title}) async {
    try {
      await _dio.patch<void>(
        '/me/videos/$videoId/details',
        data: {'title': title},
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  String _newUniqueId() {
    // 12 url-safe characters is enough for ~7e21 namespace — collision
    // odds are negligible for the upload temp-dir lifetime.
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    final rand = Random.secure();
    return List.generate(12, (_) => chars[rand.nextInt(chars.length)]).join();
  }
}
