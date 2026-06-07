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

            // Frontend / template surfaces (SiteController, Blade partials).
            // Maintenance off so public routes aren't 503'd by the
            // MaintenanceMode middleware. Multi-language off so views don't
            // chase a Language row that doesn't exist.
            'maintenance_mode' => false,
            'multi_language'   => false,
            'system_status'    => 1,
            'agree'            => false,
            'base_color'       => '5a5fcd',
            'secondary_color'  => 'a3a3c4',
            'input'            => (object) [],

            // Ad-network economics (AdvertiserController + AdsController +
            // PaymentController gateway-side ad purchase flow). Tests that
            // assert on dollar amounts flip these explicitly.
            'ad_config'            => (object) [],
            'ad_engagement'        => 0,
            'ad_reach'             => 0,
            'per_click_earn'       => 0,
            'per_click_spent'      => 0,
            'per_impression_spent' => 0,
        ];

        Cache::put('GeneralSetting', $defaults);
    }

    /**
     * Override gs() key(s) for the current test. Two shapes:
     *
     *   gsOverride('key', value)
     *   gsOverride(['key1' => v1, 'key2' => v2])
     *
     * Both mutate the cached GeneralSetting stub; setUp re-installs the
     * defaults for the next test, so overrides don't leak.
     */
    protected function gsOverride(string|array $keyOrMap, mixed $value = null): void
    {
        $current = Cache::get('GeneralSetting');
        if (! is_object($current)) {
            $current = (object) [];
        }

        $map = is_array($keyOrMap) ? $keyOrMap : [$keyOrMap => $value];
        foreach ($map as $key => $v) {
            $current->{$key} = $v;
        }
        Cache::put('GeneralSetting', $current);
    }

    /**
     * Set a value at a dotted path on the gs() stub. Builds the
     * intermediate stdClass / array tree on demand:
     *
     *   gsConfigOverride('mail_config.sendgrid.api_key', 'fake');
     *   gsConfigOverride('countries[0].code', 'US');
     *   gsConfigOverride('plans[0].features[2]', 'premium');
     *
     * Anything along the path that's the wrong shape gets replaced (object
     * where the path needs an array, or vice versa). Test author's intent
     * is "set this leaf", so preserving wrong-shape intermediates would
     * just surface confusing errors at the call site.
     */
    protected function gsConfigOverride(string $dottedPath, mixed $value): void
    {
        $current = Cache::get('GeneralSetting');
        if (! is_object($current)) {
            $current = (object) [];
        }

        $segments = $this->parsePath($dottedPath);
        $this->setAtPath($current, $segments, $value);
        Cache::put('GeneralSetting', $current);
    }

    /**
     * Parse 'a.b[0].c' into a sequence of segments, each tagged with the
     * shape expected at that position:
     *   - ['kind' => 'prop', 'name' => 'a']    -> object property access.
     *   - ['kind' => 'index', 'name' => 0]     -> array index.
     */
    private function parsePath(string $path): array
    {
        $segments = [];
        foreach (explode('.', $path) as $part) {
            if (! preg_match_all('/([A-Za-z0-9_]+)|\[(\d+)\]/', $part, $matches, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($matches as $m) {
                if ($m[1] !== '') {
                    $segments[] = ['kind' => 'prop', 'name' => $m[1]];
                } else {
                    $segments[] = ['kind' => 'index', 'name' => (int) $m[2]];
                }
            }
        }
        return $segments;
    }

    /**
     * Recursively walk $cursor along $segments and assign $value at the
     * leaf. The trick: PHP arrays are copy-on-write, so we can't return
     * an array "pointer" and expect mutations to propagate back. Instead
     * the recursion passes $cursor by reference at each level — works
     * uniformly for both stdClass (handle semantics) and array (cow).
     *
     * Build-the-next-container-on-demand logic is per segment kind:
     *   - prop segment expects $cursor to be an object.
     *   - index segment expects $cursor to be an array.
     * Anything that's the wrong shape gets replaced; the test author's
     * intent is "set this leaf", and silently dropping the call would be
     * worse than overwriting an intermediate.
     */
    private function setAtPath(object|array &$cursor, array $segments, mixed $value): void
    {
        if (empty($segments)) return;

        $seg = array_shift($segments);
        $isLast = empty($segments);

        if ($seg['kind'] === 'prop') {
            if (! is_object($cursor)) {
                $cursor = (object) [];
            }
            $name = $seg['name'];
            if ($isLast) {
                $cursor->{$name} = $value;
                return;
            }
            // Need to descend — ensure the next container shape matches
            // what the NEXT segment expects.
            $next = $segments[0];
            $expectsArray = $next['kind'] === 'index';
            if ($expectsArray) {
                if (! isset($cursor->{$name}) || ! is_array($cursor->{$name})) {
                    $cursor->{$name} = [];
                }
            } else {
                if (! isset($cursor->{$name}) || ! is_object($cursor->{$name})) {
                    $cursor->{$name} = (object) [];
                }
            }
            $this->setAtPath($cursor->{$name}, $segments, $value);
            return;
        }

        // index segment.
        if (! is_array($cursor)) {
            $cursor = [];
        }
        $idx = $seg['name'];
        if ($isLast) {
            $cursor[$idx] = $value;
            return;
        }
        $next = $segments[0];
        $expectsArray = $next['kind'] === 'index';
        if ($expectsArray) {
            if (! isset($cursor[$idx]) || ! is_array($cursor[$idx])) {
                $cursor[$idx] = [];
            }
        } else {
            if (! isset($cursor[$idx]) || ! is_object($cursor[$idx])) {
                $cursor[$idx] = (object) [];
            }
        }
        $this->setAtPath($cursor[$idx], $segments, $value);
    }
}
