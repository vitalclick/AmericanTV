import 'dart:io' show Platform;

import 'package:dio/dio.dart';
import 'package:flutter/services.dart' show PlatformException;
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:purchases_flutter/purchases_flutter.dart';

import '../../../api/api_exception.dart';
import '../../../api/dio_client.dart';
import '../domain/plan_preview.dart';

final iapRepositoryProvider = Provider<IapRepository>((ref) {
  return IapRepository(ref.read(dioProvider));
});

class IapRepository {
  IapRepository(this._dio);
  final Dio _dio;

  Future<PlanPreview> planBySlug(String slug) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>('/plans/$slug');
      return PlanPreview.fromJson(response.data!['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  /// Drives the native purchase flow via RevenueCat, then verifies the
  /// transaction with the Laravel backend. Returns once the server has
  /// recorded the unlock — the caller can then refresh entitlements.
  Future<IapUnlockResult> purchaseAndVerifyPlan(PlanPreview plan) async {
    final productId = plan.storeProductId;
    if (productId == null) {
      throw const ApiException(message: 'This plan is not available on mobile.');
    }

    // Step 1 — present the platform purchase sheet via RevenueCat.
    final CustomerInfo info;
    final StoreTransaction tx;
    try {
      final products = await Purchases.getProducts([productId]);
      if (products.isEmpty) {
        throw const ApiException(message: 'Product not configured on the store.');
      }
      final result = await Purchases.purchaseStoreProduct(products.first);
      info = result.customerInfo;
      tx = result.storeTransaction;
    } on PlatformException catch (e) {
      final code = PurchasesErrorHelper.getErrorCode(e);
      if (code == PurchasesErrorCode.purchaseCancelledError) {
        throw const ApiException(message: 'Purchase cancelled.');
      }
      throw ApiException(message: e.message ?? 'Purchase failed.');
    }

    // Step 2 — hand the receipt to Laravel so PurchasedPlan + Transaction
    // rows land in the same shape they would for a web gateway purchase.
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/purchases/iap/verify',
        data: {
          'platform': Platform.isIOS ? 'apple' : 'google',
          'product_id': productId,
          'transaction_id': tx.transactionIdentifier,
          'receipt': tx.transactionIdentifier, // RevenueCat hides the raw receipt; the server resolves via the transaction id + originalTransactionId webhook.
          'is_subscription': true,
        },
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      return IapUnlockResult(
        unlocked: data['unlocked'] as bool? ?? false,
        planId: data['plan_id'] as int?,
        customerInfo: info,
      );
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }
}

class IapUnlockResult {
  const IapUnlockResult({required this.unlocked, this.planId, this.customerInfo});
  final bool unlocked;
  final int? planId;
  final CustomerInfo? customerInfo;
}
