import 'dart:async';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';

final uploadRepositoryProvider = Provider<UploadRepository>((ref) {
  return UploadRepository(ref.read(dioProvider));
});

class UploadRepository {
  UploadRepository(this._dio);
  final Dio _dio;

  /// Chunks the file in 5 MiB slices and POSTs each to /me/videos/chunk,
  /// then asks the server to merge. Reports progress 0-1 to the caller.
  /// Returns the server's Video row id once the merge completes.
  Future<int> uploadVideo({
    required File file,
    String? title,
    bool isShorts = false,
    void Function(double progress)? onProgress,
  }) async {
    const chunkSize = 5 * 1024 * 1024;
    final raf = await file.open();
    final total = await file.length();
    final filename = file.uri.pathSegments.last;
    final totalChunks = (total / chunkSize).ceil();

    try {
      for (var index = 0; index < totalChunks; index++) {
        final end = ((index + 1) * chunkSize).clamp(0, total);
        final length = end - index * chunkSize;
        await raf.setPosition(index * chunkSize);
        final bytes = await raf.read(length);

        final form = FormData.fromMap({
          'chunk': MultipartFile.fromBytes(
            bytes,
            filename: '$filename.part$index',
          ),
          'index': index,
          'total_chunks': totalChunks,
          'file_name': filename,
          if (isShorts) 'shorts': '1',
        });

        try {
          await _dio.post<void>('/me/videos/chunk', data: form);
        } on DioException catch (e) {
          throw ApiException.fromDio(e);
        }

        onProgress?.call((index + 1) / totalChunks);
      }

      try {
        final response = await _dio.post<Map<String, dynamic>>(
          '/me/videos/merge',
          data: {
            'file_name': filename,
            'total_chunks': totalChunks,
            if (isShorts) 'shorts': '1',
          },
        );
        final video = (response.data?['data']?['video']) as Map<String, dynamic>?;
        final id = video?['id'] as int?;
        if (id == null) {
          throw const ApiException(message: 'Server merged the upload but returned no video id.');
        }
        if (title != null && title.isNotEmpty) {
          await _submitDetails(videoId: id, title: title);
        }
        return id;
      } on DioException catch (e) {
        throw ApiException.fromDio(e);
      }
    } finally {
      await raf.close();
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
}
