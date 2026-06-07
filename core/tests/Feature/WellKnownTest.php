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
        // Apple's tooling refuses to follow redirects, so the legacy
        // root-relative path must also resolve directly (no 301).
        $rootCopy = $this->get('/apple-app-site-association');
        $rootCopy->assertOk();
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
}
