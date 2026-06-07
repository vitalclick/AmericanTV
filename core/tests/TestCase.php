<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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
