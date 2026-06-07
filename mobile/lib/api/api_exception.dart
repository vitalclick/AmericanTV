import 'package:dio/dio.dart';

/// User-facing API error. Maps Laravel's validation envelope
/// `{ message, errors: { field: [..] } }` plus auth/network failures.
class ApiException implements Exception {
  const ApiException({
    required this.message,
    this.statusCode,
    this.fieldErrors = const {},
    this.isNetwork = false,
    this.isUnauthenticated = false,
  });

  factory ApiException.fromDio(DioException e) {
    if (e.type == DioExceptionType.connectionError ||
        e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.sendTimeout) {
      return const ApiException(
        message: 'Could not reach the server. Check your connection.',
        isNetwork: true,
      );
    }

    final response = e.response;
    final status = response?.statusCode;
    final data = response?.data;

    if (status == 401) {
      return ApiException(
        message: _extractMessage(data) ?? 'Please sign in to continue.',
        statusCode: status,
        isUnauthenticated: true,
      );
    }

    if (status == 422 && data is Map) {
      final errors = (data['errors'] as Map?)?.map(
            (key, value) => MapEntry(
              key.toString(),
              (value as List).map((e) => e.toString()).toList(),
            ),
          ) ??
          <String, List<String>>{};
      return ApiException(
        message: _extractMessage(data) ?? 'Please check the form and try again.',
        statusCode: status,
        fieldErrors: errors,
      );
    }

    return ApiException(
      message: _extractMessage(data) ?? 'Something went wrong. Please try again.',
      statusCode: status,
    );
  }

  final String message;
  final int? statusCode;
  final Map<String, List<String>> fieldErrors;
  final bool isNetwork;
  final bool isUnauthenticated;

  static String? _extractMessage(dynamic data) {
    if (data is Map && data['message'] is String) return data['message'];
    return null;
  }

  @override
  String toString() => 'ApiException($statusCode: $message)';
}
