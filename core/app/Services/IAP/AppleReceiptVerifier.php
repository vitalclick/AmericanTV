<?php

namespace App\Services\IAP;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies an App Store transaction via the App Store Server API.
 *
 * The mobile client sends the signed JWS representation it gets back from
 * StoreKit 2's `Transaction.finish()` flow. We decode the JWS header to find
 * Apple's signing key, verify the signature against Apple's public keys, then
 * read the inner payload.
 *
 * For production: replace the JWS verification block with a well-tested library
 * (e.g. readdle/app-store-server-api) — rolling JWS verification by hand is
 * easy to get subtly wrong.
 */
class AppleReceiptVerifier
{
    public function __construct(
        private readonly string $bundleId,
        private readonly string $issuerId,
        private readonly string $keyId,
        private readonly string $privateKey,
        private readonly string $environment = 'production',
    ) {}

    public function verify(string $signedTransaction): ReceiptVerificationResult
    {
        try {
            $payload = $this->decodeJws($signedTransaction);
        } catch (\Throwable $e) {
            Log::warning('Apple JWS decode failed', ['err' => $e->getMessage()]);
            return ReceiptVerificationResult::failure('Invalid signed transaction');
        }

        if (($payload['bundleId'] ?? null) !== $this->bundleId) {
            return ReceiptVerificationResult::failure('Bundle ID mismatch');
        }

        // Cross-check with App Store Server API (defence in depth — confirms the
        // transaction hasn't been refunded server-side since the client encoded it).
        $apiResult = $this->lookupTransaction($payload['transactionId']);
        if (!$apiResult['ok']) {
            return ReceiptVerificationResult::failure($apiResult['error']);
        }

        $isSubscription = !empty($payload['expiresDate']);

        return new ReceiptVerificationResult(
            valid: true,
            transactionId: (string) $payload['transactionId'],
            productId: $payload['productId'],
            purchasedAt: new \DateTimeImmutable('@' . (int) ($payload['purchaseDate'] / 1000)),
            expiresAt: $isSubscription
                ? new \DateTimeImmutable('@' . (int) ($payload['expiresDate'] / 1000))
                : null,
            isSubscription: $isSubscription,
            isSandbox: ($payload['environment'] ?? '') === 'Sandbox',
            rawPayload: json_encode($payload),
        );
    }

    /**
     * Decode the JWS payload. SECURITY: production code must verify the
     * signature against Apple's published x5c chain. Stubbed here for clarity.
     */
    private function decodeJws(string $jws): array
    {
        $parts = explode('.', $jws);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Malformed JWS');
        }

        // TODO(production): verify $parts[2] signature against the x5c chain in
        // the header. Apple rotates these; use a JWK cache with refresh.
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        if (!is_array($payload)) {
            throw new \RuntimeException('Malformed JWS payload');
        }

        return $payload;
    }

    private function lookupTransaction(string $transactionId): array
    {
        $host = $this->environment === 'sandbox'
            ? 'api.storekit-sandbox.itunes.apple.com'
            : 'api.storekit.itunes.apple.com';

        $token = $this->buildAppStoreServerJwt();

        $response = Http::withToken($token)
            ->acceptJson()
            ->get("https://{$host}/inApps/v1/transactions/{$transactionId}");

        if ($response->failed()) {
            return ['ok' => false, 'error' => 'App Store API error: ' . $response->status()];
        }

        return ['ok' => true, 'signed' => $response->json('signedTransactionInfo')];
    }

    /**
     * App Store Server API requires an ES256 JWT signed with your in-app purchase key.
     * Use firebase/php-jwt or lcobucci/jwt in production.
     */
    private function buildAppStoreServerJwt(): string
    {
        // TODO(production): sign ES256 JWT with $this->privateKey,
        // header { alg: ES256, kid: $this->keyId, typ: JWT },
        // payload { iss: $this->issuerId, iat, exp, aud: 'appstoreconnect-v1', bid: $this->bundleId }.
        throw new \RuntimeException('JWT signing not implemented in skeleton');
    }
}
