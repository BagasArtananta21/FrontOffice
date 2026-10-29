<?php

namespace Tests\Feature\Display;

use App\Models\DisplayDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class DisplayStateTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    private const NOW = '2026-10-15 10:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->travelTo(self::NOW);
        $this->withoutVite();
    }

    private function pollStatus(): array
    {
        return $this->asDisplay()->get(route('display.status'))->assertOk()->json();
    }

    private function device(): DisplayDevice
    {
        app()->instance('current_opd_id', $this->opd->id);

        return DisplayDevice::findOrFail($this->deviceId);
    }

    private function secondsAgo(int $seconds): string
    {
        return now()->subSeconds($seconds)->format('Y-m-d H:i:s');
    }

    public function test_status_reports_idle_when_the_form_is_not_requested(): void
    {
        $this->assertSame(['status' => 'ok', 'tampilkan_form' => false, 'waiting_key' => null], $this->pollStatus());
    }

    public function test_status_reports_the_form_when_the_officer_asked_for_it(): void
    {
        $this->showForm();

        $this->assertSame(['status' => 'ok', 'tampilkan_form' => true, 'waiting_key' => null], $this->pollStatus());
    }

    public function test_status_reports_a_waiting_key_while_a_signature_is_awaited(): void
    {
        $visitId = $this->makeWaitingVisit(['updated_at' => '2026-10-15 09:59:00']);

        $status = $this->pollStatus();

        $this->assertFalse($status['tampilkan_form']);
        $this->assertSame($visitId.'@'.strtotime('2026-10-15 09:59:00'), $status['waiting_key']);
    }

    public function test_the_waiting_key_changes_when_the_officer_edits_the_visit(): void
    {
        $this->makeWaitingVisit();
        $before = $this->pollStatus()['waiting_key'];

        $this->travel(10)->seconds();
        $this->device()->kunjunganMenunggu->forceFill(['nama_tamu' => 'Nama Dibetulkan'])->save();

        $this->assertNotSame($before, $this->pollStatus()['waiting_key']);
    }

    public function test_the_page_starts_on_idle_when_nothing_is_requested(): void
    {
        $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertViewHas('initialShowForm', false)
            ->assertViewHas('summary', null)
            ->assertViewHas('waitingKey', null);
    }

    public function test_the_page_starts_on_the_form_when_requested(): void
    {
        $this->showForm();

        $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertViewHas('initialShowForm', true)
            ->assertViewHas('summary', null);
    }

    public function test_the_page_shows_a_summary_without_phone_and_address_while_waiting_for_a_signature(): void
    {
        $this->makeWaitingVisit([
            'nama_tamu' => 'Ni Luh Sari',
            'instansi_asal' => 'SMA Negeri 1',
            'pegawai_tujuan' => 'Pak Wayan',
            'keperluan' => 'Mengambil dokumen',
            'no_hp' => '081234567890',
            'alamat' => 'Jalan Rahasia 99',
        ]);

        $response = $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary) => $summary === [
                'Nama' => 'Ni Luh Sari',
                'Instansi' => 'SMA Negeri 1',
                'Pegawai yang dituju' => 'Pak Wayan',
                'Keperluan' => 'Mengambil dokumen',
            ])
            ->assertSee('Ni Luh Sari')
            ->assertSee('Mengambil dokumen');

        $response->assertDontSee('081234567890')->assertDontSee('Jalan Rahasia 99');
    }

    public function test_a_waiting_request_wins_over_the_form_flag_on_the_page(): void
    {
        $this->showForm();
        $this->makeWaitingVisit();

        $this->asDisplay()->get(route('display.show'))
            ->assertViewHas('initialShowForm', true)
            ->assertViewHas('summary', fn (array $summary) => $summary['Nama'] === 'Tamu Menunggu');
    }

    public function test_polling_writes_terakhir_aktif_when_it_was_never_set(): void
    {
        $this->updateDevice(['terakhir_aktif' => null]);

        $this->pollStatus();

        $this->assertSame(self::NOW, $this->deviceRow()['terakhir_aktif']);
    }

    public static function pollingAges(): array
    {
        return [
            '1 second old' => [1, false],
            '9 seconds old' => [9, false],
            'exactly 10 seconds old' => [10, false],
            '11 seconds old' => [11, true],
            '5 minutes old' => [300, true],
        ];
    }

    #[DataProvider('pollingAges')]
    public function test_polling_writes_terakhir_aktif_only_when_it_is_older_than_10_seconds(int $age, bool $expectedWrite): void
    {
        $previous = $this->secondsAgo($age);
        $this->updateDevice(['terakhir_aktif' => $previous]);

        $this->pollStatus();

        $this->assertSame($expectedWrite ? self::NOW : $previous, $this->deviceRow()['terakhir_aktif']);
    }

    public function test_polling_does_not_touch_updated_at_of_the_device(): void
    {
        $this->updateDevice(['terakhir_aktif' => $this->secondsAgo(60), 'updated_at' => '2026-10-01 08:00:00']);

        $this->pollStatus();

        $this->assertSame(self::NOW, $this->deviceRow()['terakhir_aktif']);
        $this->assertSame('2026-10-01 08:00:00', $this->deviceRow()['updated_at']);
    }

    public static function connectionAges(): array
    {
        return [
            'just now' => [0, true],
            '29 seconds ago' => [29, true],
            'exactly 30 seconds ago' => [30, true],
            '31 seconds ago' => [31, false],
            '5 minutes ago' => [300, false],
        ];
    }

    #[DataProvider('connectionAges')]
    public function test_connection_state_flips_after_30_seconds(int $age, bool $expectedConnected): void
    {
        $this->updateDevice(['terakhir_aktif' => $this->secondsAgo($age)]);
        $device = $this->device();

        $this->assertSame(30, DisplayDevice::CONNECTED_WITHIN_SECONDS);
        $this->assertSame($expectedConnected, $device->isConnected());
        $this->assertSame($expectedConnected, DisplayDevice::connected()->whereKey($this->deviceId)->exists());
    }

    public function test_a_device_that_never_reported_is_not_connected(): void
    {
        $this->updateDevice(['terakhir_aktif' => null]);

        $this->assertFalse($this->device()->isConnected());
        $this->assertFalse(DisplayDevice::connected()->whereKey($this->deviceId)->exists());
    }

    public function test_only_a_connected_idle_active_device_is_available_for_a_signature(): void
    {
        app()->instance('current_opd_id', $this->opd->id);

        $this->assertTrue(DisplayDevice::availableForSignature()->whereKey($this->deviceId)->exists());
    }

    public static function unavailableStates(): array
    {
        return [
            'not connected' => [fn (self $test) => ['terakhir_aktif' => $test->secondsAgo(31)]],
            'never reported' => [fn () => ['terakhir_aktif' => null]],
            'deactivated' => [fn () => ['aktif' => false]],
            'the form is open' => [fn () => ['tampilkan_form' => true]],
        ];
    }

    #[DataProvider('unavailableStates')]
    public function test_a_device_in_another_state_is_not_available_for_a_signature(callable $changes): void
    {
        app()->instance('current_opd_id', $this->opd->id);
        $this->updateDevice($changes($this));

        $this->assertFalse(DisplayDevice::availableForSignature()->whereKey($this->deviceId)->exists());
    }

    public function test_a_device_already_waiting_for_a_signature_is_not_available(): void
    {
        app()->instance('current_opd_id', $this->opd->id);
        $this->makeWaitingVisit();

        $this->assertFalse(DisplayDevice::availableForSignature()->whereKey($this->deviceId)->exists());
    }

    public function test_the_available_scope_stays_inside_the_current_opd(): void
    {
        $other = $this->makeOpd('LAIN');
        $this->insertDisplay($other->id, 'token-lain');
        app()->instance('current_opd_id', $this->opd->id);

        $this->assertSame([$this->deviceId], DisplayDevice::availableForSignature()->pluck('id')->all());
    }

    public static function signatureAges(): array
    {
        return [
            'just created' => [0, false],
            '4 minutes 59 seconds' => [299, false],
            'exactly 5 minutes' => [300, false],
            '5 minutes 1 second' => [301, true],
        ];
    }

    #[DataProvider('signatureAges')]
    public function test_a_signature_request_expires_after_5_minutes_counted_from_the_visit_update(int $age, bool $expectedExpired): void
    {
        $this->makeWaitingVisit(['created_at' => $this->secondsAgo(3600), 'updated_at' => $this->secondsAgo($age)]);

        $this->assertSame(5, DisplayDevice::SIGNATURE_TIMEOUT_MINUTES);
        $this->assertSame($expectedExpired, $this->device()->signatureRequestExpired());
    }

    public function test_a_device_with_nothing_waiting_never_has_an_expired_request(): void
    {
        $this->assertFalse($this->device()->signatureRequestExpired());
        $this->assertNull($this->device()->signatureSecondsLeft());
        $this->assertNull($this->device()->waitingKey());
    }

    public function test_the_seconds_left_count_down_from_five_minutes_and_stop_at_zero(): void
    {
        $this->makeWaitingVisit(['updated_at' => $this->secondsAgo(100)]);
        $this->assertSame(200, $this->device()->signatureSecondsLeft());

        DB::table('kunjungan')->update(['updated_at' => $this->secondsAgo(900)]);
        $this->assertSame(0, $this->device()->signatureSecondsLeft());
    }
}
