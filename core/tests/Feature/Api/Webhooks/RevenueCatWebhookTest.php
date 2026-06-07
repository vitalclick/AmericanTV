<?php

namespace Tests\Feature\Api\Webhooks;

use App\Models\Deposit;
use App\Models\PurchasedPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RevenueCatWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (!\Schema::hasTable('users')) {
            $this->markTestSkipped('users table not in migrations.');
        }

        config(['iap.revenuecat.webhook_auth_header' => 'test-shared-secret']);
    }

    public function test_rejects_unauthenticated_requests(): void
    {
        $this->postJson('/api/v1/webhooks/revenuecat', [
            'event' => ['type' => 'RENEWAL', 'original_transaction_id' => 'X'],
        ])->assertStatus(401);
    }

    public function test_renewal_extends_expired_date(): void
    {
        $deposit = $this->seedDeposit();
        PurchasedPlan::forceCreate([
            'user_id'      => $deposit->user_id,
            'plan_id'      => 1,
            'owner_id'     => 1,
            'trx'          => $deposit->trx,
            'amount'       => 4.99,
            'expired_date' => Carbon::now()->addDays(5),
        ]);

        $newExpiry = Carbon::now()->addDays(35);

        $this->withHeaders(['Authorization' => 'test-shared-secret'])
            ->postJson('/api/v1/webhooks/revenuecat', [
                'event' => [
                    'type'                    => 'RENEWAL',
                    'original_transaction_id' => $deposit->iap_transaction_id,
                    'expiration_at_ms'        => $newExpiry->getTimestampMs(),
                ],
            ])->assertNoContent();

        $purchased = PurchasedPlan::where('trx', $deposit->trx)->first();
        $this->assertEqualsWithDelta(
            $newExpiry->getTimestampMs(),
            Carbon::parse($purchased->expired_date)->getTimestampMs(),
            5_000, // 5-second tolerance.
        );
    }

    public function test_refund_revokes_access_immediately(): void
    {
        $deposit = $this->seedDeposit();
        PurchasedPlan::forceCreate([
            'user_id'      => $deposit->user_id,
            'plan_id'      => 1,
            'owner_id'     => 1,
            'trx'          => $deposit->trx,
            'amount'       => 4.99,
            'expired_date' => Carbon::now()->addDays(20),
        ]);

        $this->withHeaders(['Authorization' => 'test-shared-secret'])
            ->postJson('/api/v1/webhooks/revenuecat', [
                'event' => [
                    'type'                    => 'REFUND',
                    'original_transaction_id' => $deposit->iap_transaction_id,
                    'price'                   => 4.99,
                ],
            ])->assertNoContent();

        $purchased = PurchasedPlan::where('trx', $deposit->trx)->first();
        $this->assertTrue(Carbon::parse($purchased->expired_date)->isPast());
    }

    private function seedDeposit(): Deposit
    {
        return Deposit::forceCreate([
            'user_id'           => 1,
            'plan_id'           => 1,
            'method_code'       => 5001,
            'method_currency'   => 'USD',
            'amount'            => 4.99,
            'charge'            => 0,
            'rate'              => 1,
            'final_amount'      => 4.99,
            'btc_amount'        => 0,
            'btc_wallet'        => '',
            'trx'               => 'TESTTRX12345',
            'status'            => 2, // PAYMENT_SUCCESS
            'iap_transaction_id' => 'apple-original-tx-1',
            'iap_product_id'    => 'com.americantv.plan.test.monthly',
        ]);
    }
}
