<?php

namespace Tests\Feature\Guestbook;

use App\Exceptions\DisplayUnavailableException;
use App\Models\DisplayDevice;
use App\Services\KunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class SignatureRequestTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    private const NOW = '2026-10-15 09:30:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->travelTo(self::NOW);
        $this->actingAs($this->officer);
    }

    private function service(): KunjunganService
    {
        return app(KunjunganService::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function guestData(array $overrides = []): array
    {
        return [
            'nama_tamu' => 'Ni Made Ayu',
            'jenis_kelamin' => 'perempuan',
            'keperluan' => 'Mengurus surat keterangan',
            ...$overrides,
        ];
    }

    private function secondsAgo(int $seconds): string
    {
        return now()->subSeconds($seconds)->format('Y-m-d H:i:s');
    }

    public function test_it_creates_the_visit_at_once_and_points_the_display_at_it(): void
    {
        $bidangId = $this->insertBidang($this->opd->id, 'PKP');

        $visit = $this->service()->requestSignature($this->guestData([
            'no_hp' => '081234567890',
            'instansi_asal' => 'SMA Negeri 1',
            'alamat' => 'Jalan Veteran 1',
            'bidang_id' => $bidangId,
            'pegawai_tujuan' => 'Pak Wayan',
            'catatan_petugas' => 'Datang bersama rombongan',
        ]), $this->officer);

        $this->assertDatabaseHas('kunjungan', [
            'id' => $visit->id,
            'opd_id' => $this->opd->id,
            'nomor_kunjungan' => '20261015-0001',
            'nama_tamu' => 'Ni Made Ayu',
            'jenis_kelamin' => 'perempuan',
            'keperluan' => 'Mengurus surat keterangan',
            'no_hp' => '081234567890',
            'instansi_asal' => 'SMA Negeri 1',
            'alamat' => 'Jalan Veteran 1',
            'bidang_id' => $bidangId,
            'pegawai_tujuan' => 'Pak Wayan',
            'catatan_petugas' => 'Datang bersama rombongan',
            'sumber_input' => 'frontoffice',
            'dicatat_oleh' => $this->officer->id,
            'tanda_tangan' => null,
            'alasan_tanpa_ttd' => null,
            'waktu_datang' => self::NOW,
        ]);
        $this->assertSame($visit->id, $this->deviceRow()['kunjungan_menunggu_id']);
    }

    public function test_it_uses_the_arrival_time_the_officer_typed(): void
    {
        $visit = $this->service()->requestSignature($this->guestData(['waktu_datang' => '2026-10-15 08:45:00']), $this->officer);

        $this->assertSame('2026-10-15 08:45:00', $this->rawRow('kunjungan', $visit->id)['waktu_datang']);
        $this->assertSame('20261015-0001', $this->rawRow('kunjungan', $visit->id)['nomor_kunjungan']);
    }

    public function test_it_ignores_system_fields_passed_in_the_data(): void
    {
        $stranger = $this->makeUser($this->makeOpd('LAIN'));

        $visit = $this->service()->requestSignature($this->guestData([
            'tanda_tangan' => 'tanda-tangan/palsu.png',
            'sumber_input' => 'display',
            'dicatat_oleh' => $stranger->id,
            'alasan_tanpa_ttd' => 'tamu_menolak',
            'opd_id' => $stranger->opd_id,
            'nomor_kunjungan' => 'MANUAL-0001',
        ]), $this->officer);

        $row = $this->rawRow('kunjungan', $visit->id);
        $this->assertNull($row['tanda_tangan']);
        $this->assertSame('frontoffice', $row['sumber_input']);
        $this->assertSame($this->officer->id, $row['dicatat_oleh']);
        $this->assertNull($row['alasan_tanpa_ttd']);
        $this->assertSame($this->opd->id, $row['opd_id']);
        $this->assertSame('20261015-0001', $row['nomor_kunjungan']);
    }

    public static function unavailableStates(): array
    {
        return [
            'display not connected' => [['terakhir_aktif' => '2026-10-15 09:29:00']],
            'display never reported' => [['terakhir_aktif' => null]],
            'guest form is open' => [['tampilkan_form' => true]],
            'display deactivated' => [['aktif' => false]],
        ];
    }

    #[DataProvider('unavailableStates')]
    public function test_it_refuses_when_the_display_cannot_take_a_request_and_creates_nothing(array $deviceState): void
    {
        $this->updateDevice($deviceState);

        try {
            $this->service()->requestSignature($this->guestData(), $this->officer);
            $this->fail('Seharusnya melempar DisplayUnavailableException.');
        } catch (DisplayUnavailableException) {
            $this->assertSame(0, DB::table('kunjungan')->count());
            $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
            $this->assertNull(DB::table('nomor_counters')->first());
        }
    }

    public function test_it_refuses_a_second_request_while_one_is_waiting(): void
    {
        $first = $this->service()->requestSignature($this->guestData(), $this->officer);

        try {
            $this->service()->requestSignature($this->guestData(['nama_tamu' => 'Tamu Kedua']), $this->officer);
            $this->fail('Seharusnya melempar DisplayUnavailableException.');
        } catch (DisplayUnavailableException) {
            $this->assertSame(1, DB::table('kunjungan')->count());
            $this->assertSame($first->id, $this->deviceRow()['kunjungan_menunggu_id']);
        }
    }

    public function test_it_refuses_when_there_is_no_display_at_all(): void
    {
        DB::table('display_devices')->delete();

        $this->expectException(DisplayUnavailableException::class);

        $this->service()->requestSignature($this->guestData(), $this->officer);
    }

    public function test_it_never_uses_the_display_of_another_opd(): void
    {
        $this->updateDevice(['terakhir_aktif' => '2026-10-15 09:00:00']);
        $other = $this->makeOpd('LAIN');
        $otherDevice = $this->insertDisplay($other->id, 'token-lain', ['terakhir_aktif' => self::NOW]);

        try {
            $this->service()->requestSignature($this->guestData(), $this->officer);
            $this->fail('Seharusnya melempar DisplayUnavailableException.');
        } catch (DisplayUnavailableException) {
            $this->assertNull(DB::table('display_devices')->where('id', $otherDevice)->value('kunjungan_menunggu_id'));
            $this->assertSame(0, DB::table('kunjungan')->count());
        }
    }

    public function test_a_display_at_exactly_30_seconds_still_takes_a_request(): void
    {
        $this->updateDevice(['terakhir_aktif' => $this->secondsAgo(30)]);

        $visit = $this->service()->requestSignature($this->guestData(), $this->officer);

        $this->assertSame($visit->id, $this->deviceRow()['kunjungan_menunggu_id']);
    }

    public function test_the_display_signature_endpoint_stores_the_signature_for_the_waiting_visit(): void
    {
        $visit = $this->service()->requestSignature($this->guestData(), $this->officer);

        $this->submitSignature()->assertRedirect(route('display.confirmation'));

        $path = $this->rawRow('kunjungan', $visit->id)['tanda_tangan'];
        $this->assertMatchesRegularExpression('#^tanda-tangan/2026/10/[0-9a-f-]{36}\.png$#', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame(base64_decode(substr(self::PNG, strlen('data:image/png;base64,'))), Storage::disk('local')->get($path));
        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
        $this->assertNull($this->rawRow('kunjungan', $visit->id)['alasan_tanpa_ttd']);
        $this->assertSame('Ni Made Ayu', $this->rawRow('kunjungan', $visit->id)['nama_tamu']);
    }

    public function test_the_display_is_available_again_once_the_signature_is_in(): void
    {
        $this->service()->requestSignature($this->guestData(), $this->officer);
        $this->submitSignature();

        $this->assertTrue(DisplayDevice::availableForSignature()->whereKey($this->deviceId)->exists());
        $this->assertSame(1, DB::table('kunjungan')->count());
    }

    public function test_the_visit_comes_from_the_device_row_and_a_posted_visit_id_is_ignored(): void
    {
        $waiting = $this->service()->requestSignature($this->guestData(), $this->officer);
        $decoy = $this->insertVisit($this->opd->id, self::NOW, ['nama_tamu' => 'Tamu Lain']);

        $this->submitSignature(['kunjungan_id' => $decoy, 'id' => $decoy])->assertRedirect(route('display.confirmation'));

        $this->assertNotNull($this->rawRow('kunjungan', $waiting->id)['tanda_tangan']);
        $this->assertNull($this->rawRow('kunjungan', $decoy)['tanda_tangan']);
    }

    public function test_it_rejects_a_signature_when_nothing_is_waiting_and_stores_nothing(): void
    {
        $decoy = $this->insertVisit($this->opd->id, self::NOW);

        $this->submitSignature(['kunjungan_id' => $decoy])->assertRedirect(route('display.show'));

        $this->assertNull($this->rawRow('kunjungan', $decoy)['tanda_tangan']);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_second_signature_for_the_same_request_is_rejected(): void
    {
        $this->service()->requestSignature($this->guestData(), $this->officer);
        $this->submitSignature()->assertRedirect(route('display.confirmation'));

        $this->submitSignature()->assertRedirect(route('display.show'));

        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_an_invalid_signature_keeps_the_request_waiting(): void
    {
        $visit = $this->service()->requestSignature($this->guestData(), $this->officer);

        $this->submitSignature(['tanda_tangan' => 'data:image/jpeg;base64,AAAA'])->assertSessionHasErrors('tanda_tangan');

        $this->assertNull($this->rawRow('kunjungan', $visit->id)['tanda_tangan']);
        $this->assertSame($visit->id, $this->deviceRow()['kunjungan_menunggu_id']);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_missing_signature_is_refused_with_a_clear_message(): void
    {
        $visit = $this->service()->requestSignature($this->guestData(), $this->officer);

        $this->asDisplay()->post(route('display.signature.store'))
            ->assertSessionHasErrors(['tanda_tangan' => 'Tanda tangan wajib diisi.']);

        $this->assertSame($visit->id, $this->deviceRow()['kunjungan_menunggu_id']);
    }

    public function test_the_guest_form_cannot_be_submitted_while_a_signature_is_awaited(): void
    {
        $this->service()->requestSignature($this->guestData(), $this->officer);
        $this->showForm();

        $this->submitVisit()->assertRedirect(route('display.show'));

        $this->assertSame(1, DB::table('kunjungan')->count());
    }
}
