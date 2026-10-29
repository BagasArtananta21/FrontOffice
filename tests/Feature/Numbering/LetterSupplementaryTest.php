<?php

namespace Tests\Feature\Numbering;

use App\Exceptions\LetterBaseNumberNotFoundException;
use App\Models\NomorSurat;
use App\Services\LetterNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PreparesLetterIssuing;
use Tests\TestCase;

class LetterSupplementaryTest extends TestCase
{
    use PreparesLetterIssuing, RefreshDatabase;

    /**
     * Numbers 1 and 2 issued on 5 Oct, number 3 issued on 10 Oct; "now" is 20 Oct afterwards.
     */
    private function issueThreeParentsThenMoveToTheTwentieth(): void
    {
        $this->issueOn('2026-10-05 09:00:00', '2026-10-05');
        $this->issueOn('2026-10-05 10:00:00', '2026-10-05');
        $this->issueOn('2026-10-10 09:00:00', '2026-10-10');

        $this->travelTo('2026-10-20 10:00:00');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function numberOf(NomorSurat $letter): array
    {
        return [$letter->nomor_urut, $letter->sub_nomor];
    }

    public static function backDatedLetters(): array
    {
        return [
            'a day before the first issued number has no parent' => ['2026-10-04', null],
            'the day number 1 and 2 were issued hangs under number 2' => ['2026-10-05', [2, 1]],
            'a day between the parents hangs under the last earlier parent' => ['2026-10-07', [2, 1]],
            'the day before number 3 was issued still hangs under number 2' => ['2026-10-09', [2, 1]],
            'the day number 3 was issued hangs under number 3' => ['2026-10-10', [4, 0]],
            'today is not back-dated' => ['2026-10-20', [4, 0]],
        ];
    }

    #[DataProvider('backDatedLetters')]
    public function test_a_back_dated_letter_hangs_under_the_last_parent_issued_on_or_before_its_date(string $letterDate, ?array $expected): void
    {
        $this->setUpLetters(true);
        $this->issueThreeParentsThenMoveToTheTwentieth();

        if ($expected === null) {
            $this->expectException(LetterBaseNumberNotFoundException::class);
        }

        $letter = $this->issueLetter(['tanggal_surat' => $letterDate]);

        $this->assertSame($expected, $this->numberOf($letter));
    }

    public function test_the_day_number_3_was_issued_is_the_boundary_for_hanging_under_number_3(): void
    {
        $this->setUpLetters(true);
        $this->issueThreeParentsThenMoveToTheTwentieth();
        $this->issueOn('2026-10-12 09:00:00', '2026-10-12');
        $this->travelTo('2026-10-20 10:00:00');

        $letter = $this->issueLetter(['tanggal_surat' => '2026-10-10']);

        $this->assertSame([3, 1], $this->numberOf($letter));
        $this->assertSame('443.32/3.1/PKP/UJI/X/2026', $letter->nomor_lengkap);
    }

    public function test_it_numbers_successive_supplementary_letters_2_1_then_2_2_and_does_not_move_the_counter(): void
    {
        $this->setUpLetters(true);
        $this->issueThreeParentsThenMoveToTheTwentieth();

        $first = $this->issueLetter(['tanggal_surat' => '2026-10-07']);
        $second = $this->issueLetter(['tanggal_surat' => '2026-10-08']);

        $this->assertSame([2, 1], $this->numberOf($first));
        $this->assertSame('443.32/2.1/PKP/UJI/X/2026', $first->nomor_lengkap);
        $this->assertSame([2, 2], $this->numberOf($second));
        $this->assertSame('443.32/2.2/PKP/UJI/X/2026', $second->nomor_lengkap);
        $this->assertSame(3, $this->counterValue($this->opd->id, 'surat', '2026'));

        $next = $this->issueLetter(['tanggal_surat' => '2026-10-20']);

        $this->assertSame([4, 0], $this->numberOf($next));
    }

    public function test_a_supplementary_letter_is_never_a_parent_itself(): void
    {
        $this->setUpLetters(true);
        $this->issueThreeParentsThenMoveToTheTwentieth();
        $this->issueLetter(['tanggal_surat' => '2026-10-07']);

        $letter = $this->issueLetter(['tanggal_surat' => '2026-10-09']);

        $this->assertSame([2, 2], $this->numberOf($letter));
    }

    public function test_a_supplementary_letter_keeps_the_month_and_year_of_its_letter_date(): void
    {
        $this->setUpLetters(true);
        $this->issueThreeParentsThenMoveToTheTwentieth();

        $letter = $this->issueLetter(['tanggal_surat' => '2026-10-07']);

        $this->assertSame(2026, $letter->tahun);
        $this->assertSame('2026-10-07', $letter->tanggal_surat->toDateString());
        $this->assertSame('2026-10-20 10:00:00', $letter->tanggal_terbit->format('Y-m-d H:i:s'));
    }

    public function test_a_cancelled_parent_still_counts_as_a_parent(): void
    {
        $this->setUpLetters(true);
        $this->issueThreeParentsThenMoveToTheTwentieth();
        $parent = NomorSurat::where(['nomor_urut' => 2, 'sub_nomor' => 0])->firstOrFail();
        $this->assertTrue(app(LetterNumberService::class)->cancel($parent, 'Salah ketik', $this->admin));

        $letter = $this->issueLetter(['tanggal_surat' => '2026-10-07']);

        $this->assertSame([2, 1], $this->numberOf($letter));
    }

    public function test_it_throws_without_a_parent_and_stores_nothing(): void
    {
        $this->setUpLetters(true);
        $this->issueThreeParentsThenMoveToTheTwentieth();

        try {
            $this->issueLetter(['tanggal_surat' => '2026-10-04']);
            $this->fail('Seharusnya melempar LetterBaseNumberNotFoundException.');
        } catch (LetterBaseNumberNotFoundException) {
            $this->assertSame(3, NomorSurat::count());
            $this->assertSame(3, $this->counterValue($this->opd->id, 'surat', '2026'));
        }
    }

    public function test_it_gives_the_next_number_when_nothing_was_issued_after_the_letter_date(): void
    {
        $this->setUpLetters(true);
        $this->issueOn('2026-10-05 09:00:00', '2026-10-05');
        $this->issueOn('2026-10-06 09:00:00', '2026-10-06');
        $this->travelTo('2026-10-20 10:00:00');

        $letter = $this->issueLetter(['tanggal_surat' => '2026-10-06']);

        $this->assertSame([3, 0], $this->numberOf($letter));
        $this->assertSame('443.32/3/PKP/UJI/X/2026', $letter->nomor_lengkap);
    }

    public function test_a_back_dated_letter_of_a_year_without_letters_just_gets_number_1(): void
    {
        $this->setUpLetters(true);
        $this->issueThreeParentsThenMoveToTheTwentieth();

        $letter = $this->issueLetter(['tanggal_surat' => '2025-12-20']);

        $this->assertSame([1, 0], $this->numberOf($letter));
        $this->assertSame(2025, $letter->tahun);
        $this->assertSame('443.32/1/PKP/UJI/XII/2025', $letter->nomor_lengkap);
    }

    public function test_parents_of_another_opd_are_not_considered(): void
    {
        $this->setUpLetters(true);
        $other = $this->makeOpd('LAIN');
        $otherAdmin = $this->makeUser($other);
        $otherBidang = $this->insertBidang($other->id, 'SDP');
        $this->insertFormat($other->id, $this->creatorId, '2026-01-01', true);
        $this->issueThreeParentsThenMoveToTheTwentieth();

        $this->actingAs($otherAdmin);
        $letter = $this->issueLetter(['tanggal_surat' => '2026-10-04', 'bidang_id' => $otherBidang], $otherAdmin);

        $this->assertSame([1, 0], $this->numberOf($letter));
        $this->assertSame($other->id, $letter->opd_id);
        $this->assertSame(3, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_it_gives_back_dated_letters_plain_next_numbers_when_supplementary_is_off(): void
    {
        $this->setUpLetters(false);
        $this->issueThreeParentsThenMoveToTheTwentieth();

        $first = $this->issueLetter(['tanggal_surat' => '2026-10-07']);
        $second = $this->issueLetter(['tanggal_surat' => '2026-10-04']);

        $this->assertSame([4, 0], $this->numberOf($first));
        $this->assertSame('443.32/4/PKP/UJI/X/2026', $first->nomor_lengkap);
        $this->assertSame([5, 0], $this->numberOf($second));
        $this->assertSame(5, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_the_setting_is_read_from_the_format_in_force_at_issue_time(): void
    {
        $this->setUpLetters(false);
        $this->issueThreeParentsThenMoveToTheTwentieth();
        $this->insertFormat($this->opd->id, $this->creatorId, '2026-10-20', true);

        $letter = $this->issueLetter(['tanggal_surat' => '2026-10-07']);

        $this->assertSame([2, 1], $this->numberOf($letter));
    }
}
