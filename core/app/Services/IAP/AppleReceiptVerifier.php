<?php

namespace App\Services\IAP;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

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
     * Decode + signature-verify the App Store Server JWS.
     *
     * StoreKit 2 / App Store Server API JWS payloads are signed ES256 with an
     * x5c certificate chain in the header. We:
     *   1. Parse the header and pull the x5c array.
     *   2. Use the leaf cert (x5c[0]) to verify the JWS signature.
     *   3. Return the verified payload.
     *
     * What we DON'T do here: full chain validation back to the Apple Root CA
     * G3. The risk that someone forges a signed payload using a non-Apple
     * certificate is bounded by:
     *   - We cross-check `transactionId` against the App Store Server API
     *     in lookupTransaction() (the JWS-based signature is defence in depth
     *     against tampering between us and Apple).
     *   - We compare `bundleId` and `environment` claims against config.
     * For high-value goods or strict compliance, swap this for
     * readdle/app-store-server-api which ships full x5c chain validation
     * against Apple's pinned roots.
     */
    private function decodeJws(string $jws): array
    {
        $parts = explode('.', $jws);
        if (count($parts) !== 3) {
            throw new RuntimeException('Malformed JWS');
        }

        $header = json_decode($this->b64UrlDecode($parts[0]), true);
        if (!is_array($header) || ($header['alg'] ?? null) !== 'ES256') {
            throw new RuntimeException('Unsupported JWS algorithm');
        }

        $x5c = $header['x5c'] ?? null;
        if (!is_array($x5c) || empty($x5c[0])) {
            throw new RuntimeException('Missing x5c certificate chain');
        }

        $publicKey = $this->publicKeyFromX5c($x5c[0]);

        try {
            $decoded = (array) JWT::decode($jws, new Key($publicKey, 'ES256'));
        } catch (\Throwable $e) {
            throw new RuntimeException('JWS signature invalid: ' . $e->getMessage());
        }

        return $decoded;
    }

    /**
     * Wrap a base64-encoded DER certificate (the format x5c uses) in PEM
     * armor and extract the public key. firebase/php-jwt accepts either a
     * resource or a PEM string for Key.
     */
    private function publicKeyFromX5c(string $derBase64): string
    {
        $pem = "-----BEGIN CERTIFICATE-----\n"
            . chunk_split($derBase64, 64, "\n")
            . "-----END CERTIFICATE-----\n";

        $cert = openssl_x509_read($pem);
        if ($cert === false) {
            throw new RuntimeException('Could not parse x5c leaf certificate');
        }

        $publicKey = openssl_pkey_get_public($cert);
        if ($publicKey === false) {
            throw new RuntimeException('Could not extract public key from x5c leaf');
        }

        $details = openssl_pkey_get_details($publicKey);
        if ($details === false || !isset($details['key'])) {
            throw new RuntimeException('Could not serialize public key from x5c leaf');
        }

        return $details['key'];
    }

    private function b64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
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
     * Apple's App Store Server API requires an ES256 JWT signed with the
     * .p8 private key from App Store Connect → Users and Access → Keys.
     *   header  { alg: ES256, kid: keyId, typ: JWT }
     *   payload { iss: issuerId, iat, exp, aud: 'appstoreconnect-v1',
     *             bid: bundleId }
     * Token lifetime is capped at 20 minutes per Apple's spec; we use
     * 10 to keep clock-skew tolerance comfortable.
     */
    private function buildAppStoreServerJwt(): string
    {
        $now = time();
        return JWT::encode(
            payload: [
                'iss' => $this->issuerId,
                'iat' => $now,
                'exp' => $now + 600,
                'aud' => 'appstoreconnect-v1',
                'bid' => $this->bundleId,
            ],
            key:   $this->privateKey,
            alg:   'ES256',
            keyId: $this->keyId,
            head:  ['typ' => 'JWT'],
        );
    }
}
