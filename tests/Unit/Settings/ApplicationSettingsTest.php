<?php

namespace Tests\Unit\Settings;

use App\Domain\Settings\ApplicationSettings;
use App\Models\ApplicationSetting;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_safe_defaults_are_used_when_no_settings_exist(): void
    {
        $settings = app(ApplicationSettings::class);

        $this->assertEquals(new DateTimeZone('Asia/Manila'), $settings->timezone());
        $this->assertSame(30, $settings->correctionWindowDays());
    }

    public function test_valid_database_overrides_are_returned(): void
    {
        ApplicationSetting::create(['key' => 'organization_timezone', 'value' => 'Asia/Tokyo']);
        ApplicationSetting::create(['key' => 'correction_window_days', 'value' => 45]);

        $settings = app(ApplicationSettings::class);

        $this->assertSame('Asia/Tokyo', $settings->timezone()->getName());
        $this->assertSame(45, $settings->correctionWindowDays());
    }

    public function test_invalid_database_values_fall_back_safely(): void
    {
        ApplicationSetting::create(['key' => 'organization_timezone', 'value' => 'Not/AZone']);
        ApplicationSetting::create(['key' => 'correction_window_days', 'value' => 0]);

        $settings = app(ApplicationSettings::class);

        $this->assertSame('Asia/Manila', $settings->timezone()->getName());
        $this->assertSame(30, $settings->correctionWindowDays());
    }
}
