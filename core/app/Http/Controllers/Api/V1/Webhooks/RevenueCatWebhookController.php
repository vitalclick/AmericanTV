<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\PurchasedPlan;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Receives RevenueCat customer events. The first purchase is recorded
 * elsewhere (IapPurchaseController::verify is what the mobile client calls
 * synchronously); this webhook keeps the database in sync for the events
 * that happen asynchronously after that — renewals, refunds, billing
 * issues, and unsubscribes.
 *
 * RevenueCat event reference: https://www.revenuecat.com/docs/webhooks
 *
 * Without this handler:
 * - Renewals silently lapse after the initial 30-day window.
 * - Refunded users keep access; Apple penalises apps that don't comply.
 */
class RevenueCatWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $expected = (string) config('iap.revenuecat.webhook_auth_header');
        $actual   = (string) $request->header('Authorization', '');

        if (! $expected || ! hash_equals($expected, $actual)) {
            Log::warning('RevenueCat webhook auth header mismatch');
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event', []);
        $type  = (string) ($event['type'] ?? '');
        $txId  = (string) ($event['original_transaction_id']
            ?? $event['transaction_id']
            ?? '');

        if (! $type || ! $txId) {
            return response()->json(['message' => 'Malformed event'], 422);
        }

        // The seed Deposit we wrote at /purchases/iap/verify ties the
        // store transaction to a (user, plan) — we look up everything else
        // through it. PurchasedPlan was created by userDataUpdate at that
        // point too.
        $deposit = Deposit::where('iap_transaction_id', $txId)->first();
        if (! $deposit) {
            // Pre-existing subscription that pre-dates our IAP integration.
            // RevenueCat retries on non-2xx — acknowledge instead.
            return response()->json([], 204);
        }

        match ($type) {
            'INITIAL_PURCHASE' => null, // Already handled by /verify.
            'RENEWAL'          => $this->extendSubscription($deposit, $event),
            'CANCELLATION', 'UNSUBSCRIBE' => null, // Access continues until expires_date.
            'EXPIRATION'       => $this->markExpired($deposit, $event),
            'BILLING_ISSUE'    => $this->flagBillingIssue($deposit, $event),
            'REFUND'           => $this->revokeForRefund($deposit, $event),
            'PRODUCT_CHANGE'   => $this->extendSubscription($deposit, $event), // re-anchor expiry.
            default            => null,
        };

        return response()->json([], 204);
    }

    private function extendSubscription(Deposit $deposit, array $event): void
    {
        $purchased = PurchasedPlan::where('trx', $deposit->trx)->latest('id')->first();
        if (! $purchased) {
            return;
        }

        // expiration_at_ms is canonical; period_type tells us trial vs normal.
        $expiresMs = $event['expiration_at_ms'] ?? null;
        $expiresAt = $expiresMs
            ? Carbon::createFromTimestampMs($expiresMs)
            : Carbon::parse($purchased->expired_date)->addDays(30);

        $purchased->expired_date = $expiresAt;
        $purchased->save();

        Transaction::create([
            'user_id'      => $deposit->user_id,
            'plan_id'      => $deposit->plan_id,
            'amount'       => 0,
            'charge'       => 0,
            'post_balance' => $deposit->user?->balance ?? 0,
            'trx_type'     => '+',
            'details'      => 'Subscription renewed via ' . $deposit->methodName(),
            'trx'          => $deposit->trx,
            'remark'       => 'subscription_renewal',
        ]);
    }

    private function markExpired(Deposit $deposit, array $event): void
    {
        $purchased = PurchasedPlan::where('trx', $deposit->trx)->latest('id')->first();
        if ($purchased) {
            $purchased->expired_date = Carbon::now();
            $purchased->save();
        }
    }

    private function flagBillingIssue(Deposit $deposit, array $event): void
    {
        // Drop a row into Transactions so this shows up in the user's history.
        Transaction::create([
            'user_id'      => $deposit->user_id,
            'plan_id'      => $deposit->plan_id,
            'amount'       => 0,
            'charge'       => 0,
            'post_balance' => $deposit->user?->balance ?? 0,
            'trx_type'     => '-',
            'details'      => 'Billing issue from ' . $deposit->methodName() . ' — please update your payment method.',
            'trx'          => $deposit->trx,
            'remark'       => 'billing_issue',
        ]);

        // Optional: send a push via the existing notify pipeline.
        // notify($deposit->user, 'IAP_BILLING_ISSUE', [...], ['push','email']);
    }

    private function revokeForRefund(Deposit $deposit, array $event): void
    {
        $purchased = PurchasedPlan::where('trx', $deposit->trx)->latest('id')->first();
        if ($purchased) {
            $purchased->expired_date = Carbon::now()->subSecond();
            $purchased->save();
        }

        Transaction::create([
            'user_id'      => $deposit->user_id,
            'plan_id'      => $deposit->plan_id,
            'amount'       => (float) ($event['price'] ?? $deposit->amount),
            'charge'       => 0,
            'post_balance' => $deposit->user?->balance ?? 0,
            'trx_type'     => '-',
            'details'      => 'Refund processed via ' . $deposit->methodName(),
            'trx'          => $deposit->trx,
            'remark'       => 'refund',
        ]);
    }
}
