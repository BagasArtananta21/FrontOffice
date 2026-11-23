<?php

namespace Tests\Feature\Display;

use App\Models\DisplayDevice;
use App\Services\DisplayDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

class DisplayDeviceServiceTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    private function service(): DisplayDeviceService
    {
        return app(DisplayDeviceService::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function device(string $kodeOpd = 'UJI', bool $opdAktif = true, array $overrides = []): DisplayDevice
    {
        $opd = $this->makeOpd($kodeOpd, $opdAktif);
        $id = $this->insertDisplay($opd->id, 'token-'.$kodeOpd, $overrides);

        return DisplayDevice::withoutGlobalScope('opd')->findOrFail($id);
    }

    private function rawColumn(DisplayDevice $device, string $column): mixed
    {
        return DB::table('display_devices')->where('id', $device->id)->value($column);
    }

    public function test_a_new_pin_is_six_digits(): void
    {
        $pin = $this->service()->assignNewPin($this->device());

        $this->assertMatchesRegularExpression('/^\d{6}$/', $pin);
    }

    public function test_the_pin_is_stored_encrypted_and_can_be_read_back(): void
    {
        $device = $this->device();
        $pin = $this->service()->assignNewPin($device);

        $this->assertNotSame($pin, $this->rawColumn($device, 'pin'));
        $this->assertSame($pin, $device->fresh()->pin);
    }

    public function test_the_device_is_found_by_its_pin(): void
    {
        $device = $this->device();
        $pin = $this->service()->assignNewPin($device);

        $this->assertSame(DisplayDeviceService::hmac($pin), $this->rawColumn($device, 'pin_hmac'));
        $this->assertSame($device->id, $this->service()->findActiveByPin($pin)?->id);
    }

    public function test_a_wrong_or_empty_pin_finds_nothing(): void
    {
        $pin = $this->service()->assignNewPin($this->device());
        $wrong = $pin === '000000' ? '000001' : '000000';

        $this->assertNull($this->service()->findActiveByPin($wrong));
        $this->assertNull($this->service()->findActiveByPin(''));
        $this->assertNull($this->service()->findActiveByPin(null));
    }

    public function test_an_inactive_device_is_not_found(): void
    {
        $pin = $this->service()->assignNewPin($this->device(overrides: ['aktif' => false]));

        $this->assertNull($this->service()->findActiveByPin($pin));
    }

    public function test_a_device_of_an_inactive_opd_is_not_found(): void
    {
        $pin = $this->service()->assignNewPin($this->device(opdAktif: false));

        $this->assertNull($this->service()->findActiveByPin($pin));
    }

    public function test_a_new_pin_replaces_the_old_one_without_unpairing_the_pc(): void
    {
        $device = $this->device();
        $old = $this->service()->assignNewPin($device);
        $tokenHash = $this->rawColumn($device, 'token_hash');

        $new = $this->service()->assignNewPin($device->fresh());

        $this->assertNotSame($old, $new);
        $this->assertNull($this->service()->findActiveByPin($old));
        $this->assertSame($device->id, $this->service()->findActiveByPin($new)?->id);
        $this->assertSame($tokenHash, $this->rawColumn($device, 'token_hash'));
    }

    public function test_the_pin_never_appears_when_the_model_is_serialised(): void
    {
        $device = $this->device();
        $this->service()->assignNewPin($device);

        $array = $device->fresh()->toArray();

        $this->assertArrayNotHasKey('pin', $array);
        $this->assertArrayNotHasKey('pin_hmac', $array);
        $this->assertArrayNotHasKey('token_hash', $array);
    }

    public function test_provisioning_creates_one_active_unpaired_display_with_a_pin(): void
    {
        $opd = $this->makeOpd();
        app()->instance('current_opd_id', $opd->id);

        $device = $this->service()->provisionFor($opd);

        $row = DB::table('display_devices')->where('id', $device->id)->first();
        $this->assertSame($opd->id, $row->opd_id);
        $this->assertTrue((bool) $row->aktif);
        $this->assertNull($row->token_hash);
        $this->assertSame($device->id, $this->service()->findActiveByPin($device->pin)?->id);
    }

    public function test_an_opd_cannot_be_provisioned_twice(): void
    {
        $opd = $this->makeOpd();
        app()->instance('current_opd_id', $opd->id);
        $this->service()->provisionFor($opd);

        try {
            $this->service()->provisionFor($opd);
            $this->fail('Display kedua untuk OPD yang sama seharusnya ditolak.');
        } catch (QueryException $e) {
            $this->assertSame('23000', (string) $e->getCode());
        }

        $this->assertSame(1, DB::table('display_devices')->where('opd_id', $opd->id)->count());
    }

    public function test_pairing_an_unpaired_display_returns_a_token_whose_hash_is_stored(): void
    {
        $device = $this->device(overrides: ['token_hash' => null]);
        $pin = $this->service()->assignNewPin($device);

        $token = $this->service()->pair($pin);

        $this->assertNotNull($token);
        $this->assertSame(hash('sha256', $token), $this->rawColumn($device, 'token_hash'));
    }

    public function test_pairing_a_display_that_is_already_paired_returns_nothing(): void
    {
        $device = $this->device();
        $pin = $this->service()->assignNewPin($device);

        $this->assertNull($this->service()->pair($pin));
        $this->assertSame(hash('sha256', 'token-UJI'), $this->rawColumn($device, 'token_hash'));
    }

    public function test_unpairing_frees_the_display_for_its_pin(): void
    {
        $device = $this->device();
        app()->instance('current_opd_id', $device->opd_id);
        $pin = $this->service()->assignNewPin($device);

        $this->service()->unpair($device);

        $this->assertNull($this->rawColumn($device, 'token_hash'));
        $this->assertNull($this->rawColumn($device, 'terakhir_aktif'));
        $this->assertNotNull($this->service()->pair($pin));
    }

    public function test_unpairing_is_refused_while_a_guest_is_waiting_to_sign(): void
    {
        $device = $this->device();
        app()->instance('current_opd_id', $device->opd_id);
        $visit = $this->insertVisit($device->opd_id, now()->toDateTimeString());
        DB::table('display_devices')->where('id', $device->id)->update(['kunjungan_menunggu_id' => $visit]);

        try {
            $this->service()->unpair($device);
            $this->fail('Memutuskan display seharusnya ditolak selama ada tamu yang menunggu tanda tangan.');
        } catch (InvalidArgumentException) {
            $this->assertSame(hash('sha256', 'token-UJI'), $this->rawColumn($device, 'token_hash'));
        }
    }

    public function test_a_reload_request_is_recorded_without_touching_updated_at(): void
    {
        $device = $this->device(overrides: ['updated_at' => '2026-01-01 00:00:00']);
        app()->instance('current_opd_id', $device->opd_id);

        $this->service()->requestReload($device);

        $this->assertNotNull($this->rawColumn($device, 'muat_ulang_diminta_pada'));
        $this->assertSame('2026-01-01 00:00:00', $this->rawColumn($device, 'updated_at'));
    }

}
