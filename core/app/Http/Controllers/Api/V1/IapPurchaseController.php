<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\Deposit;
use App\Models\IapProduct;
use App\Services\IAP\AppleReceiptVerifier;
use App\Services\IAP\GoogleReceiptVerifier;
use App\Services\IAP\ReceiptVerificationResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single endpoint for native-app purchases. The mobile app calls this after
 * StoreKit / Play Billing returns a successful purchase. We verify with
 * Apple/Google server-to-server, then mint a Deposit and reuse the existing
 * post-purchase pipeline so PurchasedPlan/PurchasedVideo/PurchasedPlaylist +
 * Transaction rows are created identically to the web flow.
 */
class IapPurchaseController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'platform'       => 'required|in:apple,google',
            'product_id'     => 'required|string',
            'transaction_id' => 'required|string',
            'receipt'        => 'required|string',
            // For Google subscriptions only — distinguishes the API call below.
            'is_subscription' => 'sometimes|boolean',
        ]);

        // Idempotency: if we've already processed this transaction, return its result.
        $existing = Deposit::where('iap_transaction_id', $request->transaction_id)->first();
        if ($existing) {
            return $this->success($existing);
        }

        $verification = $this->verifyWithStore($request);
        if (!$verification->valid) {
            return response()->json([
                'status'  => 'error',
                'message' => $verification->error ?? 'Receipt verification failed',
            ], 422);
        }

        // Resolve the product server-side. Don't trust the client to tell us
        // what they're buying — go from the verified product_id to our row.
        $product = $request->platform === 'apple'
            ? IapProduct::forApple($verification->productId)->first()
            : IapProduct::forGoogle($verification->productId)->first();

        if (!$product) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unknown product',
            ], 422);
        }

        $item = $product->resolveItem();
        if (!$item) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Product not currently available',
            ], 422);
        }

        $deposit = DB::transaction(function () use ($request, $verification, $product, $item) {
            $deposit                     = new Deposit();
            $deposit->user_id            = $request->user()->id;
            $deposit->method_code        = config("iap.method_code.{$request->platform}");
            $deposit->method_currency    = 'USD';
            $deposit->amount             = $product->price_usd_mobile;
            $deposit->charge             = 0;
            $deposit->rate               = 1;
            $deposit->final_amount       = $product->price_usd_mobile;
            $deposit->btc_amount         = 0;
            $deposit->btc_wallet         = '';
            $deposit->trx                = strtoupper(Str::random(12));
            $deposit->status             = Status::PAYMENT_INITIATE;
            $deposit->iap_transaction_id = $verification->transactionId;
            $deposit->iap_product_id     = $verification->productId;
            $deposit->iap_receipt        = $verification->rawPayload;

            match ($product->type) {
                'plan'     => $deposit->plan_id = $item->id,
                'video'    => $deposit->video_id = $item->id,
                'playlist' => $deposit->playlist_id = $item->id,
            };

            $deposit->save();

            // Reuse the existing post-purchase pipeline. This creates the right
            // PurchasedPlan / PurchasedVideo / PurchasedPlaylist row, transfers
            // funds, and emits notifications — identical to the web IPN path.
            PaymentController::userDataUpdate($deposit);

            return $deposit->fresh();
        });

        return $this->success($deposit);
    }

    public function restore(Request $request): JsonResponse
    {
        // The mobile app calls this on "Restore Purchases". Treat each entry
        // as a verify call; idempotency handles dupes.
        $request->validate([
            'platform' => 'required|in:apple,google',
            'entries'  => 'required|array|min:1',
        ]);

        $results = [];
        foreach ($request->entries as $entry) {
            $sub = new Request($entry + ['platform' => $request->platform]);
            $sub->setUserResolver(fn () => $request->user());
            $results[] = $this->verify($sub)->getData(true);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $results,
        ]);
    }

    private function verifyWithStore(Request $request): ReceiptVerificationResult
    {
        if ($request->platform === 'apple') {
            $verifier = new AppleReceiptVerifier(
                bundleId:    config('iap.apple.bundle_id'),
                issuerId:    config('iap.apple.issuer_id'),
                keyId:       config('iap.apple.key_id'),
                privateKey:  config('iap.apple.private_key'),
                environment: config('iap.apple.environment'),
            );
            return $verifier->verify($request->receipt);
        }

        $verifier = new GoogleReceiptVerifier(
            packageName:            config('iap.google.package_name'),
            serviceAccountJsonPath: config('iap.google.service_account_json'),
        );

        return $request->boolean('is_subscription')
            ? $verifier->verifySubscription($request->receipt)
            : $verifier->verifyProduct($request->product_id, $request->receipt);
    }

    private function success(Deposit $deposit): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => [
                'trx'             => $deposit->trx,
                'transaction_id'  => $deposit->iap_transaction_id,
                'unlocked'        => $deposit->status == Status::PAYMENT_SUCCESS,
                'plan_id'         => $deposit->plan_id,
                'video_id'        => $deposit->video_id,
                'playlist_id'     => $deposit->playlist_id,
            ],
        ]);
    }
}
