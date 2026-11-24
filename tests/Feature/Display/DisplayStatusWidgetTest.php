<?php

namespace Tests\Feature\Display;

use App\Filament\Admin\Widgets\DisplayStatus;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class DisplayStatusWidgetTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->giveDevicePin();

        Filament::setCurrentPanel('admin');
        $this->actingAs($this->officer);
    }

    public function test_the_pin_is_not_sent_until_show_pin_is_opened(): void
    {
        Livewire::test(DisplayStatus::class)
            ->assertDontSee(self::DISPLAY_PIN)
            ->mountAction('showPin')
            ->assertActionMounted('showPin')
            ->assertMountedActionModalSee(self::DISPLAY_PIN);
    }

    public function test_an_unpaired_display_shows_the_pairing_steps_and_only_pin_actions(): void
    {
        $this->unpairDevice();

        Livewire::test(DisplayStatus::class)
            ->assertSee('Belum terpasang')
            ->assertSee(route('display.pair'))
            ->assertActionVisible('showPin')
            ->assertActionVisible('regeneratePin')
            ->assertActionHidden('unpair')
            ->assertActionHidden('reload');
    }

    public function test_a_paired_display_offers_unpair_and_reload(): void
    {
        Livewire::test(DisplayStatus::class)
            ->assertDontSee('Belum terpasang')
            ->assertActionVisible('unpair')
            ->assertActionVisible('reload');
    }

    public function test_unpair_frees_the_display(): void
    {
        Livewire::test(DisplayStatus::class)->callAction('unpair');

        $this->assertNull($this->deviceRow()['token_hash']);
    }

    public function test_unpair_is_refused_while_a_guest_is_waiting_to_sign(): void
    {
        $this->makeWaitingVisit();

        Livewire::test(DisplayStatus::class)
            ->callAction('unpair')
            ->assertDispatched('swal');

        $this->assertSame(hash('sha256', self::DISPLAY_TOKEN), $this->deviceRow()['token_hash']);
    }

    public function test_regenerate_pin_replaces_the_pin_and_keeps_the_display_paired(): void
    {
        $before = $this->deviceRow();

        Livewire::test(DisplayStatus::class)->callAction('regeneratePin');

        $after = $this->deviceRow();
        $this->assertNotSame($before['pin_hmac'], $after['pin_hmac']);
        $this->assertSame($before['token_hash'], $after['token_hash']);
    }

    public function test_reload_records_a_reload_request(): void
    {
        $this->assertNull($this->deviceRow()['muat_ulang_diminta_pada']);

        Livewire::test(DisplayStatus::class)->callAction('reload');

        $this->assertNotNull($this->deviceRow()['muat_ulang_diminta_pada']);
    }

    public function test_actions_never_touch_the_display_of_another_opd(): void
    {
        $other = $this->makeOpd('LAIN');
        $otherDevice = $this->insertDisplay($other->id, 'token-lain');
        $snapshot = $this->rawRow('display_devices', $otherDevice);

        Livewire::test(DisplayStatus::class)
            ->callAction('reload')
            ->callAction('regeneratePin')
            ->callAction('clearForm')
            ->callAction('unpair');

        $this->assertSame($snapshot, $this->rawRow('display_devices', $otherDevice));
        $this->assertNull(DB::table('display_devices')->where('id', $this->deviceId)->value('token_hash'));
    }

    public function test_clear_form_changes_the_key_the_display_polls(): void
    {
        $this->assertNull($this->asDisplay()->get(route('display.status'))->json('clear_form_key'));

        Livewire::test(DisplayStatus::class)->callAction('clearForm');

        $this->assertNotNull($this->asDisplay()->get(route('display.status'))->json('clear_form_key'));
    }

    public function test_clear_form_is_hidden_while_a_guest_is_waiting_to_sign(): void
    {
        $this->makeWaitingVisit();

        Livewire::test(DisplayStatus::class)->assertDontSee('Bersihkan formulir');
    }

}
