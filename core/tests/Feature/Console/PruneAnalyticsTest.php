<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PruneAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! \Schema::hasTable('app_events')) {
            $this->markTestSkipped('app_events not in migrations.');
        }
    }

    public function test_old_events_are_deleted_recent_ones_kept(): void
    {
        config(['analytics.retention_days' => 30]);

        DB::table('app_events')->insert([
            ['name' => 'old', 'occurred_at' => Carbon::now()->subDays(60), 'created_at' => Carbon::now()],
            ['name' => 'edge', 'occurred_at' => Carbon::now()->subDays(31), 'created_at' => Carbon::now()],
            ['name' => 'recent', 'occurred_at' => Carbon::now()->subDays(5), 'created_at' => Carbon::now()],
        ]);

        $this->artisan('analytics:prune')->assertExitCode(0);

        $names = DB::table('app_events')->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['recent'], $names);
    }

    public function test_dry_run_deletes_nothing(): void
    {
        config(['analytics.retention_days' => 7]);

        DB::table('app_events')->insert([
            ['name' => 'old', 'occurred_at' => Carbon::now()->subDays(30), 'created_at' => Carbon::now()],
        ]);

        $this->artisan('analytics:prune --dry-run')->assertExitCode(0);
        $this->assertSame(1, DB::table('app_events')->count());
    }
}
