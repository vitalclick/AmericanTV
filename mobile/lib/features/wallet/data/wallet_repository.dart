import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../domain/transaction.dart';

final walletRepositoryProvider = Provider<WalletRepository>((ref) {
  return WalletRepository(ref.read(dioProvider));
});

class WalletRepository {
  WalletRepository(this._dio);
  final Dio _dio;

  Future<WalletSnapshot> wallet() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>('/me/wallet');
      final body = response.data!;
      return WalletSnapshot(
        balance: (body['balance'] as num?)?.toDouble() ?? 0,
        currency: body['currency'] as String? ?? 'USD',
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Future<PaginatedTransactions> transactions({String? type, int page = 1}) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        '/me/transactions',
        queryParameters: {
          if (type != null) 'type': type,
          'page': page,
        },
      );
      final body = response.data!;
      final items = (body['data'] as List)
          .map((e) => Transaction.fromJson(e as Map<String, dynamic>))
          .toList();
      final meta = (body['meta'] as Map?)?.cast<String, dynamic>() ?? const {};
      return PaginatedTransactions(
        items: items,
        page: meta['current_page'] as int? ?? 1,
        lastPage: meta['last_page'] as int? ?? 1,
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}

class WalletSnapshot {
  const WalletSnapshot({required this.balance, required this.currency});
  final double balance;
  final String currency;
}

class PaginatedTransactions {
  const PaginatedTransactions({
    required this.items,
    required this.page,
    required this.lastPage,
  });

  final List<Transaction> items;
  final int page;
  final int lastPage;

  bool get hasMore => page < lastPage;
}
