<?php

namespace Tests\Feature\Settings;

use App\Models\DisplayDevice;
use App\Services\SystemSettingService as Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsFixtures;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class DisplaySettingsTest extends TestCase
{
    use BuildsFixtures, InteractsWithDisplay, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpDisplay();
    }

    public function test_the_signature_timeout_follows_the_setting(): void
    {
        app(Settings::class)->update([Settings::SIGNATURE_TIMEOUT_MINUTES => 2]);
        $this->makeWaitingVisit(['updated_at' => now()->subSeconds(121)->format('Y-m-d H:i:s')]);

        app()->instance('current_opd_id', $this->opd->id);

        $device = DisplayDevice::findOrFail($this->deviceId);

        $this->assertTrue($device->signatureRequestExpired());
    }

    public function test_the_display_page_receives_the_idle_minutes(): void
    {
        app(Settings::class)->update([Settings::IDLE_SCREENSAVER_MINUTES => 7]);

        $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertViewHas('idleMinutes', 7);
    }

    public function test_the_confirmation_page_returns_after_the_configured_seconds(): void
    {
        app(Settings::class)->update([Settings::CONFIRMATION_SECONDS => 12]);

        $this->asDisplay()->get(route('display.confirmation'))
            ->assertOk()
            ->assertSee('content="12;url=', false)
            ->assertSee('animation-duration: 12s', false);
    }
}