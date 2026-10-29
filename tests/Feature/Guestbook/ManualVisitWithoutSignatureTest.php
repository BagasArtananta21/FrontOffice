<?php

namespace Tests\Feature\Guestbook;

use App\Models\DisplayDevice;
use App\Models\Kunjungan;
use App\Services\KunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class ManualVisitWithoutSignatureTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->travelTo('2026-10-15 09:30:00');
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

    public static function allowedReasons(): array
    {
        return [
            'display unavailable' => [Kunjungan::REASON_DISPLAY_UNAVAILABLE, 'display_tidak_tersedia'],
            'guest left' => [Kunjungan::REASON_GUEST_LEFT, 'tamu_sudah_pergi'],
            'guest refused' => [Kunjungan::REASON_GUEST_REFUSED, 'tamu_menolak'],
        ];
    }

    #[DataProvider('allowedReasons')]
    public function test_it_records_the_visit_with_the_reason_and_no_signature(string $reason, string $expectedStored): void
    {
        $visit = $this->service()->recordWithoutSignature($this->guestData(['catatan_petugas' => 'Terburu-buru']), $this->officer, $reason);

        $this->assertDatabaseHas('kunjungan', [
            'id' => $visit->id,
            'opd_id' => $this->opd->id,
            'nomor_kunjungan' => '20261015-0001',
            'nama_tamu' => 'Ni Made Ayu',
            'catatan_petugas' => 'Terburu-buru',
            'alasan_tanpa_ttd' => $expectedStored,
            'tanda_tangan' => null,
            'sumber_input' => 'frontoffice',
            'dicatat_oleh' => $this->officer->id,
            'waktu_datang' => '2026-10-15 09:30:00',
        ]);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public static function unknownReasons(): array
    {
        return [
            'empty' => [''],
            'free text' => ['malas tanda tangan'],
            'wrong case' => ['TAMU_MENOLAK'],
            'label instead of key' => ['Tamu menolak'],
        ];
    }

    #[DataProvider('unknownReasons')]
    public function test_it_refuses_a_reason_it_does_not_know_and_stores_nothing(string $reason): void
    {
        try {
            $this->service()->recordWithoutSignature($this->guestData(), $this->officer, $reason);
            $this->fail('Seharusnya melempar InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString($reason, $e->getMessage());
            $this->assertSame(0, DB::table('kunjungan')->count());
        }
    }

    public function test_it_uses_the_arrival_time_the_officer_typed(): void
    {
        $visit = $this->service()->recordWithoutSignature(
            $this->guestData(['waktu_datang' => '2026-10-14 14:05:00']),
            $this->officer,
            Kunjungan::REASON_GUEST_LEFT,
        );

        $this->assertSame('2026-10-14 14:05:00', $this->rawRow('kunjungan', $visit->id)['waktu_datang']);
        $this->assertSame('20261014-0001', $this->rawRow('kunjungan', $visit->id)['nomor_kunjungan']);
    }

    public function test_it_ignores_system_fields_passed_in_the_data(): void
    {
        $stranger = $this->makeUser($this->makeOpd('LAIN'));

        $visit = $this->service()->recordWithoutSignature($this->guestData([
            'tanda_tangan' => 'tanda-tangan/palsu.png',
            'sumber_input' => 'display',
            'dicatat_oleh' => $stranger->id,
            'alasan_tanpa_ttd' => 'waktu_habis',
            'opd_id' => $stranger->opd_id,
            'nomor_kunjungan' => 'MANUAL-0001',
        ]), $this->officer, Kunjungan::REASON_GUEST_REFUSED);

        $row = $this->rawRow('kunjungan', $visit->id);
        $this->assertNull($row['tanda_tangan']);
        $this->assertSame('frontoffice', $row['sumber_input']);
        $this->assertSame($this->officer->id, $row['dicatat_oleh']);
        $this->assertSame('tamu_menolak', $row['alasan_tanpa_ttd']);
        $this->assertSame($this->opd->id, $row['opd_id']);
        $this->assertSame('20261015-0001', $row['nomor_kunjungan']);
    }

    public function test_it_does_not_touch_the_display(): void
    {
        $before = $this->deviceRow();

        $this->service()->recordWithoutSignature($this->guestData(), $this->officer, Kunjungan::REASON_GUEST_LEFT);

        $this->assertSame($before, $this->deviceRow());
    }

    public function test_manual_and_display_visits_share_one_monthly_sequence(): void
    {
        $this->service()->recordWithoutSignature($this->guestData(), $this->officer, Kunjungan::REASON_GUEST_LEFT);
        $this->showForm();
        $this->submitVisit()->assertRedirect(route('display.confirmation'));
        $this->service()->recordWithoutSignature($this->guestData(['nama_tamu' => 'Tamu Ketiga']), $this->officer, Kunjungan::REASON_GUEST_REFUSED);

        $this->assertSame(
            ['20261015-0001', '20261015-0002', '20261015-0003'],
            DB::table('kunjungan')->orderBy('nomor_kunjungan')->pluck('nomor_kunjungan')->all(),
        );
    }

    public function test_the_signature_status_label_follows_the_state_of_the_visit(): void
    {
        $signed = Kunjungan::withoutGlobalScope('opd')->find($this->insertVisit($this->opd->id, '2026-10-15 09:00:00', ['tanda_tangan' => 'tanda-tangan/2026/10/a.png']));
        $refused = Kunjungan::withoutGlobalScope('opd')->find($this->insertVisit($this->opd->id, '2026-10-15 09:00:00', ['alasan_tanpa_ttd' => 'tamu_menolak']));
        $timedOut = Kunjungan::withoutGlobalScope('opd')->find($this->insertVisit($this->opd->id, '2026-10-15 09:00:00', ['alasan_tanpa_ttd' => 'waktu_habis']));
        $nothing = Kunjungan::withoutGlobalScope('opd')->find($this->insertVisit($this->opd->id, '2026-10-15 09:00:00'));
        $waitingId = $this->makeWaitingVisit();
        $waiting = Kunjungan::withoutGlobalScope('opd')->withExists('displayMenunggu')->find($waitingId);

        $this->assertSame('Sudah', $signed->signatureStatusLabel());
        $this->assertSame('Tamu menolak', $refused->signatureStatusLabel());
        $this->assertSame('Tidak ditandatangani dalam batas waktu', $timedOut->signatureStatusLabel());
        $this->assertSame('Tidak Ada', $nothing->signatureStatusLabel());
        $this->assertSame('Menunggu', $waiting->signatureStatusLabel());
        $this->assertTrue($waiting->isWaitingForSignature());
        $this->assertFalse($signed->isWaitingForSignature());
    }

    public function test_a_signed_visit_is_never_reported_as_waiting_even_if_the_pointer_remains(): void
    {
        $visitId = $this->makeWaitingVisit(['tanda_tangan' => 'tanda-tangan/2026/10/a.png']);

        $visit = Kunjungan::withoutGlobalScope('opd')->find($visitId);

        $this->assertFalse($visit->isWaitingForSignature());
        $this->assertSame('Sudah', $visit->signatureStatusLabel());
        $this->assertNotNull(DisplayDevice::withoutGlobalScope('opd')->find($this->deviceId)->kunjungan_menunggu_id);
    }

    public function test_the_waiting_state_uses_the_preloaded_exists_attribute_without_extra_queries(): void
    {
        $this->makeWaitingVisit();
        $this->insertVisit($this->opd->id, '2026-10-15 09:00:00');
        $visits = Kunjungan::withoutGlobalScope('opd')->withExists('displayMenunggu')->get();

        DB::enableQueryLog();
        $labels = $visits->map->signatureStatusLabel()->sort()->values()->all();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame(['Menunggu', 'Tidak Ada'], $labels);
        $this->assertSame([], $queries);
    }
}
