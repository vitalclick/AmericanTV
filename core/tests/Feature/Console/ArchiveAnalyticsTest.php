<?php

namespace Tests\Feature\Console;

use Aws\CommandInterface;
use Aws\Result;
use Aws\S3\S3Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ArchiveAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! \Schema::hasTable('app_events')) {
            $this->markTestSkipped('app_events not in migrations.');
        }
    }

    public function test_writes_a_csv_containing_only_in_range_events(): void
    {
        $this->seedEvents();

        $this->artisan('analytics:archive --from=2026-02-01 --to=2026-04-01')
            ->expectsOutputToContain('Archiving 2 events')
            ->assertExitCode(0);

        $files = glob(storage_path('app/analytics-archive-20260201-20260401.csv'));
        $this->assertCount(1, $files);

        $content = file_get_contents($files[0]);
        $this->assertStringContainsString('feed_tile_tap', $content);
        $this->assertStringContainsString('paywall_impression', $content);
        $this->assertStringNotContainsString('out_of_range', $content);

        File::delete($files[0]);
    }

    public function test_gzip_flag_writes_a_gzipped_archive(): void
    {
        $this->seedEvents();

        $this->artisan('analytics:archive --from=2026-02-01 --to=2026-04-01 --gzip')
            ->assertExitCode(0);

        $files = glob(storage_path('app/analytics-archive-20260201-20260401.csv.gz'));
        $this->assertCount(1, $files);

        // Gzip magic header — bytes 0x1f 0x8b.
        $fh = fopen($files[0], 'rb');
        $magic = fread($fh, 2);
        fclose($fh);
        $this->assertSame("\x1f\x8b", $magic);

        // Round-trip decompression contains the same rows.
        $decompressed = gzdecode(file_get_contents($files[0]));
        $this->assertStringContainsString('feed_tile_tap', $decompressed);

        File::delete($files[0]);
    }

    public function test_then_prune_without_upload_target_is_a_no_op(): void
    {
        $this->seedEvents();

        $this->artisan('analytics:archive --since=365 --then-prune')
            ->expectsOutputToContain('Skipping --then-prune')
            ->assertExitCode(0);

        $this->assertSame(3, DB::table('app_events')->count());
    }

    public function test_upload_to_calls_putObject_then_prune_clears_archived_rows(): void
    {
        $this->seedEvents();

        // Capture the PutObject parameters so we can assert on them after.
        $captured = null;

        $mock = $this->createMock(S3Client::class);
        $mock->method('__call')->willReturnCallback(
            function (string $method, array $args) use (&$captured) {
                if ($method === 'putObject') {
                    $captured = $args[0]; // first arg is the params array.
                    return new Result(['ETag' => '"abc"']);
                }
                throw new \RuntimeException("Unexpected S3 call: $method");
            }
        );

        $this->app->instance(S3Client::class, $mock);

        $this->artisan(
            'analytics:archive '
            . '--from=2026-02-01 --to=2026-04-01 '
            . '--upload-to=s3://reporting/events/2026-q1/ '
            . '--then-prune'
        )->assertExitCode(0);

        $this->assertNotNull($captured, 'putObject was not called');
        $this->assertSame('reporting', $captured['Bucket']);
        $this->assertStringStartsWith('events/2026-q1/analytics-archive-', $captured['Key']);
        $this->assertStringEndsWith('.csv', $captured['Key']);
        $this->assertSame('text/csv', $captured['ContentType']);
        $this->assertFileExists($captured['SourceFile']);

        // The two in-range events should have been pruned; the out-of-range
        // row survives.
        $this->assertSame(1, DB::table('app_events')->count());
        $this->assertSame(
            'out_of_range',
            DB::table('app_events')->first()->name,
        );

        File::delete($captured['SourceFile']);
    }

    private function seedEvents(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-01T00:00:00Z'));

        DB::table('app_events')->insert([
            [
                'name'        => 'feed_tile_tap',
                'occurred_at' => '2026-02-15 00:00:00',
                'created_at'  => '2026-02-15 00:00:00',
            ],
            [
                'name'        => 'paywall_impression',
                'occurred_at' => '2026-03-10 00:00:00',
                'created_at'  => '2026-03-10 00:00:00',
            ],
            [
                'name'        => 'out_of_range',
                'occurred_at' => '2026-04-15 00:00:00',
                'created_at'  => '2026-04-15 00:00:00',
            ],
        ]);
    }
}
