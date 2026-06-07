<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Services\Stream\CloudflareStreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Stream notifies us when an upload finishes transcoding (or fails).
 * We use the callback to flip videos.hls_status so the player stops serving
 * the MP4 fallback once HLS is ready.
 *
 * Webhook signing key lives in stream.providers.cloudflare.webhook_secret.
 * Cloudflare signs the body with HMAC-SHA256 and includes the signature in
 * the Webhook-Signature header.
 */
class CloudflareStreamController extends Controller
{
    public function handle(Request $request, CloudflareStreamService $service): JsonResponse
    {
        $secret    = (string) config('stream.providers.cloudflare.webhook_secret');
        $signature = (string) $request->header('Webhook-Signature', '');

        if (!$secret || !$service->verifyWebhookSignature($request->getContent(), $signature, $secret)) {
            Log::warning('Cloudflare Stream webhook signature mismatch', [
                'sig' => substr($signature, 0, 32),
            ]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $uid   = (string) $request->input('uid');
        $state = (string) $request->input('status.state', '');

        if (!$uid) {
            return response()->json(['message' => 'Missing uid'], 422);
        }

        $video = Video::where('stream_provider_id', $uid)->first();
        if (!$video) {
            // Possible if Cloudflare delivers the webhook before the backfill
            // command persists the UID. Acknowledge anyway so we don't get
            // retried into oblivion.
            return response()->json([], 204);
        }

        $duration = (int) $request->input('duration', 0);

        $video->hls_status = match ($state) {
            'ready'    => 3,
            'error'    => 4,
            'pendingupload', 'queued', 'inprogress' => 2,
            default    => $video->hls_status,
        };

        if ($duration > 0 && empty($video->duration_seconds)) {
            $video->duration_seconds = $duration;
        }

        if ($state === 'ready') {
            $video->hls_manifest_url = sprintf(
                'https://customer-%s.cloudflarestream.com/%s/manifest/video.m3u8',
                config('stream.providers.cloudflare.account_id'),
                $uid,
            );
        }

        $video->save();

        return response()->json([], 204);
    }
}
