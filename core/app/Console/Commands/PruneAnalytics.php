<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Drops app_events older than analytics.retention_days. Runs in batches so
 * a long-overdue prune doesn't single-shot delete millions of rows and
 * trigger replication lag.
 *
 *   php artisan analytics:prune
 *   php artisan analytics:prune --days=30 --dry-run
 */
class PruneAnalytics extends Command
{
    protected $signature = 'analytics:prune
        {--days= : Override config(analytics.retention_days)}
        {--batch=10000 : Rows per delete batch}
        {--dry-run : Report what would be deleted without touching the DB}';

    protected $description = 'Delete mobile-app engagement events older than the retention window.';

    public function handle(): int
    {
        $days  = (int) ($this->option('days') ?: config('analytics.retention_days', 90));
        $batch = max(100, (int) $this->option('batch'));
        $cutoff = Carbon::now()->subDays($days);

        $candidateCount = DB::table('app_events')
            ->where('occurred_at', '<', $cutoff)
            ->count();

        if ($candidateCount === 0) {
            $this->info('Nothing to prune.');
            return self::SUCCESS;
        }

        $this->info("{$candidateCount} events older than {$cutoff->toIso8601String()}.");

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $deleted = 0;
        do {
            $rows = DB::table('app_events')
                ->where('occurred_at', '<', $cutoff)
                ->limit($batch)
                ->delete();
            $deleted += $rows;
            if ($rows > 0) {
                $this->line("  - deleted {$rows} (running total {$deleted})");
            }
        } while ($rows >= $batch);

        $this->info("Done. Pruned {$deleted} events.");
        return self::SUCCESS;
    }
}
