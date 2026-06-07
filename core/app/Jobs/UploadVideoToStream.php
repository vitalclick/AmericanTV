<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\Stream\CloudflareStreamService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Hands a newly-uploaded video file off to Cloudflare Stream. Runs after
 * VideoManager::mergeChunks has written the merged file to a temp path —
 * we copy/push the bytes to Stream, store the resulting UID, and let the
 * Stream webhook flip hls_status to "ready" when transcoding completes.
 *
 * Queued so the upload response doesn't block on the Cloudflare round-trip
 * (TUS or copy-from-URL can take 10+ seconds for large files).
 */
class UploadVideoToStream implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly int $videoId,
        public readonly string $localPath,
        public readonly bool $cleanupAfter = true,
    ) {}

    public function handle(CloudflareStreamService $service): void
    {
        $video = Video::find($this->videoId);
        if (! $video) {
            Log::warning('UploadVideoToStream: video disappeared', ['id' => $this->videoId]);
            $this->cleanup();
            return;
        }

        if (! File::exists($this->localPath)) {
            Log::warning('UploadVideoToStream: local file missing', ['path' => $this->localPath]);
            return;
        }

        if ($video->stream_provider_id) {
            // Already migrated — likely a retry after the webhook fired.
            $this->cleanup();
            return;
        }

        try {
            $uid = $service->uploadFile($this->localPath, [
                'name'     => $video->title ?: ('video-' . $video->id),
                'video_id' => (string) $video->id,
                'origin'   => 'upload',
            ]);
        } catch (\Throwable $e) {
            // Don't ditch the local copy on failure — the retry can use it.
            Log::error('UploadVideoToStream: push failed', [
                'video_id' => $video->id,
                'err'      => $e->getMessage(),
            ]);
            throw $e;
        }

        $video->stream_provider    = 'cloudflare';
        $video->stream_provider_id = $uid;
        $video->hls_status         = 2; // transcoding; webhook flips to 3.
        $video->save();

        $this->cleanup();
    }

    private function cleanup(): void
    {
        if ($this->cleanupAfter && File::exists($this->localPath)) {
            File::delete($this->localPath);
        }
    }
}
