<?php

namespace Tests\Feature\Console;

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
