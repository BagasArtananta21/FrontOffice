<?php

namespace Tests\Feature\Trigger;

use App\Models\Kunjungan;
use App\Models\Opd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AssertsTriggerErrors;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class VisitUpdateTriggerTest extends TestCase
{
    use AssertsTriggerErrors, BuildsFixtures, RefreshDatabase;

    private const LOCKED_MESSAGE = 'tidak boleh diubah';

    private const SIGNATURE_PATH = 'tanda-tangan/2026/10/a.png';

    private Opd $opd;

    private Opd $otherOpd;

    private string $userId;

    private string $otherUserId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = $this->makeOpd('UJI');
        $this->otherOpd = $this->makeOpd('LAIN');
        $this->userId = $this->makeUser($this->opd)->id;
        $this->otherUserId = $this->makeUser($this->opd)->id;
    }

    /**
     * A visit recorded by an officer, with a signature (source: frontoffice).
     */
    private function signedVisit(): string
    {
        return $this->insertVisit($this->opd->id, '2026-10-15 09:00:00', [
            'dicatat_oleh' => $this->userId,
            'tanda_tangan' => self::SIGNATURE_PATH,
        ]);
    }

    /**
     * A visit with neither a signature nor an officer, the way a display visit can start.
     */
    private function bareVisit(): string
    {
        return $this->insertVisit($this->opd->id, '2026-10-15 09:00:00', ['sumber_input' => 'display']);
    }

    private function updateVisit(string $id, array $changes): void
    {
        DB::table('kunjungan')->where('id', $id)->update($changes);
    }

    private function assertRejectedAndUnchanged(string $id, array $changes): void
    {
        $before = $this->rawRow('kunjungan', $id);

        $this->assertTriggerRejects(fn () => $this->updateVisit($id, $changes), self::LOCKED_MESSAGE);

        $this->assertSame($before, $this->rawRow('kunjungan', $id));
    }

    private function alternativeValueFor(string $column): string
    {
        return match ($column) {
            'nomor_kunjungan' => '20261015-0099',
            'opd_id' => $this->otherOpd->id,
            'dicatat_oleh' => $this->otherUserId,
            'sumber_input' => 'display',
            'waktu_datang' => '2026-10-14 08:30:00',
            'tanda_tangan' => 'tanda-tangan/2026/10/b.png',
        };
    }

    public static function lockedColumns(): array
    {
        return array_map(fn (string $column) => [$column], [
            'nomor_kunjungan',
            'opd_id',
            'dicatat_oleh',
            'sumber_input',
            'waktu_datang',
            'tanda_tangan',
        ]);
    }

    #[DataProvider('lockedColumns')]
    public function test_it_rejects_changing_a_locked_column(string $column): void
    {
        $id = $this->signedVisit();

        $this->assertRejectedAndUnchanged($id, [$column => $this->alternativeValueFor($column)]);
    }

    public function test_it_rejects_clearing_the_officer(): void
    {
        $id = $this->signedVisit();

        $this->assertRejectedAndUnchanged($id, ['dicatat_oleh' => null]);
    }

    public function test_it_rejects_moving_the_arrival_time_within_the_same_day(): void
    {
        $id = $this->signedVisit();

        $this->assertRejectedAndUnchanged($id, ['waktu_datang' => '2026-10-15 10:00:00']);
    }

    public function test_it_rejects_assigning_an_officer_to_a_visit_that_had_none(): void
    {
        $id = $this->bareVisit();

        $this->assertRejectedAndUnchanged($id, ['dicatat_oleh' => $this->userId]);
    }

    public function test_it_rejects_clearing_the_signature(): void
    {
        $id = $this->signedVisit();

        $this->assertRejectedAndUnchanged($id, ['tanda_tangan' => null]);
    }

    public function test_it_lets_the_signature_be_filled_once_from_null(): void
    {
        $id = $this->bareVisit();

        $this->updateVisit($id, ['tanda_tangan' => self::SIGNATURE_PATH]);

        $this->assertSame(self::SIGNATURE_PATH, $this->rawRow('kunjungan', $id)['tanda_tangan']);

        $this->assertRejectedAndUnchanged($id, ['tanda_tangan' => 'tanda-tangan/2026/10/b.png']);
        $this->assertRejectedAndUnchanged($id, ['tanda_tangan' => null]);

        $this->updateVisit($id, ['tanda_tangan' => self::SIGNATURE_PATH]);

        $this->assertSame(self::SIGNATURE_PATH, $this->rawRow('kunjungan', $id)['tanda_tangan']);
    }

    public function test_it_rejects_a_change_that_mixes_a_free_column_with_a_locked_one(): void
    {
        $id = $this->signedVisit();

        $this->assertRejectedAndUnchanged($id, [
            'nama_tamu' => 'Nama yang lain',
            'nomor_kunjungan' => '20261015-0099',
        ]);
    }

    public function test_it_allows_changing_every_other_column(): void
    {
        $id = $this->signedVisit();
        $bidangId = $this->insertBidang($this->opd->id, 'PKP');

        $changes = [
            'bidang_id' => $bidangId,
            'nama_tamu' => 'Nama yang lain',
            'jenis_kelamin' => 'perempuan',
            'instansi_asal' => 'Instansi yang lain',
            'no_hp' => '081234567890',
            'alamat' => 'Alamat yang lain',
            'pegawai_tujuan' => 'Pegawai yang lain',
            'keperluan' => 'Keperluan yang lain',
            'alasan_tanpa_ttd' => 'waktu_habis',
            'catatan_petugas' => 'Catatan yang lain',
            'updated_at' => '2026-10-20 12:00:00',
        ];

        $this->updateVisit($id, $changes);

        $row = $this->rawRow('kunjungan', $id);

        foreach ($changes as $column => $value) {
            $this->assertSame($value, $row[$column], "Kolom {$column} seharusnya bisa diubah.");
        }

        $this->assertSame('20261015-0001', $row['nomor_kunjungan']);
    }

    public function test_it_allows_recording_and_clearing_the_reason_without_a_signature(): void
    {
        $id = $this->bareVisit();

        $this->updateVisit($id, ['alasan_tanpa_ttd' => 'waktu_habis']);
        $this->assertSame('waktu_habis', $this->rawRow('kunjungan', $id)['alasan_tanpa_ttd']);

        $this->updateVisit($id, ['alasan_tanpa_ttd' => null]);
        $this->assertNull($this->rawRow('kunjungan', $id)['alasan_tanpa_ttd']);
    }

    public function test_writing_the_locked_values_again_changes_nothing(): void
    {
        $id = $this->signedVisit();
        $before = $this->rawRow('kunjungan', $id);

        $this->updateVisit($id, [
            'nomor_kunjungan' => $before['nomor_kunjungan'],
            'opd_id' => $before['opd_id'],
            'dicatat_oleh' => $before['dicatat_oleh'],
            'sumber_input' => $before['sumber_input'],
            'waktu_datang' => $before['waktu_datang'],
            'tanda_tangan' => $before['tanda_tangan'],
        ]);

        $this->assertSame($before, $this->rawRow('kunjungan', $id));
    }

    public function test_it_also_stops_a_change_saved_through_eloquent(): void
    {
        $id = $this->signedVisit();
        $visit = Kunjungan::query()->withoutGlobalScope('opd')->findOrFail($id);

        $visit->forceFill(['sumber_input' => 'display']);

        $this->assertTriggerRejects(fn () => $visit->save(), self::LOCKED_MESSAGE);

        $this->assertSame('frontoffice', $this->rawRow('kunjungan', $id)['sumber_input']);
    }
}
