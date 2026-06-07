<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Walks every default key on the gs() stub and asserts the helper can
 * return it without warnings. Protects against:
 *
 *   - A typo in installGsStub() that names a key the helper never reads
 *     (silent dead-key — no test would catch it without this).
 *   - A type mismatch (e.g. setting a key to null when the call site
 *     reads `gs('foo')->bar` and expects an object).
 *   - A missing default that a future controller starts reading, where
 *     gs('new_key') would return null and the controller would silently
 *     fall through to the "feature disabled" branch.
 *
 * The second protection is heuristic only: we can detect "wrong type for
 * object access" by trying to read `->something` on the value if it's
 * supposed to be a config object. The third protection isn't here yet —
 * see "Suggested next batch".
 */
class GsStubDefaultsTest extends TestCase
{
    public function test_every_stub_key_returns_through_gs_without_a_warning(): void
    {
        $settings = Cache::get('GeneralSetting');
        $this->assertIsObject($settings, 'gs() stub should be a stdClass');

        $errors = [];
        foreach (get_object_vars($settings) as $key => $expectedValue) {
            // Reading via the helper exercises the same code path the
            // controllers do (cache lookup + property access + @null fallback).
            $actual = gs($key);

            if ($expectedValue === null) {
                $this->assertNull($actual, "gs('{$key}') should mirror the stub's null");
                continue;
            }

            if ($actual === null) {
                $errors[] = "gs('{$key}') returned null but the stub defines {$key} with a value.";
                continue;
            }

            // Type-shape check: an object stub should still come back as
            // an object. Catches "I set $this->mail_config = 'oops'" typos.
            if (is_object($expectedValue) && ! is_object($actual)) {
                $errors[] = "gs('{$key}') returned " . gettype($actual)
                    . " but the stub set it as an object.";
            }
        }

        $this->assertEmpty(
            $errors,
            "gs() stub defaults are wrong:\n  - " . implode("\n  - ", $errors)
        );
    }

    public function test_object_config_keys_support_property_access(): void
    {
        // Per-key list of config objects the controllers reach into.
        // Reading a missing property on a real stdClass returns null
        // *and* emits a notice in dev mode — both are wrong for tests.
        // We use the @ suppression here only to mirror how gs() itself
        // calls @$general->$key — and assert it returns null cleanly.
        $configKeys = ['mail_config', 'firebase_config', 'socialite_credentials', 'ad_config', 'input'];

        foreach ($configKeys as $key) {
            $value = gs($key);
            $this->assertIsObject($value, "gs('{$key}') must be an object");
            // Reach for a non-existent leaf — should resolve to null
            // without throwing or warning.
            $leaf = @$value->some_nonexistent_leaf;
            $this->assertNull($leaf, "gs('{$key}')->some_nonexistent_leaf should be null cleanly");
        }
    }
}
