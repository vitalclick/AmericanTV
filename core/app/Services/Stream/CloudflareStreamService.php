<?php

namespace App\Services\Stream;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper over the Cloudflare Stream API.
 *
 * The integration model: after the chunked upload merges into a single MP4 on
 * disk in mergeChunks(), we hand that file off to Stream via a single
 * "copy from URL" call (cheap, async) or a tus upload (for files > 200MB).
 * Stream transcodes to HLS, returns a video UID, and we store it on the Video
 * row. Webhooks notify us when the asset is ready.
 *
 * Delivery: we never expose the public Cloudflare URL. Instead we mint a
 * short-lived signed token per playback request — that's what /videos/{id}/source
 * returns to the mobile player.
 */
class CloudflareStreamService
{
    public function __construct(
        private readonly string $accountId,
        private readonly string $apiToken,
        private readonly ?string $signingKeyId = null,
        private readonly ?string $signingKeyPem = null,
    ) {}

    /**
     * Tell Cloudflare to pull the MP4 from a publicly-reachable URL.
     *
     * Best for files already in S3/Wasabi — generate a short-lived presigned URL
     * and pass it here. Returns the Stream video UID.
     */
    public function uploadFromUrl(string $sourceUrl, array $meta = []): string
    {
        $response = $this->client()
            ->post("/accounts/{$this->accountId}/stream/copy", [
                'url'  => $sourceUrl,
                'meta' => $meta,
            ]);

        $this->throwIfFailed($response, 'copy');

        return $response->json('result.uid');
    }

    /**
     * Direct upload from a local file. Use this from mergeChunks() if the
     * merged file is on the same host as the PHP process.
     *
     * For files larger than ~200MB, use createTusSession() instead and stream
     * the file in chunks rather than loading it in memory.
     */
    public function uploadFile(string $localPath, array $meta = []): string
    {
        $response = $this->client()
            ->attach('file', fopen($localPath, 'r'), basename($localPath))
            ->post("/accounts/{$this->accountId}/stream", [
                'meta' => json_encode($meta),
            ]);

        $this->throwIfFailed($response, 'upload');

        return $response->json('result.uid');
    }

    /**
     * Initiate a TUS resumable upload. Returns the endpoint URL the client (or
     * background worker) will PATCH chunks to.
     */
    public function createTusSession(int $sizeBytes, array $meta = []): string
    {
        $response = $this->client()
            ->withHeaders([
                'Tus-Resumable'   => '1.0.0',
                'Upload-Length'   => (string) $sizeBytes,
                'Upload-Metadata' => $this->encodeTusMetadata($meta),
            ])
            ->post("/accounts/{$this->accountId}/stream?direct_user=true");

        $this->throwIfFailed($response, 'tus');

        return $response->header('Location');
    }

    /**
     * Mint a signed playback URL for a single watch session.
     *
     * Returns the master HLS manifest URL with an embedded JWT good for the
     * requested duration. Mobile players consume this directly.
     */
    public function signedManifestUrl(string $videoUid, int $ttlSeconds = 14400): string
    {
        if (!$this->signingKeyId || !$this->signingKeyPem) {
            // Fallback to public delivery — fine for free content, NOT for paid.
            return "https://customer-{$this->accountId}.cloudflarestream.com/{$videoUid}/manifest/video.m3u8";
        }

        $token = $this->mintToken($videoUid, $ttlSeconds);
        return "https://customer-{$this->accountId}.cloudflarestream.com/{$token}/manifest/video.m3u8";
    }

    /**
     * Webhook verification: Stream signs each callback with a key from your
     * account. Compare HMAC-SHA256 over the raw body against the
     * Webhook-Signature header before trusting the payload.
     */
    public function verifyWebhookSignature(string $rawBody, string $signatureHeader, string $secret): bool
    {
        // Header format: "time=1234,sig1=abc..."
        $parts = collect(explode(',', $signatureHeader))
            ->mapWithKeys(fn ($p) => [explode('=', $p)[0] => explode('=', $p)[1] ?? '']);

        $expected = hash_hmac('sha256', ($parts['time'] ?? '') . '.' . $rawBody, $secret);
        return hash_equals($expected, $parts['sig1'] ?? '');
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl('https://api.cloudflare.com/client/v4')
            ->withToken($this->apiToken)
            ->acceptJson()
            ->timeout(30);
    }

    private function throwIfFailed($response, string $op): void
    {
        if ($response->failed()) {
            Log::error("Cloudflare Stream {$op} failed", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException("Cloudflare Stream {$op} failed: " . $response->status());
        }
    }

    private function encodeTusMetadata(array $meta): string
    {
        return collect($meta)
            ->map(fn ($v, $k) => $k . ' ' . base64_encode((string) $v))
            ->implode(',');
    }

    /**
     * Mint a Stream signed token. Production: sign an RS256 JWT with your
     * Stream signing key (kid = $this->signingKeyId, payload = { sub: $uid,
     * exp: time()+$ttl }). Use firebase/php-jwt or lcobucci/jwt.
     */
    private function mintToken(string $videoUid, int $ttlSeconds): string
    {
        // TODO(production): real JWT signing. Stubbed for skeleton.
        throw new \RuntimeException('Stream signed-token JWT signing not implemented in skeleton');
    }
}
