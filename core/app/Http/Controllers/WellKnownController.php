<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves the .well-known files that drive iOS Universal Links and Android
 * App Links. Both must be available over HTTPS at the root domain, with
 * `Content-Type: application/json`, and **no redirects** (Apple's tooling
 * refuses to follow even a 301).
 *
 * iOS:    https://americantv.vip/.well-known/apple-app-site-association
 * Android: https://americantv.vip/.well-known/assetlinks.json
 *
 * The values returned here intentionally hardcode the Team ID + bundle IDs.
 * They're public information (anyone can inspect either file) so there's
 * no secret to gate them behind env config; doing so would just add a
 * deployment failure mode.
 */
class WellKnownController extends Controller
{
    /**
     * Apple App Site Association. The `paths` array tells iOS which URL
     * patterns on the current host should open the app instead of Safari.
     * We claim everything under /video and /channel; /admin and /api
     * stay in the browser.
     *
     * Team ID + bundle ID come from env so staging environments can serve
     * their own AASA (different Team / bundle) without a code change.
     */
    public function appleAppSiteAssociation(): Response
    {
        $appId = $this->iosAppId();

        $payload = [
            'applinks' => [
                'apps' => [],
                'details' => [
                    [
                        'appID' => $appId,
                        'paths' => [
                            '/video/*',
                            '/play/*',
                            '/short-play/*',
                            '/channel/*',
                            '/preview/channel/*',
                            '/preview/playlist/*',
                            '/preview/monthly-plan/*',
                            'NOT /admin/*',
                            'NOT /api/*',
                            'NOT /ipn/*',
                        ],
                    ],
                ],
            ],
            // webcredentials lets iOS suggest saved passwords on the
            // login screen if it has them for this host.
            'webcredentials' => [
                'apps' => [$appId],
            ],
        ];

        return response()
            ->json($payload, 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/json');
    }

    /**
     * Android Asset Links. Same idea, different shape. Verifying the SHA-256
     * of the upload keystore proves we own both the app and the domain.
     *
     * The fingerprint comes from:
     *   keytool -list -v -keystore americantv-release.keystore \
     *     -alias americantv | grep "SHA256:"
     *
     * Set it as `ANDROID_RELEASE_SHA256` in the Laravel .env so a key
     * rotation doesn't require a code deploy. Package name comes from
     * IOS / ANDROID_PACKAGE_NAME env so staging can ship its own.
     */
    public function androidAssetLinks(): Response
    {
        $fingerprint = (string) env(
            'ANDROID_RELEASE_SHA256',
            // The placeholder is intentional — without a real fingerprint
            // App Links won't verify, and we'd rather see a verification
            // failure than ship the wrong cert. Replace via .env.
            'REPLACE_WITH_KEYSTORE_SHA256_FINGERPRINT',
        );

        $payload = [
            [
                'relation' => [
                    'delegate_permission/common.handle_all_urls',
                ],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => $this->androidPackageName(),
                    'sha256_cert_fingerprints' => [$fingerprint],
                ],
            ],
        ];

        return response()
            ->json($payload, 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/json');
    }

    /**
     * Apple's `appID` field is `{TeamID}.{bundleID}`. Pulled from env so
     * staging can serve a staging Team / bundle. Defaults match
     * production so existing deploys without the env vars set keep
     * working unchanged.
     */
    private function iosAppId(): string
    {
        $teamId   = (string) env('IOS_APPLE_TEAM_ID', 'PDNU7JKBQZ');
        $bundleId = (string) env('IOS_BUNDLE_ID', 'com.americantv.userapp');
        return "{$teamId}.{$bundleId}";
    }

    private function androidPackageName(): string
    {
        return (string) env('ANDROID_PACKAGE_NAME', 'com.americantv.app');
    }
}
