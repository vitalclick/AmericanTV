import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:americantv/api/api_exception.dart';

void main() {
  group('ApiException.fromDio', () {
    test('maps connection errors to a network exception', () {
      final e = DioException(
        requestOptions: RequestOptions(path: '/x'),
        type: DioExceptionType.connectionError,
      );
      final api = ApiException.fromDio(e);
      expect(api.isNetwork, isTrue);
      expect(api.isUnauthenticated, isFalse);
    });

    test('maps 401 to an unauthenticated exception', () {
      final e = DioException(
        requestOptions: RequestOptions(path: '/x'),
        response: Response(
          requestOptions: RequestOptions(path: '/x'),
          statusCode: 401,
          data: const {'message': 'Token expired'},
        ),
      );
      final api = ApiException.fromDio(e);
      expect(api.isUnauthenticated, isTrue);
      expect(api.statusCode, 401);
      expect(api.message, 'Token expired');
    });

    test('extracts Laravel field errors from a 422', () {
      final e = DioException(
        requestOptions: RequestOptions(path: '/x'),
        response: Response(
          requestOptions: RequestOptions(path: '/x'),
          statusCode: 422,
          data: const {
            'message': 'Invalid',
            'errors': {
              'password': ['wrong'],
              'email': ['taken', 'reserved'],
            },
          },
        ),
      );
      final api = ApiException.fromDio(e);
      expect(api.statusCode, 422);
      expect(api.fieldErrors['password'], ['wrong']);
      expect(api.fieldErrors['email'], ['taken', 'reserved']);
    });

    test('falls back to a generic message when none is supplied', () {
      final e = DioException(
        requestOptions: RequestOptions(path: '/x'),
        response: Response(
          requestOptions: RequestOptions(path: '/x'),
          statusCode: 500,
        ),
      );
      final api = ApiException.fromDio(e);
      expect(api.message.isNotEmpty, isTrue);
      expect(api.statusCode, 500);
    });
  });
}
