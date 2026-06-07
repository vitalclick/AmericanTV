<?php

namespace Tests;

use Aws\S3\S3Client;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    /**
     * Bindings we explicitly forget between tests so a mock from one test
     * can't leak into the next. Currently this is just S3Client, but the
     * list is meant to grow — keeping it as a const makes the audit trail
     * obvious whenever someone adds another container-resolved external.
     */
    private const TEST_ONLY_BINDINGS = [
        S3Client::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Most controllers call `gs(...)` to read the GeneralSetting singleton.
        // That row isn't seeded in :memory: tests, so we drop a stand-in into
        // the cache the helper actually reads (`Cache::get('GeneralSetting')`)
        // and let individual tests override specific keys via the same cache.
        $this->installGsStub();
    }

    protected function tearDown(): void
    {
        // Forget any test-only bindings the previous test may have left
        // behind. Without this a PHPUnit::createMock(S3Client::class) bound
        // via $this->app->instance() in test A would still resolve in test
        // B and silently absorb production-path PutObject calls.
        foreach (self::TEST_ONLY_BINDINGS as $abstract) {
            if (app()->bound($abstract)) {
                app()->forgetInstance($abstract);
            }
        }

        parent::tearDown();
    }

    protected function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    /**
     * The gs() helper reads `Cache::get('GeneralSetting')`. We populate that
     * cache slot with a stand-in stdClass whose properties cover every key
     * the api/v1 surface touches. Tests can override specific keys with
     * `gsOverride('secure_password', true)`.
     *
     * Without this stub, gs('foo') returned null for every key — which
     * silently exercised the "feature disabled" branch and made it
     * impossible to test the "enabled" branch without verbose per-test
     * Cache::put plumbing.
     */
    private function installGsStub(): void
    {
        $defaults = (object) [
            // Auth feature flags — false means "no enforcement", which is
            // the most permissive shape for a fresh test install.
            'registration'    => true,   // registration *allowed* by default.
            'secure_password' => false,
            'ev'              => false,  // email verification optional.
            'sv'              => false,  // mobile verification optional.
            'kv'              => false,  // KYC optional.

            // Display / formatting keys.
            'cur_text'        => 'USD',
            'site_name'       => 'TestTV',
            'cur_sym'         => '$',

            // FFmpeg gating in VideoManager — off in tests so transcoding
            // is skipped and we don't depend on a binary being installed.
            'ffmpeg_status'   => false,

            // Sale-side commission percentages, mirrored from production
            // defaults so transaction tests get plausible figures without
            // having to seed them per test.
            'video_sell_charge'    => 0,
            'playlist_sell_charge' => 0,
            'plan_sell_charge'     => 0,

            // Monetization gates (ManageVideoController / AdvertiserController).
            // 0 / 0 means there's no minimum threshold; tests that probe the
            // gate flip them via gsOverride().
            'minimum_subscribe'   => 0,
            'minimum_views'       => 0,
            'monetization_amount' => 0,
            'monetization_status' => 1, // monetization enabled by default.
            'is_monthly_subscription' => true,
            'is_playlist_sell'       => true,

            // Module switches admin views check before rendering.
            'ads_module'        => true,
            'system_customized' => false,
            'is_storage'        => false,
            'available_version' => '',

            // Notification channel toggles. False = channel disabled, so
            // tests don't accidentally fire emails / SMS to nowhere.
            'en' => false,   // email notifications.
            'sn' => false,   // sms notifications.
            'pn' => false,   // push notifications.

            // External configs the helpers reach for (returned as empty
            // objects so `gs('mail_config')->host` resolves without warnings).
            'mail_config'          => (object) [],
            'firebase_config'      => (object) [],
            'socialite_credentials' => (object) [],
        ];

        Cache::put('GeneralSetting', $defaults);
    }

    /**
     * Override one gs() key for the current test. Equivalent to mutating
     * the stub and re-putting it; tear-down restores the defaults via
     * setUp on the next test.
     */
    protected function gsOverride(string $key, mixed $value): void
    {
        $current = Cache::get('GeneralSetting');
        if (! is_object($current)) {
            $current = (object) [];
        }
        $current->{$key} = $value;
        Cache::put('GeneralSetting', $current);
    }
}
