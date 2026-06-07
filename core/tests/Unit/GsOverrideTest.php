<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Coverage for the gsOverride helper on TestCase. Both shapes need to
 * land in the same cache slot the gs() helper reads.
 */
class GsOverrideTest extends TestCase
{
    public function test_single_key_form_sets_one_property(): void
    {
        $this->gsOverride('secure_password', true);

        $settings = Cache::get('GeneralSetting');
        $this->assertTrue($settings->secure_password);
        // Other defaults should still be present.
        $this->assertTrue($settings->registration);
    }

    public function test_array_form_sets_multiple_properties_in_one_call(): void
    {
        $this->gsOverride([
            'secure_password' => true,
            'ev'              => true,
            'cur_text'        => 'EUR',
        ]);

        $settings = Cache::get('GeneralSetting');
        $this->assertTrue($settings->secure_password);
        $this->assertTrue($settings->ev);
        $this->assertSame('EUR', $settings->cur_text);
    }

    public function test_overrides_reset_between_tests(): void
    {
        // If the previous test's override of secure_password=true leaked,
        // this would fail. setUp re-installs the defaults so it shouldn't.
        $settings = Cache::get('GeneralSetting');
        $this->assertFalse(
            $settings->secure_password,
            'gsOverride leaked across tests — installGsStub() should reset defaults on every setUp.',
        );
    }
}
