<?php

namespace Tests\Unit\Services\IAP;

use App\Services\IAP\AppleReceiptVerifier;
use App\Services\IAP\ReceiptVerificationResult;
use PHPUnit\Framework\TestCase;

class AppleReceiptVerifierTest extends TestCase
{
    public function test_rejects_a_malformed_jws(): void
    {
        $result = $this->verifier()->verify('not-a-jws');

        $this->assertInstanceOf(ReceiptVerificationResult::class, $result);
        $this->assertFalse($result->valid);
        $this->assertSame('Invalid signed transaction', $result->error);
    }

    public function test_rejects_a_jws_with_no_x5c_in_the_header(): void
    {
        // alg correct, but no x5c chain -> we can't verify the signature.
        $header  = $this->b64(['alg' => 'ES256', 'typ' => 'JWT']);
        $payload = $this->b64(['transactionId' => '1']);
        $jws = "$header.$payload.notasignature";

        $result = $this->verifier()->verify($jws);

        $this->assertFalse($result->valid);
        $this->assertSame('Invalid signed transaction', $result->error);
    }

    public function test_rejects_a_jws_signed_with_a_self_generated_cert(): void
    {
        // Generate an ECDSA P-256 key + self-signed cert, sign a payload,
        // and confirm the verifier rejects it because the issuer claim or
        // signature won't match what we expect from the App Store. We
        // exercise the signature-verification path successfully; the claim
        // check (bundleId mismatch with config) is what kills it.
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name'       => 'prime256v1',
        ]);
        $this->assertNotFalse($key);

        $dn = ['CN' => 'fake-apple-leaf'];
        $csr  = openssl_csr_new($dn, $key, ['digest_alg' => 'sha256']);
        $cert = openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);
        openssl_x509_export($cert, $certPem);
        $der = base64_encode($this->pemToDer($certPem));

        $header  = $this->b64([
            'alg' => 'ES256',
            'typ' => 'JWT',
            'x5c' => [$der],
        ]);
        $payload = $this->b64([
            'bundleId'       => 'com.attacker',  // intentionally wrong.
            'transactionId'  => 'abc',
            'productId'      => 'plan_monthly',
            'purchaseDate'   => 1717777777000,
            'environment'    => 'Sandbox',
        ]);
        $signingInput = "$header.$payload";

        // Sign with our self-generated key — JWT format expects the raw r||s
        // ECDSA signature (not the OpenSSL DER), 64 bytes total.
        openssl_sign($signingInput, $derSig, $key, OPENSSL_ALGO_SHA256);
        $sig = $this->derSignatureToJoseR($derSig);

        $jws = "$signingInput." . rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

        $result = $this->verifier()->verify($jws);

        // Signature verifies (the cert is self-signed but we don't validate
        // the chain — see the security note in AppleReceiptVerifier). The
        // bundleId claim check is what rejects this payload.
        $this->assertFalse($result->valid);
        $this->assertSame('Bundle ID mismatch', $result->error);
    }

    private function verifier(): AppleReceiptVerifier
    {
        return new AppleReceiptVerifier(
            bundleId:   'com.americantv',
            issuerId:   'issuer',
            keyId:      'key',
            privateKey: 'fake',
            environment: 'sandbox',
        );
    }

    private function b64(array $data): string
    {
        return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
    }

    private function pemToDer(string $pem): string
    {
        $stripped = preg_replace('/-----[^-]+-----|\s+/', '', $pem);
        return base64_decode($stripped);
    }

    /**
     * Convert a DER-encoded ECDSA signature to the JOSE concat format
     * (raw r||s, each 32 bytes for P-256). firebase/php-jwt expects this.
     */
    private function derSignatureToJoseR(string $der): string
    {
        $pos = 2; // skip SEQUENCE tag + length.
        if (ord($der[1]) > 0x80) {
            $pos += (ord($der[1]) & 0x7f);
        }

        $parts = [];
        for ($i = 0; $i < 2; $i++) {
            $this->assertSame(0x02, ord($der[$pos])); // INTEGER tag.
            $pos++;
            $len = ord($der[$pos]);
            $pos++;
            $bytes = substr($der, $pos, $len);
            $pos += $len;
            $bytes = ltrim($bytes, "\x00"); // drop leading zero pad.
            $parts[] = str_pad($bytes, 32, "\x00", STR_PAD_LEFT);
        }

        return $parts[0] . $parts[1];
    }
}
