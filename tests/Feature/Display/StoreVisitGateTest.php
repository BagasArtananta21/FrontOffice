<?php

namespace Tests\Feature\Display;

use App\Models\DisplayDevice;
use App\Models\Kunjungan;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StoreVisitGateTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-uji-perangkat-display';

    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private User $officer;

    private DisplayDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $opd = Opd::create(['kode_opd' => 'UJI', 'nama_opd' => 'OPD Uji']);
        app()->instance('current_opd_id', $opd->id);

        $this->officer = new User();
        $this->officer->forceFill([
            'name' => 'Petugas Uji',
            'email' => 'petugas@example.com',
            'password' => 'abcdefghijkl',
            'role' => 'admin_fo',
            'opd_id' => $opd->id,
            'aktif' => true,
            'terakhir_login_pada' => now(),
        ])->save();

        $this->device = new DisplayDevice();
        $this->device->forceFill([
            'nama' => 'PC Uji',
            'token_hash' => hash('sha256', self::TOKEN),
            'aktif' => true,
            'terakhir_aktif' => now(),
        ])->save();
    }

    private function submitVisit(): TestResponse
    {
        return $this->withCookie(DisplayDevice::COOKIE, self::TOKEN)
            ->post(route('display.kunjungan.store'), [
                'nama_tamu' => 'Budi Santoso',
                'jenis_kelamin' => 'laki_laki',
                'keperluan' => 'Konsultasi',
                'tanda_tangan' => self::PNG,
            ]);
    }

    public function test_submit_is_rejected_while_waiting_for_signature(): void
    {
        $waiting = new Kunjungan([
            'nama_tamu' => 'Tamu Menunggu',
            'jenis_kelamin' => 'laki_laki',
            'keperluan' => 'Tes',
        ]);
        $waiting->forceFill([
            'waktu_datang' => now(),
            'sumber_input' => Kunjungan::SUMBER_FRONT_OFFICE,
        ])->save();

        $this->device->forceFill(['kunjungan_menunggu_id' => $waiting->id])->save();

        $this->submitVisit()->assertRedirect(route('display.show'));

        $this->assertDatabaseCount('kunjungan', 1);
    }

    public function test_submit_is_accepted_without_the_officer_opening_the_form(): void
    {
        $this->submitVisit()->assertRedirect(route('display.confirmation'));

        $this->assertDatabaseCount('kunjungan', 1);
        $this->assertDatabaseHas('kunjungan', [
            'nama_tamu' => 'Budi Santoso',
            'sumber_input' => Kunjungan::SUMBER_DISPLAY,
            'dicatat_oleh' => $this->officer->id,
        ]);
    }

    /**
     * Form selalu terbuka (revisi 18 Nov): tidak ada lagi jendela "satu
     * kunjungan per Tampilkan form". Pembatas yang tersisa hanya throttle
     * display-submit (lihat StoreVisitSuccessTest).
     */
    public function test_successive_submits_each_create_a_visit(): void
    {
        $this->submitVisit()->assertRedirect(route('display.confirmation'));
        $this->submitVisit()->assertRedirect(route('display.confirmation'));

        $this->assertDatabaseCount('kunjungan', 2);
    }

    public function test_submit_is_accepted_again_once_the_waiting_request_is_released(): void
    {
        $waiting = new Kunjungan([
            'nama_tamu' => 'Tamu Menunggu',
            'jenis_kelamin' => 'laki_laki',
            'keperluan' => 'Tes',
        ]);
        $waiting->forceFill([
            'waktu_datang' => now(),
            'sumber_input' => Kunjungan::SUMBER_FRONT_OFFICE,
        ])->save();

        $this->device->forceFill(['kunjungan_menunggu_id' => $waiting->id])->save();
        $this->submitVisit()->assertRedirect(route('display.show'));

        $this->device->forceFill(['kunjungan_menunggu_id' => null])->save();
        $this->submitVisit()->assertRedirect(route('display.confirmation'));

        $this->assertDatabaseCount('kunjungan', 2);
    }
}
