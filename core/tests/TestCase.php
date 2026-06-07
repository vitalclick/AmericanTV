<?php

namespace Tests;

use Aws\S3\S3Client;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

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
        // That row isn't seeded in :memory: tests, so stub the global helper
        // to return safe defaults for every key the auth flow touches.
        if (!app()->bound('gs.test_stub_installed')) {
            $this->installGsStub();
            app()->instance('gs.test_stub_installed', true);
        }
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
     * The gs() helper reads from a cached GeneralSetting row; in test we
     * return a static stub so the AuthController doesn't blow up when the
     * row is absent.
     */
    private function installGsStub(): void
    {
        // No-op for now — the helper is forgiving; gs('foo') returns null
        // when the row is absent, which exercises the "feature disabled"
        // branch we want to test.
    }
}
