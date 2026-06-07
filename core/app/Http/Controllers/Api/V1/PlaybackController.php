<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Impression;
use App\Models\Video;
use App\Models\WatchHistory;
use App\Services\Stream\CloudflareStreamService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class PlaybackController extends Controller
{
    public function source(Request $request, int $id): JsonResponse
    {
        $video = Video::with('videoFiles', 'storage', 'user')
            ->where('id', $id)
            ->published()
            ->public()
            ->regular()
            ->whereHas('user', fn (Builder $q) => $q->active())
            ->firstOrFail();

        if (!$video->showEligible()) {
            return response()->json([
                'message' => 'You need to purchase this video to watch it.',
                'price'   => (float) $video->price,
            ], 402);
        }

        $ttl = (int) config('stream.signed_url_ttl_seconds', 14400);

        // Cloudflare-migrated videos get a signed HLS URL.
        if ($video->stream_provider === 'cloudflare' && $video->stream_provider_id) {
            $hls = $this->cloudflareUrl($video->stream_provider_id, $ttl);
            return response()->json([
                'hls_url'         => $hls,
                'mp4_url'         => null,
                'mp4_sources'     => [],
                'poster'          => $this->posterUrl($video),
                'expires_at'      => now()->addSeconds($ttl)->toIso8601String(),
                'duration_seconds'=> null,
            ]);
        }

        // Fallback for the un-migrated library: surface the existing MP4
        // sources. The video-path route streams bytes through PHP — fine for
        // testing the mobile client end-to-end, but the HLS migration in
        // CloudflareStreamService is the real target.
        $sources = $video->videoFiles->map(function ($file) {
            return [
                'quality'   => $file->quality,
                'url'       => route('video.path', encrypt($file->id)),
                'mime_type' => 'video/mp4',
            ];
        })->values();

        return response()->json([
            'hls_url'         => null,
            'mp4_url'         => $sources->first()['url'] ?? null,
            'mp4_sources'     => $sources,
            'poster'          => $this->posterUrl($video),
            'expires_at'      => null,
            'duration_seconds'=> null,
        ]);
    }

    public function recordView(Request $request, int $id): JsonResponse
    {
        $video = Video::published()->public()->findOrFail($id);

        // Replace the web flow's session-based 20-minute dedupe with a cache
        // key. The web flow used the session because cookies are guaranteed
        // there; mobile is token-only.
        $cacheKey = sprintf(
            'view_dedupe:%s:%d',
            $request->user()?->id ?? 'anon-' . sha1($request->ip()),
            $video->id,
        );

        if (Cache::has($cacheKey)) {
            return response()->json([], 204);
        }

        Cache::put($cacheKey, true, Carbon::now()->addMinutes(20));

        $video->increment('views');

        Impression::create([
            'user_id'  => $video->user_id,
            'video_id' => $video->id,
        ]);

        if ($request->user()) {
            // Bubble the row back to "now" if it already exists so the Library
            // tab's history orders by most-recent watch.
            $history = WatchHistory::firstOrNew([
                'user_id'  => $request->user()->id,
                'video_id' => $video->id,
            ]);
            $history->last_view = Carbon::now();
            $history->save();
        }

        return response()->json([], 204);
    }

    private function cloudflareUrl(string $videoUid, int $ttlSeconds): string
    {
        $service = new CloudflareStreamService(
            accountId:     (string) config('stream.providers.cloudflare.account_id'),
            apiToken:      (string) config('stream.providers.cloudflare.api_token'),
            signingKeyId:  config('stream.providers.cloudflare.signing_key_id'),
            signingKeyPem: config('stream.providers.cloudflare.signing_key_pem'),
        );
        return $service->signedManifestUrl($videoUid, $ttlSeconds);
    }

    private function posterUrl(Video $video): ?string
    {
        if (!$video->thumb_image) {
            return null;
        }
        return asset(getFilePath('thumbnail') . '/' . $video->thumb_image);
    }
}
