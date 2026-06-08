<?php

/**
 * Config values consumed by App\Http\Controllers\WellKnownController.
 *
 * Why this file exists at all: env() called from a controller returns
 * null once `php artisan config:cache` has run, because the cached
 * config bakes env() calls in at cache time. Reading the same values
 * via config('well_known.*') keeps them resolvable across both cached
 * and uncached config states — env() runs here at config-load time
 * (or cache-build time) and the resulting strings are what the
 * controller actually sees.
 */

return [
    // SHA-256 fingerprint of the Android upload keystore. Powers
    // /.well-known/assetlinks.json. Rotate by updating
    // ANDROID_RELEASE_SHA256 in .env and re-running config:cache.
    'android_release_sha256' => env(
        'ANDROID_RELEASE_SHA256',
        'REPLACE_WITH_KEYSTORE_SHA256_FINGERPRINT',
    ),

    // Apple Team ID + bundle ID. Defaults match the production app so
    // existing deploys without explicit env entries keep working.
    'ios_team_id'   => env('IOS_APPLE_TEAM_ID', 'PDNU7JKBQZ'),
    'ios_bundle_id' => env('IOS_BUNDLE_ID', 'com.americantv.userapp'),

    'android_package_name' => env('ANDROID_PACKAGE_NAME', 'com.americantv.app'),
];
