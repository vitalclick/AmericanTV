<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Streams app_events from a date range into a CSV file and (optionally)
 * uploads it to S3 / Wasabi / any aws-sdk-compatible object store. The
 * companion to `analytics:prune` — lets prune run aggressively on the
 * transactional DB while the historical record survives elsewhere.
 *
 *   php artisan analytics:archive --from=2026-01-01 --to=2026-04-01
 *   php artisan analytics:archive --since=90 --upload-to=s3://reporting/events/
 *   php artisan analytics:archive --since=90 --upload-to=s3://reporting/events/ --then-prune
 *
 * If --upload-to is omitted the CSV stays on the local disk; the path is
 * printed for follow-up handling.
 */
class ArchiveAnalytics extends Command
{
    protected $signature = 'analytics:archive
        {--from= : Inclusive ISO date (e.g. 2026-01-01); paired with --to}
        {--to= : Exclusive ISO date (e.g. 2026-04-01); paired with --from}
        {--since= : Convenience: days-ago start, with end = now}
        {--upload-to= : s3://bucket/prefix/ — uploads the CSV with aws-sdk-php}
        {--then-prune : Delete rows from app_events after a successful upload}
        {--gzip : Write a .csv.gz instead of raw CSV (~5-10x smaller)}
        {--batch=5000 : Rows per stream batch}';

    protected $description = 'Stream historical app_events into CSV (+ optional S3 upload).';

    public function handle(): int
    {
        $batch = max(500, (int) $this->option('batch'));
        [$from, $to] = $this->resolveRange();

        $expectedRows = DB::table('app_events')
            ->whereBetween('occurred_at', [$from, $to])
            ->count();

        if ($expectedRows === 0) {
            $this->info('No events in range.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Archiving %s events from %s to %s.',
            number_format($expectedRows),
            $from->toDateString(),
            $to->toDateString(),
        ));

        $localPath = $this->writeCsv($from, $to, $batch);

        $uploadTarget = $this->option('upload-to');
        if ($uploadTarget) {
            $this->uploadToS3($localPath, $uploadTarget);
        } else {
            $this->info("CSV at: {$localPath}");
        }

        if ($this->option('then-prune')) {
            if (! $uploadTarget) {
                $this->warn('Skipping --then-prune: no remote target verified.');
            } else {
                $deleted = DB::table('app_events')
                    ->whereBetween('occurred_at', [$from, $to])
                    ->delete();
                $this->info("Pruned {$deleted} archived rows.");
            }
        }

        return self::SUCCESS;
    }

    private function writeCsv(Carbon $from, Carbon $to, int $batch): string
    {
        $stamp = $from->format('Ymd') . '-' . $to->format('Ymd');
        $gzip = (bool) $this->option('gzip');
        $extension = $gzip ? '.csv.gz' : '.csv';
        $localPath = storage_path("app/analytics-archive-{$stamp}{$extension}");

        // gzopen + gzwrite mirror the fopen/fputcsv contract but with on-the-
        // fly compression. We hand-format CSV rows so we can flow through
        // either handle uniformly — fputcsv only accepts a real file handle.
        $fh = $gzip ? gzopen($localPath, 'wb6') : fopen($localPath, 'w');
        $write = function (array $row) use ($fh, $gzip) {
            $line = implode(',', array_map($this->csvEscape(...), $row)) . "\n";
            $gzip ? gzwrite($fh, $line) : fwrite($fh, $line);
        };

        $write(['id', 'user_id', 'name', 'platform', 'session_id', 'video_id', 'payload', 'occurred_at']);

        $bar = $this->output->createProgressBar();
        $bar->start();

        $lastId = 0;
        do {
            $rows = DB::table('app_events')
                ->whereBetween('occurred_at', [$from, $to])
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batch)
                ->get();

            foreach ($rows as $row) {
                $write([
                    $row->id,
                    $row->user_id,
                    $row->name,
                    $row->platform,
                    $row->session_id,
                    $row->video_id,
                    $row->payload,
                    $row->occurred_at,
                ]);
                $lastId = $row->id;
            }
            $bar->advance(count($rows));
        } while ($rows->count() > 0);

        $bar->finish();
        $this->newLine();
        $gzip ? gzclose($fh) : fclose($fh);
        return $localPath;
    }

    /**
     * Minimal RFC-4180 CSV escaping: quote when the field contains a
     * delimiter, double-quote, CR, or LF; double internal quotes.
     */
    private function csvEscape(mixed $value): string
    {
        $s = (string) ($value ?? '');
        if ($s === '') return '';
        if (preg_match('/[",\r\n]/', $s)) {
            return '"' . str_replace('"', '""', $s) . '"';
        }
        return $s;
    }

    private function uploadToS3(string $localPath, string $target): void
    {
        // Parse s3://bucket/prefix/
        if (! preg_match('#^s3://([^/]+)/(.*)$#', $target, $m)) {
            $this->error("--upload-to must look like s3://bucket/prefix/");
            return;
        }
        [$_, $bucket, $prefix] = $m;
        $key = trim($prefix, '/') . '/' . basename($localPath);

        // Resolve via the container so tests can bind a mock S3Client
        // without us having to dependency-inject through the artisan
        // signature.
        $client = Container::getInstance()->has(S3Client::class)
            ? Container::getInstance()->make(S3Client::class)
            : new S3Client([
                'version'     => 'latest',
                'region'      => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
                'endpoint'    => env('AWS_ENDPOINT_URL'), // null for AWS S3, set for Wasabi / DO Spaces.
            ]);

        try {
            $client->putObject([
                'Bucket'     => $bucket,
                'Key'        => $key,
                'SourceFile' => $localPath,
                'ContentType' => 'text/csv',
            ]);
            $this->info("Uploaded to s3://{$bucket}/{$key}");
        } catch (\Throwable $e) {
            $this->error("S3 upload failed: " . $e->getMessage());
            // Leave the local file so the operator can retry the upload by
            // hand without re-streaming millions of rows from the DB.
            throw $e;
        }
    }

    private function resolveRange(): array
    {
        if ($since = $this->option('since')) {
            return [Carbon::now()->subDays((int) $since), Carbon::now()];
        }
        $from = $this->option('from');
        $to   = $this->option('to');
        if (! $from || ! $to) {
            $this->error('Provide either --since=N OR both --from + --to.');
            exit(self::FAILURE);
        }
        return [Carbon::parse($from), Carbon::parse($to)];
    }
}
