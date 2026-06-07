<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Models\VideoFile;
use App\Services\Stream\CloudflareStreamService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Walks videos that haven't been migrated to Cloudflare Stream yet and pushes
 * their highest-quality MP4 to Stream via copy-from-URL.
 *
 * Run iteratively — each invocation processes one batch. Cloudflare rate-limits
 * the copy endpoint, so a single large run is more likely to fail than several
 * small ones spaced apart.
 *
 *   php artisan stream:backfill --limit=50 --dry-run
 */
class BackfillCloudflareStream extends Command
{
    protected $signature = 'stream:backfill
        {--limit=50 : Maximum number of videos to process this run}
        {--dry-run : List what would be uploaded without making API calls}';

    protected $description = 'Migrate unmigrated videos to Cloudflare Stream for HLS delivery.';

    public function handle(CloudflareStreamService $service): int
    {
        $accountId = config('stream.providers.cloudflare.account_id');
        $apiToken  = config('stream.providers.cloudflare.api_token');

        if (!$this->option('dry-run') && (!$accountId || !$apiToken)) {
            $this->error('CLOUDFLARE_ACCOUNT_ID and CLOUDFLARE_STREAM_API_TOKEN must be set.');
            return self::FAILURE;
        }

        $candidates = Video::query()
            ->whereNull('stream_provider_id')
            ->where('is_shorts_video', 0)
            ->with(['videoFiles' => fn ($q) => $q->orderByDesc('id')])
            ->limit((int) $this->option('limit'))
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('Nothing to do — all eligible videos already have a Stream UID.');
            return self::SUCCESS;
        }

        $this->info("Found {$candidates->count()} candidate(s).");

        $successes = 0;
        $failures  = 0;

        foreach ($candidates as $video) {
            /** @var VideoFile|null $best */
            $best = $video->videoFiles->first();
            if (!$best) {
                $this->warn("Skip {$video->id}: no VideoFile rows.");
                continue;
            }

            $sourceUrl = $this->publicSourceUrl($best);
            if (!$sourceUrl) {
                $this->warn("Skip {$video->id}: could not resolve a public URL for file {$best->id}.");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  - would upload video {$video->id} ({$video->title}) from {$sourceUrl}");
                continue;
            }

            try {
                $uid = $service->uploadFromUrl($sourceUrl, [
                    'name'      => $video->title,
                    'video_id'  => (string) $video->id,
                    'origin'    => 'backfill',
                ]);

                $video->stream_provider    = 'cloudflare';
                $video->stream_provider_id = $uid;
                $video->hls_status         = 2; // transcoding; the webhook will flip to 3 (ready).
                $video->save();

                $successes++;
                $this->line("  ✓ {$video->id} -> Stream UID {$uid}");
            } catch (\Throwable $e) {
                $failures++;
                Log::warning('Stream backfill failed', [
                    'video_id' => $video->id,
                    'err'      => $e->getMessage(),
                ]);
                $this->error("  ✗ {$video->id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done. {$successes} ok, {$failures} failed.");
        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Returns a publicly-reachable URL Cloudflare can pull from. For Wasabi /
     * S3-backed storage the existing storage layer should mint a presigned URL;
     * for local disk we synthesize an asset() URL (only usable when the Laravel
     * host is reachable from the internet).
     */
    private function publicSourceUrl(VideoFile $file): ?string
    {
        $video = $file->video;
        if (!$video) {
            return null;
        }

        // Reuses the existing helper that knows how to construct URLs against
        // each Storage backend (local, Wasabi, DigitalOcean Spaces, FTP).
        return getVideo($file->file_name, $video) ?: null;
    }
}
