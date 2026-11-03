<?php

namespace Tests\Feature\Display;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class StaleFormTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    private const NOW = '2026-10-15 08:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->travelTo(self::NOW);
        $this->withoutVite();
    }

    private function openFormSince(string $when): void
    {
        $this->updateDevice(['tampilkan_form' => true, 'updated_at' => $when]);
    }

    public function test_a_form_opened_yesterday_is_closed_when_the_display_polls(): void
    {
        $this->openFormSince('2026-10-14 16:00:00');

        $status = $this->asDisplay()->get(route('display.status'))->assertOk()->json();

        $this->assertFalse($status['tampilkan_form']);
        $this->assertDatabaseHas('display_devices', ['id' => $this->deviceId, 'tampilkan_form' => false]);
    }

    public function test_a_form_opened_today_stays_open_even_right_after_midnight(): void
    {
        $this->openFormSince('2026-10-15 00:00:00');

        $status = $this->asDisplay()->get(route('display.status'))->assertOk()->json();

        $this->assertTrue($status['tampilkan_form']);
        $this->assertDatabaseHas('display_devices', ['id' => $this->deviceId, 'tampilkan_form' => true]);
    }

    public function test_the_page_does_not_start_on_a_form_left_open_since_yesterday(): void
    {
        $this->openFormSince('2026-10-14 16:00:00');

        $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertViewHas('initialShowForm', false);
    }

    public function test_a_submit_on_a_form_left_open_since_yesterday_is_rejected(): void
    {
        $this->openFormSince('2026-10-14 16:00:00');

        $this->submitVisit()->assertRedirect(route('display.show'));

        $this->assertDatabaseCount('kunjungan', 0);
    }

    public function test_a_submit_on_a_form_opened_today_is_still_accepted(): void
    {
        $this->openFormSince('2026-10-15 07:00:00');

        $this->submitVisit()->assertRedirect(route('display.confirmation'));

        $this->assertDatabaseCount('kunjungan', 1);
    }
}
