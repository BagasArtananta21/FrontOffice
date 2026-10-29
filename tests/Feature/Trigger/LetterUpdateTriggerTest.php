<?php

namespace Tests\Feature\Trigger;

use App\Models\NomorSurat;
use App\Models\Opd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AssertsTriggerErrors;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class LetterUpdateTriggerTest extends TestCase
{
    use AssertsTriggerErrors, BuildsFixtures, RefreshDatabase;

    private const LOCKED_MESSAGE = 'Nomor surat tidak boleh diubah';

    private Opd $opd;

    private Opd $otherOpd;

    private string $userId;

    private string $otherUserId;

    private string $formatId;

    private string $otherFormatId;

    private string $bidangId;

    private string $otherBidangId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = $this->makeOpd('UJI');
        $this->otherOpd = $this->makeOpd('LAIN');
        $this->userId = $this->makeUser(null, 'super_admin')->id;
        $this->otherUserId = $this->makeUser(null, 'super_admin')->id;
        $this->formatId = $this->insertFormat($this->opd->id, $this->userId);
        $this->otherFormatId = $this->insertFormat($this->opd->id, $this->userId, '2026-06-01');
        $this->bidangId = $this->insertBidang($this->opd->id, 'PKP');
        $this->otherBidangId = $this->insertBidang($this->opd->id, 'SDP');
    }

    /**
     * A letter with every optional column filled in, the way the service leaves it.
     *
     * @param  array<string, mixed>  $extra
     */
    private function fullLetter(array $extra = []): string
    {
        return $this->insertLetter([
            'opd_id' => $this->opd->id,
            'format_nomor_surat_id' => $this->formatId,
            'bidang_id' => $this->bidangId,
            'tujuan_surat' => 'Dinas Pendidikan',
            'nama_peminta' => 'Ibu Sekretaris',
            'nomor_lengkap' => '443.32/1/PKP/UJI/X/2026',
            'dibuat_oleh' => $this->userId,
            ...$extra,
        ]);
    }

    /**
     * A letter whose optional columns are all NULL (nomor_lengkap is still unset).
     */
    private function bareLetter(): string
    {
        return $this->insertLetter([
            'opd_id' => $this->opd->id,
            'format_nomor_surat_id' => $this->formatId,
            'dibuat_oleh' => $this->userId,
        ]);
    }

    private function updateLetter(string $id, array $changes): void
    {
        DB::table('nomor_surat')->where('id', $id)->update($changes);
    }

    private function cancelFields(): array
    {
        return [
            'status' => 'batal',
            'alasan_batal' => 'Salah ketik perihal',
            'dibatalkan_oleh' => $this->otherUserId,
            'batal_pada' => '2026-10-16 10:00:00',
        ];
    }

    private function cancelled(): string
    {
        $id = $this->fullLetter();
        $this->updateLetter($id, $this->cancelFields());

        return $id;
    }

    private function assertRejectedAndUnchanged(string $id, array $changes, string $message): void
    {
        $before = $this->rawRow('nomor_surat', $id);

        $this->assertTriggerRejects(fn () => $this->updateLetter($id, $changes), $message);

        $this->assertSame($before, $this->rawRow('nomor_surat', $id));
    }

    private function alternativeValueFor(string $column): mixed
    {
        return match ($column) {
            'opd_id' => $this->otherOpd->id,
            'format_nomor_surat_id' => $this->otherFormatId,
            'bidang_id' => $this->otherBidangId,
            'tahun' => 2027,
            'nomor_urut' => 99,
            'sub_nomor' => 5,
            'kode_klasifikasi' => '800',
            'perihal' => 'Perihal yang lain',
            'tujuan_surat' => 'Tujuan yang lain',
            'nama_peminta' => 'Peminta yang lain',
            'tanggal_surat' => '2026-10-14',
            'tanggal_terbit' => '2026-10-16 09:00:00',
            'dibuat_oleh' => $this->otherUserId,
            'nomor_lengkap' => '443.32/9/SDP/UJI/X/2026',
        };
    }

    public static function lockedColumns(): array
    {
        return array_map(fn (string $column) => [$column], [
            'opd_id',
            'format_nomor_surat_id',
            'bidang_id',
            'tahun',
            'nomor_urut',
            'sub_nomor',
            'kode_klasifikasi',
            'perihal',
            'tujuan_surat',
            'nama_peminta',
            'tanggal_surat',
            'tanggal_terbit',
            'dibuat_oleh',
            'nomor_lengkap',
        ]);
    }

    public static function nullableLockedColumns(): array
    {
        return [['bidang_id'], ['tujuan_surat'], ['nama_peminta']];
    }

    #[DataProvider('lockedColumns')]
    public function test_it_rejects_changing_a_locked_column(string $column): void
    {
        $id = $this->fullLetter();

        $this->assertRejectedAndUnchanged($id, [$column => $this->alternativeValueFor($column)], self::LOCKED_MESSAGE);
    }

    #[DataProvider('nullableLockedColumns')]
    public function test_it_rejects_clearing_a_locked_column(string $column): void
    {
        $id = $this->fullLetter();

        $this->assertRejectedAndUnchanged($id, [$column => null], self::LOCKED_MESSAGE);
    }

    #[DataProvider('nullableLockedColumns')]
    public function test_it_rejects_filling_a_locked_column_that_was_null(string $column): void
    {
        $id = $this->bareLetter();

        $this->assertRejectedAndUnchanged($id, [$column => $this->alternativeValueFor($column)], self::LOCKED_MESSAGE);
    }

    public function test_it_rejects_a_change_that_touches_several_locked_columns_at_once(): void
    {
        $id = $this->fullLetter();

        $this->assertRejectedAndUnchanged($id, [
            'perihal' => 'Perihal yang lain',
            'kode_klasifikasi' => '800',
        ], self::LOCKED_MESSAGE);
    }

    public function test_it_allows_an_update_that_leaves_every_locked_column_alone(): void
    {
        $id = $this->fullLetter();

        $this->updateLetter($id, ['updated_at' => '2026-10-20 12:00:00', 'perihal' => 'Perihal uji']);

        $this->assertSame('2026-10-20 12:00:00', $this->rawRow('nomor_surat', $id)['updated_at']);
        $this->assertSame('terbit', $this->rawRow('nomor_surat', $id)['status']);
    }

    public function test_it_lets_nomor_lengkap_be_filled_once_from_null(): void
    {
        $id = $this->bareLetter();

        $this->updateLetter($id, ['nomor_lengkap' => '443.32/1/PKP/UJI/X/2026']);

        $this->assertSame('443.32/1/PKP/UJI/X/2026', $this->rawRow('nomor_surat', $id)['nomor_lengkap']);
    }

    public function test_it_locks_nomor_lengkap_once_it_is_set(): void
    {
        $id = $this->bareLetter();
        $this->updateLetter($id, ['nomor_lengkap' => '443.32/1/PKP/UJI/X/2026']);

        $this->assertRejectedAndUnchanged($id, ['nomor_lengkap' => '443.32/2/PKP/UJI/X/2026'], self::LOCKED_MESSAGE);
        $this->assertRejectedAndUnchanged($id, ['nomor_lengkap' => null], self::LOCKED_MESSAGE);

        $this->updateLetter($id, ['nomor_lengkap' => '443.32/1/PKP/UJI/X/2026']);

        $this->assertSame('443.32/1/PKP/UJI/X/2026', $this->rawRow('nomor_surat', $id)['nomor_lengkap']);
    }

    public function test_it_cancels_a_letter_with_reason_user_and_time_in_one_update(): void
    {
        $id = $this->fullLetter();

        $this->updateLetter($id, $this->cancelFields());

        $row = $this->rawRow('nomor_surat', $id);
        $this->assertSame('batal', $row['status']);
        $this->assertSame('Salah ketik perihal', $row['alasan_batal']);
        $this->assertSame($this->otherUserId, $row['dibatalkan_oleh']);
        $this->assertSame('2026-10-16 10:00:00', $row['batal_pada']);
        $this->assertSame('443.32/1/PKP/UJI/X/2026', $row['nomor_lengkap']);
    }

    public function test_it_cancels_a_letter_through_the_service_query(): void
    {
        $id = $this->fullLetter();

        $changed = NomorSurat::query()->withoutGlobalScope('opd')->whereKey($id)->update($this->cancelFields());

        $this->assertSame(1, $changed);
        $this->assertSame('batal', $this->rawRow('nomor_surat', $id)['status']);
    }

    public function test_it_rejects_cancelling_together_with_a_change_to_a_locked_column(): void
    {
        $id = $this->fullLetter();

        $this->assertRejectedAndUnchanged($id, [...$this->cancelFields(), 'perihal' => 'Perihal yang lain'], self::LOCKED_MESSAGE);
    }

    public static function incompleteCancellations(): array
    {
        return [
            'no cancellation fields at all' => [[]],
            'reason only' => [['alasan_batal' => 'Salah ketik']],
        ];
    }

    #[DataProvider('incompleteCancellations')]
    public function test_it_rejects_cancelling_without_the_full_cancellation_data(array $fields): void
    {
        $id = $this->fullLetter();

        $this->assertTriggerRejects(
            fn () => $this->updateLetter($id, ['status' => 'batal', ...$fields]),
            'pembatalan',
        );

        $this->assertSame('terbit', $this->rawRow('nomor_surat', $id)['status']);
    }

    public function test_it_rejects_an_unknown_status_value(): void
    {
        $id = $this->fullLetter();

        $this->assertTriggerRejects(
            fn () => $this->updateLetter($id, ['status' => 'dihapus']),
            'tidak',
        );

        $this->assertSame('terbit', $this->rawRow('nomor_surat', $id)['status']);
    }

    public static function cancellationColumns(): array
    {
        return [
            'reason' => ['alasan_batal', 'Alasan yang diganti'],
            'cancelled by' => ['dibatalkan_oleh', 'OTHER_USER'],
            'cancelled at' => ['batal_pada', '2026-10-17 08:00:00'],
        ];
    }

    #[DataProvider('cancellationColumns')]
    public function test_it_rejects_editing_cancellation_data_afterwards(string $column, string $value): void
    {
        $id = $this->cancelled();
        $value = $value === 'OTHER_USER' ? $this->userId : $value;

        $this->assertRejectedAndUnchanged($id, [$column => $value], 'Data pembatalan hanya bisa diisi saat membatalkan');
    }

    public function test_it_rejects_clearing_cancellation_data_afterwards(): void
    {
        $id = $this->cancelled();

        $this->assertRejectedAndUnchanged($id, ['alasan_batal' => null], 'Data pembatalan hanya bisa diisi saat membatalkan');
    }

    public function test_it_rejects_cancellation_data_on_a_letter_that_stays_issued(): void
    {
        $id = $this->fullLetter();

        $this->assertRejectedAndUnchanged($id, ['alasan_batal' => 'Iseng'], 'Data pembatalan hanya bisa diisi saat membatalkan');
        $this->assertRejectedAndUnchanged($id, ['dibatalkan_oleh' => $this->userId], 'Data pembatalan hanya bisa diisi saat membatalkan');
        $this->assertRejectedAndUnchanged($id, ['batal_pada' => '2026-10-16 10:00:00'], 'Data pembatalan hanya bisa diisi saat membatalkan');
    }

    public function test_it_never_restores_a_cancelled_letter(): void
    {
        $id = $this->cancelled();

        $this->assertRejectedAndUnchanged($id, ['status' => 'terbit'], 'tidak bisa dipulihkan');
        $this->assertRejectedAndUnchanged($id, [
            'status' => 'terbit',
            'alasan_batal' => null,
            'dibatalkan_oleh' => null,
            'batal_pada' => null,
        ], 'tidak bisa dipulihkan');

        $this->assertSame('batal', $this->rawRow('nomor_surat', $id)['status']);
    }

    public function test_it_keeps_locked_columns_locked_on_a_cancelled_letter(): void
    {
        $id = $this->cancelled();

        $this->assertRejectedAndUnchanged($id, ['perihal' => 'Perihal yang lain'], self::LOCKED_MESSAGE);
    }

    public function test_writing_the_same_cancellation_values_again_changes_nothing(): void
    {
        $id = $this->cancelled();
        $before = $this->rawRow('nomor_surat', $id);

        $this->updateLetter($id, $this->cancelFields());

        $this->assertSame($before, $this->rawRow('nomor_surat', $id));
    }
}
