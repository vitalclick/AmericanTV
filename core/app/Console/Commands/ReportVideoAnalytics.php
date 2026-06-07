<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\V1\AnalyticsController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

/**
 * Prints a per-video watch summary to stdout. Useful for:
 *  - admin one-off reporting from the shell.
 *  - cron jobs that feed an external dashboard (pipe to mailx / Slack).
 *
 *   php artisan analytics:video-summary 42
 *   php artisan analytics:video-summary 42 --days=7
 */
class ReportVideoAnalytics extends Command
{
    protected $signature = 'analytics:video-summary
        {video_id : Video id to report on}
        {--days=30 : Look-back window in days}';

    protected $description = 'Print watch-duration summary for one video.';

    public function handle(AnalyticsController $controller): int
    {
        $videoId = (int) $this->argument('video_id');
        $days    = (int) $this->option('days');

        // We deliberately pass through the controller so the bucketing logic
        // lives in exactly one place. Authorize as the video owner so the
        // owner guard inside the controller passes.
        $request = Request::create(
            "/api/v1/analytics/videos/$videoId/watch-summary",
            'GET',
            ['window_days' => $days],
        );
        $request->setUserResolver(fn () => \App\Models\Video::find($videoId)?->user);

        try {
            $response = $controller->videoWatchSummary($request, $videoId);
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $payload = $response->getData(true)['data'];

        $this->info("Video {$payload['video_id']} (last {$payload['window_days']}d):");
        $this->line("  sessions        = {$payload['sessions']}");
        $this->line("  completion_rate = " . number_format($payload['completion_rate'] * 100, 1) . '%');
        $this->line("  p50 watched     = {$payload['p50']}s");
        $this->line("  p90 watched     = {$payload['p90']}s");
        $this->newLine();
        $this->line('  dropoff:');
        foreach ($payload['dropoff'] as $bucket => $count) {
            $this->line(sprintf('    %-10s %d', $bucket, $count));
        }

        return self::SUCCESS;
    }
}
