<?php

namespace Tests\Feature;

use Tests\TestCase;

class WellKnownTest extends TestCase
{
    public function test_apple_app_site_association_serves_application_json(): void
    {
        $response = $this->get('/.well-known/apple-app-site-association');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        // One-hour cache so a CDN can absorb request volume after deploy
        // without re-rotating Team IDs being indefinitely stuck.
        $response->assertHeader('Cache-Control', 'public, max-age=3600');
        // Apple's tooling refuses to follow redirects, so the legacy
        // root-relative path must also resolve directly (no 301).
        $rootCopy = $this->get('/apple-app-site-association');
        $rootCopy->assertOk();
    }

    public function test_android_asset_links_carries_a_cache_control_header(): void
    {
        $this->get('/.well-known/assetlinks.json')
            ->assertOk()
            ->assertHeader('Cache-Control', 'public, max-age=3600');
    }

    public function test_apple_app_site_association_carries_the_right_app_id(): void
    {
        $payload = $this->get('/.well-known/apple-app-site-association')->json();

        $this->assertSame(
            'PDNU7JKBQZ.com.americantv.userapp',
            $payload['applinks']['details'][0]['appID'],
        );
        // /admin and /api must stay out of the app to keep the admin
        // dashboard + the API surface in the browser.
        $paths = $payload['applinks']['details'][0]['paths'];
        $this->assertContains('NOT /admin/*', $paths);
        $this->assertContains('NOT /api/*', $paths);
    }

    public function test_android_asset_links_carries_the_right_package_name(): void
    {
        $payload = $this->get('/.well-known/assetlinks.json')->json();

        $this->assertSame(
            'com.americantv.app',
            $payload[0]['target']['package_name'],
        );
        $this->assertSame(
            'android_app',
            $payload[0]['target']['namespace'],
        );
        $this->assertContains(
            'delegate_permission/common.handle_all_urls',
            $payload[0]['relation'],
        );
    }

    public function test_android_asset_links_carries_the_fingerprint_from_env(): void
    {
        config(['app.placeholder' => 'TEST']);
        putenv('ANDROID_RELEASE_SHA256=AA:BB:CC:DD:EE:FF');

        $payload = $this->get('/.well-known/assetlinks.json')->json();
        $this->assertContains(
            'AA:BB:CC:DD:EE:FF',
            $payload[0]['target']['sha256_cert_fingerprints'],
        );
    }

    public function test_staging_env_can_override_team_and_bundle_ids(): void
    {
        // Staging serves its own AASA pointing at a staging Team + bundle,
        // so a tester walking through a staging URL doesn't accidentally
        // open the production app.
        putenv('IOS_APPLE_TEAM_ID=STAGING1234');
        putenv('IOS_BUNDLE_ID=com.americantv.userapp.staging');
        putenv('ANDROID_PACKAGE_NAME=com.americantv.app.staging');

        try {
            $apple = $this->get('/.well-known/apple-app-site-association')->json();
            $this->assertSame(
                'STAGING1234.com.americantv.userapp.staging',
                $apple['applinks']['details'][0]['appID'],
            );
            $this->assertSame(
                'STAGING1234.com.americantv.userapp.staging',
                $apple['webcredentials']['apps'][0],
            );

            $android = $this->get('/.well-known/assetlinks.json')->json();
            $this->assertSame(
                'com.americantv.app.staging',
                $android[0]['target']['package_name'],
            );
        } finally {
            // Reset so other tests in this file see production defaults.
            putenv('IOS_APPLE_TEAM_ID');
            putenv('IOS_BUNDLE_ID');
            putenv('ANDROID_PACKAGE_NAME');
        }
    }
}
