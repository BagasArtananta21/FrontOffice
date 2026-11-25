<?php

namespace Tests\Feature\Display;

use App\Filament\Admin\Widgets\DisplayStatus;
use App\Models\Kunjungan;
use App\Services\SystemSettingService as Settings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

/**
 * The notice FO sees in the Status Display widget when a signature request is
 * released by the timeout (DisplayStatus::checkSignatureRequest()). The wording
 * carries the timeout in minutes, which comes from the system settings.
 */
class SignatureTimeoutNoticeTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    private const TITLE = 'Waktu Tanda Tangan Habis';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();

        Filament::setCurrentPanel('admin');
        $this->actingAs($this->officer);
    }

    /**
     * @return \Closure(string, array<int|string, mixed>): bool
     */
    private function infoNotice(string $textContains): \Closure
    {
        return function (string $event, array $params) use ($textContains): bool {
            $payload = $params[0] ?? $params;

            return ($payload['icon'] ?? null) === 'info'
                && ($payload['title'] ?? null) === self::TITLE
                && str_contains($payload['text'] ?? '', $textContains);
        };
    }

    public function test_a_timed_out_request_tells_officer_the_configured_minutes(): void
    {
        app(Settings::class)->update([Settings::SIGNATURE_TIMEOUT_MINUTES => 2]);
        $visitId = $this->makeWaitingVisit();

        $widget = Livewire::test(DisplayStatus::class)
            ->assertSet('watchedKunjunganId', $visitId);

        // Past the 2 minute limit, with the display still reporting in.
        $this->travel(3)->minutes();
        $this->updateDevice(['terakhir_aktif' => now()]);

        $widget->call('checkSignatureRequest')
            ->assertDispatched('swal', $this->infoNotice('Tamu Menunggu tidak menandatangani dalam 2 menit.'))
            ->assertSet('watchedKunjunganId', null);

        $this->assertSame(Kunjungan::REASON_TIMEOUT, $this->rawRow('kunjungan', $visitId)['alasan_tanpa_ttd']);
        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
    }

    public function test_a_request_released_while_the_display_is_disconnected_names_the_configured_minutes(): void
    {
        app(Settings::class)->update([Settings::SIGNATURE_TIMEOUT_MINUTES => 2]);
        $visitId = $this->makeWaitingVisit();

        $widget = Livewire::test(DisplayStatus::class);

        // terakhir_aktif is left behind, so the display counts as disconnected.
        $this->travel(3)->minutes();

        $widget->call('checkSignatureRequest')
            ->assertDispatched('swal', $this->infoNotice('Display terputus lebih dari 2 menit saat menunggu tanda tangan Tamu Menunggu.'));

        $this->assertSame(Kunjungan::REASON_DISPLAY_UNAVAILABLE, $this->rawRow('kunjungan', $visitId)['alasan_tanpa_ttd']);
    }

    public function test_the_minutes_in_the_notice_follow_a_changed_setting(): void
    {
        app(Settings::class)->update([Settings::SIGNATURE_TIMEOUT_MINUTES => 7]);
        $this->makeWaitingVisit();

        $widget = Livewire::test(DisplayStatus::class);

        $this->travel(8)->minutes();
        $this->updateDevice(['terakhir_aktif' => now()]);

        $widget->call('checkSignatureRequest')
            ->assertDispatched('swal', $this->infoNotice('dalam 7 menit.'));
    }

    public function test_a_request_that_is_still_within_the_limit_shows_no_notice(): void
    {
        app(Settings::class)->update([Settings::SIGNATURE_TIMEOUT_MINUTES => 2]);
        $visitId = $this->makeWaitingVisit();

        $widget = Livewire::test(DisplayStatus::class);

        $this->travel(1)->minutes();
        $this->updateDevice(['terakhir_aktif' => now()]);

        $widget->call('checkSignatureRequest')
            ->assertNotDispatched('swal')
            ->assertSet('watchedKunjunganId', $visitId);

        $this->assertNull($this->rawRow('kunjungan', $visitId)['alasan_tanpa_ttd']);
    }

    public function test_a_request_released_for_another_reason_shows_no_timeout_notice(): void
    {
        $visitId = $this->makeWaitingVisit();

        $widget = Livewire::test(DisplayStatus::class);

        // Released from elsewhere (for example another browser tab) as "guest left".
        $this->updateDevice(['kunjungan_menunggu_id' => null]);
        Kunjungan::query()->whereKey($visitId)->update(['alasan_tanpa_ttd' => Kunjungan::REASON_GUEST_LEFT]);

        $widget->call('checkSignatureRequest')
            ->assertNotDispatched('swal')
            ->assertSet('watchedKunjunganId', null);
    }
}
