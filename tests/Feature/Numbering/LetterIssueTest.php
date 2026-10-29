<?php

namespace Tests\Feature\Numbering;

use App\Exceptions\LetterFormatNotFoundException;
use App\Models\NomorSurat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use Throwable;
use Tests\Support\PreparesLetterIssuing;
use Tests\TestCase;

class LetterIssueTest extends TestCase
{
    use PreparesLetterIssuing, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLetters();
        $this->travelTo('2026-10-15 10:30:00');
    }

    public function test_it_issues_number_1_first_and_assembles_the_full_number(): void
    {
        $letter = $this->issueLetter(['tanggal_surat' => '2026-10-15']);

        $this->assertSame(1, $letter->nomor_urut);
        $this->assertSame(0, $letter->sub_nomor);
        $this->assertSame('443.32/1/PKP/UJI/X/2026', $letter->nomor_lengkap);
        $this->assertDatabaseHas('nomor_surat', [
            'id' => $letter->id,
            'opd_id' => $this->opd->id,
            'format_nomor_surat_id' => $this->formatId,
            'bidang_id' => $this->bidangId,
            'tahun' => 2026,
            'nomor_urut' => 1,
            'sub_nomor' => 0,
            'nomor_lengkap' => '443.32/1/PKP/UJI/X/2026',
            'kode_klasifikasi' => '443.32',
            'perihal' => 'Undangan rapat',
            'tujuan_surat' => 'Dinas Pendidikan',
            'nama_peminta' => 'Pak Budi',
            'tanggal_surat' => '2026-10-15',
            'tanggal_terbit' => '2026-10-15 10:30:00',
            'status' => 'terbit',
            'dibuat_oleh' => $this->admin->id,
            'dibatalkan_oleh' => null,
            'alasan_batal' => null,
        ]);
    }

    public function test_it_increments_the_number_and_keeps_the_counter_in_step(): void
    {
        $first = $this->issueLetter();
        $second = $this->issueLetter();
        $third = $this->issueLetter();

        $this->assertSame([1, 2, 3], [$first->nomor_urut, $second->nomor_urut, $third->nomor_urut]);
        $this->assertSame('443.32/3/PKP/UJI/X/2026', $third->nomor_lengkap);
        $this->assertSame(3, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_it_records_the_officer_who_issued_the_number(): void
    {
        $other = $this->makeUser($this->opd);

        $letter = $this->issueLetter(by: $other);

        $this->assertSame($other->id, $letter->dibuat_oleh);
        $this->assertSame($other->id, $letter->pembuat->id);
    }

    public function test_it_stamps_the_issue_time_with_now_not_with_the_letter_date(): void
    {
        $letter = $this->issueLetter(['tanggal_surat' => '2026-10-03']);

        $this->assertSame('2026-10-15 10:30:00', $letter->tanggal_terbit->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-03', $letter->tanggal_surat->toDateString());
    }

    public function test_it_leaves_optional_columns_empty_when_not_given(): void
    {
        $letter = $this->issueLetter(['tujuan_surat' => null, 'nama_peminta' => null]);

        $this->assertNull($letter->tujuan_surat);
        $this->assertNull($letter->nama_peminta);
    }

    public function test_it_ignores_system_columns_passed_in_the_data(): void
    {
        $letter = $this->issueLetter([
            'nomor_urut' => 77,
            'sub_nomor' => 3,
            'status' => 'batal',
            'nomor_lengkap' => 'PALSU',
            'dibuat_oleh' => $this->makeUser(null, 'super_admin')->id,
            'opd_id' => $this->makeOpd('LAIN')->id,
        ]);

        $this->assertSame([1, 0], [$letter->nomor_urut, $letter->sub_nomor]);
        $this->assertSame('terbit', $letter->status);
        $this->assertSame('443.32/1/PKP/UJI/X/2026', $letter->nomor_lengkap);
        $this->assertSame($this->admin->id, $letter->dibuat_oleh);
        $this->assertSame($this->opd->id, $letter->opd_id);
    }

    public function test_it_throws_when_no_format_exists_and_stores_nothing(): void
    {
        DB::table('format_nomor_surat')->delete();

        try {
            $this->issueLetter();
            $this->fail('Seharusnya melempar LetterFormatNotFoundException.');
        } catch (LetterFormatNotFoundException) {
            $this->assertSame(0, DB::table('nomor_surat')->count());
            $this->assertNull($this->counterValue($this->opd->id, 'surat', '2026'));
        }
    }

    public function test_it_throws_when_the_only_format_starts_in_the_future(): void
    {
        DB::table('format_nomor_surat')->delete();
        $this->insertFormat($this->opd->id, $this->creatorId, '2026-10-16');

        $this->expectException(LetterFormatNotFoundException::class);

        $this->issueLetter();
    }

    public function test_it_uses_a_format_that_starts_today(): void
    {
        DB::table('format_nomor_surat')->delete();
        $this->insertFormat($this->opd->id, $this->creatorId, '2026-10-15');

        $this->assertSame('443.32/1/PKP/UJI/X/2026', $this->issueLetter()->nomor_lengkap);
    }

    public function test_the_format_in_force_is_the_latest_one_not_after_the_issue_moment(): void
    {
        $secondFormat = $this->insertFormat($this->opd->id, $this->creatorId, '2026-10-16', false, [
            ['jenis' => 'urut'],
            ['jenis' => 'teks', 'teks' => 'BARU'],
            ['jenis' => 'tahun'],
        ]);

        $this->travelTo('2026-10-15 23:59:59');
        $before = $this->issueLetter();

        $this->travelTo('2026-10-16 00:00:00');
        $after = $this->issueLetter();

        $this->assertSame($this->formatId, $before->format_nomor_surat_id);
        $this->assertSame('443.32/1/PKP/UJI/X/2026', $before->nomor_lengkap);
        $this->assertSame($secondFormat, $after->format_nomor_surat_id);
        $this->assertSame('2/BARU/2026', $after->nomor_lengkap);
    }

    public function test_the_format_is_chosen_at_issue_time_not_at_the_letter_date(): void
    {
        $this->insertFormat($this->opd->id, $this->creatorId, '2026-10-01', false, [
            ['jenis' => 'urut'],
            ['jenis' => 'tahun'],
        ]);

        $letter = $this->issueLetter(['tanggal_surat' => '2026-03-10']);

        $this->assertSame('1/2026', $letter->nomor_lengkap);
    }

    public function test_a_newer_format_of_another_opd_does_not_affect_this_opd(): void
    {
        $other = $this->makeOpd('LAIN');
        $this->insertFormat($other->id, $this->creatorId, '2026-10-01', false, [['jenis' => 'teks', 'teks' => 'LAIN'], ['jenis' => 'urut']]);

        $this->assertSame('443.32/1/PKP/UJI/X/2026', $this->issueLetter()->nomor_lengkap);
    }

    public function test_the_year_and_month_come_from_the_letter_date_and_not_the_issue_date(): void
    {
        $letter = $this->issueLetter(['tanggal_surat' => '2025-12-20']);

        $this->assertSame(2025, $letter->tahun);
        $this->assertSame('443.32/1/PKP/UJI/XII/2025', $letter->nomor_lengkap);
        $this->assertSame(1, $this->counterValue($this->opd->id, 'surat', '2025'));
        $this->assertNull($this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_each_letter_year_has_its_own_counter(): void
    {
        $this->issueLetter(['tanggal_surat' => '2025-12-20']);
        $this->issueLetter(['tanggal_surat' => '2025-11-02']);
        $current = $this->issueLetter(['tanggal_surat' => '2026-10-15']);
        $older = $this->issueLetter(['tanggal_surat' => '2025-10-01']);

        $this->assertSame(1, $current->nomor_urut);
        $this->assertSame(3, $older->nomor_urut);
        $this->assertSame(1, $this->counterValue($this->opd->id, 'surat', '2026'));
        $this->assertSame(3, $this->counterValue($this->opd->id, 'surat', '2025'));
    }

    public function test_the_first_letter_of_january_restarts_the_numbering(): void
    {
        $this->issueLetter();
        $this->issueLetter();

        $this->travelTo('2027-01-04 09:00:00');
        $this->insertFormat($this->opd->id, $this->creatorId, '2026-12-31');

        $letter = $this->issueLetter(['tanggal_surat' => '2027-01-04']);

        $this->assertSame(1, $letter->nomor_urut);
        $this->assertSame('443.32/1/PKP/UJI/I/2027', $letter->nomor_lengkap);
    }

    public function test_it_continues_after_a_manually_set_last_number(): void
    {
        app(\App\Services\LetterNumberService::class)->setLastNumber($this->opd->id, 150);

        $letter = $this->issueLetter();

        $this->assertSame(151, $letter->nomor_urut);
        $this->assertSame('443.32/151/PKP/UJI/X/2026', $letter->nomor_lengkap);
    }

    public function test_it_renders_a_format_without_bidang_when_no_bidang_is_given(): void
    {
        DB::table('format_nomor_surat')->delete();
        $this->insertFormat($this->opd->id, $this->creatorId, '2026-01-01', false, [
            ['jenis' => 'klasifikasi'],
            ['jenis' => 'urut'],
            ['jenis' => 'kode_opd'],
            ['jenis' => 'tahun'],
        ]);

        $letter = $this->issueLetter(['bidang_id' => null]);

        $this->assertNull($letter->bidang_id);
        $this->assertSame('443.32/1/UJI/2026', $letter->nomor_lengkap);
    }

    // BUG (defence in depth): issue() never checks bidang_id against the OPD; with a format that has no `bidang` segment a bidang of another OPD is stored silently. The panel form limits the options, so only reachable outside the form.
    #[Group('known-bug')]
    public function test_it_does_not_store_a_bidang_of_another_opd(): void
    {
        DB::table('format_nomor_surat')->delete();
        $this->insertFormat($this->opd->id, $this->creatorId, '2026-01-01', false, [['jenis' => 'klasifikasi'], ['jenis' => 'urut']]);
        $foreignBidang = $this->insertBidang($this->makeOpd('LAIN')->id, 'SDP');

        try {
            $this->issueLetter(['bidang_id' => $foreignBidang]);
        } catch (Throwable) {
        }

        $this->assertDatabaseMissing('nomor_surat', ['bidang_id' => $foreignBidang]);
    }

    public function test_a_missing_bidang_rolls_everything_back_and_does_not_burn_a_number(): void
    {
        try {
            $this->issueLetter(['bidang_id' => null]);
            $this->fail('Format butuh bidang, seharusnya melempar exception.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('bidang', $e->getMessage());
        }

        $this->assertSame(0, NomorSurat::count());
        $this->assertNull($this->counterValue($this->opd->id, 'surat', '2026'));

        $this->assertSame(1, $this->issueLetter()->nomor_urut);
    }

    public function test_another_opd_has_its_own_numbering_and_its_own_code_in_the_number(): void
    {
        $other = $this->makeOpd('LAIN');
        $otherAdmin = $this->makeUser($other);
        $otherBidang = $this->insertBidang($other->id, 'SDP');
        $this->insertFormat($other->id, $this->creatorId);

        $this->issueLetter();
        $this->issueLetter();

        $this->actingAs($otherAdmin);
        $letterOfOther = $this->issueLetter(['bidang_id' => $otherBidang], $otherAdmin);

        $this->assertSame(1, $letterOfOther->nomor_urut);
        $this->assertSame('443.32/1/SDP/LAIN/X/2026', $letterOfOther->nomor_lengkap);
        $this->assertSame($other->id, $letterOfOther->opd_id);
        $this->assertSame(2, $this->counterValue($this->opd->id, 'surat', '2026'));
    }
}
