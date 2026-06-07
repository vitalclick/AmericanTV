<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Daily 03:15 cleanup. Off-peak in PT-aligned timezones, no overlap with
// the existing cron jobs (CronController) which run on the hour.
Schedule::command('analytics:prune')
    ->dailyAt('03:15')
    ->withoutOverlapping()
    ->runInBackground();
