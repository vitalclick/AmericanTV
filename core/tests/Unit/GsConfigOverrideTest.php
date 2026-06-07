<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GsConfigOverrideTest extends TestCase
{
    public function test_sets_a_value_two_levels_deep(): void
    {
        $this->gsConfigOverride('mail_config.sendgrid.api_key', 'SG-test');

        $settings = Cache::get('GeneralSetting');
        $this->assertSame('SG-test', $settings->mail_config->sendgrid->api_key);
    }

    public function test_preserves_other_keys_on_the_same_top_level_object(): void
    {
        // First call creates mail_config + sendgrid.api_key.
        $this->gsConfigOverride('mail_config.sendgrid.api_key', 'first');
        // Second call adds a sibling on sendgrid.
        $this->gsConfigOverride('mail_config.sendgrid.from', 'noreply@x');

        $settings = Cache::get('GeneralSetting');
        $this->assertSame('first', $settings->mail_config->sendgrid->api_key);
        $this->assertSame('noreply@x', $settings->mail_config->sendgrid->from);
    }

    public function test_replaces_a_non_object_along_the_path(): void
    {
        // Seed an intermediate node as a string. Override semantics:
        // overwrite, don't recursively convert.
        $this->gsOverride('mail_config', (object) ['legacy' => 'string-value-shouldnt-block']);
        $this->gsConfigOverride('mail_config.sendgrid.api_key', 'fake');

        $settings = Cache::get('GeneralSetting');
        $this->assertSame('fake', $settings->mail_config->sendgrid->api_key);
    }

    public function test_single_level_path_works_like_gsOverride(): void
    {
        $this->gsConfigOverride('cur_text', 'EUR');

        $settings = Cache::get('GeneralSetting');
        $this->assertSame('EUR', $settings->cur_text);
    }

    public function test_array_index_notation_writes_into_a_real_array(): void
    {
        $this->gsConfigOverride('countries[0].code', 'US');
        $this->gsConfigOverride('countries[1].code', 'CA');

        $settings = Cache::get('GeneralSetting');
        $this->assertIsArray($settings->countries);
        $this->assertSame('US', $settings->countries[0]->code);
        $this->assertSame('CA', $settings->countries[1]->code);
    }

    public function test_mixed_object_and_array_segments(): void
    {
        $this->gsConfigOverride('plans[0].features[2]', 'premium');

        $settings = Cache::get('GeneralSetting');
        $this->assertSame('premium', $settings->plans[0]->features[2]);
    }
}
