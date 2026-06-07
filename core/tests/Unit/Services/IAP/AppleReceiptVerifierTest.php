<?php

namespace Tests\Unit\Services\IAP;

use App\Services\IAP\AppleReceiptVerifier;
use App\Services\IAP\ReceiptVerificationResult;
use PHPUnit\Framework\TestCase;

class AppleReceiptVerifierTest extends TestCase
{
    public function test_rejects_a_malformed_jws(): void
    {
        $verifier = new AppleReceiptVerifier(
            bundleId: 'com.americantv',
            issuerId: 'issuer',
            keyId: 'key',
            privateKey: 'fake',
            environment: 'sandbox',
        );

        $result = $verifier->verify('not-a-jws');

        $this->assertInstanceOf(ReceiptVerificationResult::class, $result);
        $this->assertFalse($result->valid);
        $this->assertSame('Invalid signed transaction', $result->error);
    }

    public function test_rejects_a_jws_with_the_wrong_bundle_id(): void
    {
        $verifier = new AppleReceiptVerifier(
            bundleId: 'com.americantv',
            issuerId: 'issuer',
            keyId: 'key',
            privateKey: 'fake',
            environment: 'sandbox',
        );

        // Craft a JWS with a different bundleId. We skip signature checks
        // (the verifier intentionally doesn't validate signatures yet — see
        // its decodeJws TODO), so this exercises the claim-checking path
        // in isolation.
        $header  = $this->b64(['alg' => 'ES256', 'kid' => 'fake']);
        $payload = $this->b64([
            'bundleId'        => 'com.someone-else',
            'transactionId'   => 'abc123',
            'productId'       => 'plan_monthly',
            'purchaseDate'    => 1717777777000,
            'environment'     => 'Sandbox',
        ]);
        $jws = "$header.$payload.notasignature";

        $result = $verifier->verify($jws);

        $this->assertFalse($result->valid);
        $this->assertSame('Bundle ID mismatch', $result->error);
    }

    private function b64(array $data): string
    {
        return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
    }
}
