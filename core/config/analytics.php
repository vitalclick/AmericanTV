<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Analytics retention
    |--------------------------------------------------------------------------
    |
    | How many days of mobile-app engagement events (app_events) to keep
    | before `php artisan analytics:prune` deletes them. 90 days is enough
    | for week-over-week and quarter-over-quarter comparisons; longer is
    | usually a reporting-warehouse responsibility, not transactional DB.
    |
    */
    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 90),
];
