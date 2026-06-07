<?php

namespace App\Services\IAP;

use Google\Client as GoogleClient;
use Google\Service\AndroidPublisher;
use Illuminate\Support\Facades\Log;

/**
 * Verifies a Google Play Billing purchase via the Android Publisher API.
 *
 * The mobile client sends the purchase token returned by Play Billing. We hit
 * either purchases.products.get (one-time) or purchases.subscriptionsv2.get
 * (subscription) depending on the product type registered server-side.
 *
 * Requires a service account in your GCP project with access to the Play
 * Console (Setup → API access → grant the service account "View financial data"
 * and "Manage orders" permissions on the app).
 */
class GoogleReceiptVerifier
{
    public function __construct(
        private readonly string $packageName,
        private readonly string $serviceAccountJsonPath,
    ) {}

    public function verifyProduct(string $productId, string $purchaseToken): ReceiptVerificationResult
    {
        try {
            $publisher = $this->publisher();
            $purchase  = $publisher->purchases_products->get(
                $this->packageName,
                $productId,
                $purchaseToken
            );
        } catch (\Throwable $e) {
            Log::warning('Google product verify failed', ['err' => $e->getMessage()]);
            return ReceiptVerificationResult::failure('Play Billing API error');
        }

        // purchaseState: 0 = purchased, 1 = canceled, 2 = pending.
        if ($purchase->getPurchaseState() !== 0) {
            return ReceiptVerificationResult::failure('Purchase not in PURCHASED state');
        }

        return new ReceiptVerificationResult(
            valid: true,
            transactionId: $purchase->getOrderId() ?: $purchaseToken,
            productId: $productId,
            purchasedAt: new \DateTimeImmutable('@' . (int) ($purchase->getPurchaseTimeMillis() / 1000)),
            isSubscription: false,
            rawPayload: json_encode($purchase->toSimpleObject()),
        );
    }

    public function verifySubscription(string $purchaseToken): ReceiptVerificationResult
    {
        try {
            $publisher = $this->publisher();
            // subscriptionsv2 returns a richer payload; use the v1 endpoint if
            // you're still on the older subscription model in Play Console.
            $sub = $publisher->purchases_subscriptionsv2->get(
                $this->packageName,
                $purchaseToken
            );
        } catch (\Throwable $e) {
            Log::warning('Google subscription verify failed', ['err' => $e->getMessage()]);
            return ReceiptVerificationResult::failure('Play Billing API error');
        }

        $state = $sub->getSubscriptionState(); // ACTIVE, CANCELED, EXPIRED, ...
        if (!in_array($state, ['SUBSCRIPTION_STATE_ACTIVE', 'SUBSCRIPTION_STATE_IN_GRACE_PERIOD'], true)) {
            return ReceiptVerificationResult::failure("Subscription state: {$state}");
        }

        $lineItems = $sub->getLineItems();
        $first     = $lineItems[0] ?? null;

        return new ReceiptVerificationResult(
            valid: true,
            transactionId: $sub->getLatestOrderId(),
            productId: $first ? $first->getProductId() : null,
            purchasedAt: new \DateTimeImmutable($sub->getStartTime()),
            expiresAt: $first ? new \DateTimeImmutable($first->getExpiryTime()) : null,
            isSubscription: true,
            rawPayload: json_encode($sub->toSimpleObject()),
        );
    }

    private function publisher(): AndroidPublisher
    {
        $client = new GoogleClient();
        $client->setAuthConfig($this->serviceAccountJsonPath);
        $client->addScope(AndroidPublisher::ANDROIDPUBLISHER);
        return new AndroidPublisher($client);
    }
}
