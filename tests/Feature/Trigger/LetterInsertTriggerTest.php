<?php

namespace Tests\Feature\Trigger;

use App\Models\Opd;
use App\Services\LetterNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\AssertsTriggerErrors;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class LetterInsertTriggerTest extends TestCase
{
    use AssertsTriggerErrors, BuildsFixtures, RefreshDatabase;

    private Opd $opd;

    private string $userId;

    private string $formatId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = $this->makeOpd('UJI');
        $this->userId = $this->makeUser(null, 'super_admin')->id;
        $this->formatId = $this->insertFormat($this->opd->id, $this->userId);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function newLetter(array $extra = [], ?Opd $opd = null, ?string $formatId = null): string
    {
        return $this->insertLetter([
            'opd_id' => ($opd ?? $this->opd)->id,
            'format_nomor_surat_id' => $formatId ?? $this->formatId,
            'dibuat_oleh' => $this->userId,
            ...$extra,
        ]);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function numberOf(string $id): array
    {
        $row = $this->rawRow('nomor_surat', $id);

        return [(int) $row['nomor_urut'], (int) $row['sub_nomor']];
    }

    private function letterCount(): int
    {
        return DB::table('nomor_surat')->count();
    }

    public function test_it_gives_the_first_letter_of_a_year_number_1(): void
    {
        $id = $this->newLetter();

        $this->assertSame([1, 0], $this->numberOf($id));
        $this->assertSame(1, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_it_numbers_letters_sequentially(): void
    {
        $ids = [$this->newLetter(), $this->newLetter(), $this->newLetter()];

        $this->assertSame([[1, 0], [2, 0], [3, 0]], array_map($this->numberOf(...), $ids));
        $this->assertSame(3, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_it_restarts_at_1_in_a_new_year_and_leaves_the_old_counter_alone(): void
    {
        $this->newLetter();
        $this->newLetter();

        $next = $this->newLetter(['tahun' => 2027, 'tanggal_surat' => '2027-01-03']);

        $this->assertSame([1, 0], $this->numberOf($next));
        $this->assertSame(1, $this->counterValue($this->opd->id, 'surat', '2027'));
        $this->assertSame(2, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_it_takes_the_counter_period_from_the_tahun_column(): void
    {
        $id = $this->newLetter(['tahun' => 2025, 'tanggal_surat' => '2026-03-01', 'tanggal_terbit' => '2026-03-02 08:00:00']);

        $this->assertSame([1, 0], $this->numberOf($id));
        $this->assertSame(1, $this->counterValue($this->opd->id, 'surat', '2025'));
        $this->assertNull($this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_it_keeps_a_separate_sequence_per_opd(): void
    {
        $other = $this->makeOpd('LAIN');
        $otherFormat = $this->insertFormat($other->id, $this->userId);

        $a1 = $this->newLetter();
        $a2 = $this->newLetter();
        $b1 = $this->newLetter([], $other, $otherFormat);
        $a3 = $this->newLetter();

        $this->assertSame([[1, 0], [2, 0], [3, 0]], array_map($this->numberOf(...), [$a1, $a2, $a3]));
        $this->assertSame([1, 0], $this->numberOf($b1));
        $this->assertSame(3, $this->counterValue($this->opd->id, 'surat', '2026'));
        $this->assertSame(1, $this->counterValue($other->id, 'surat', '2026'));
    }

    public function test_it_ignores_a_sub_number_sent_with_a_new_number(): void
    {
        $id = $this->newLetter(['nomor_urut' => 0, 'sub_nomor' => 7]);

        $this->assertSame([1, 0], $this->numberOf($id));
    }

    public function test_a_supplementary_letter_gets_sub_numbers_1_2_3_of_its_parent(): void
    {
        $this->newLetter();
        $this->newLetter();
        $this->newLetter();

        $first = $this->newLetter(['nomor_urut' => 2]);
        $second = $this->newLetter(['nomor_urut' => 2]);
        $third = $this->newLetter(['nomor_urut' => 2]);

        $this->assertSame([[2, 1], [2, 2], [2, 3]], array_map($this->numberOf(...), [$first, $second, $third]));
    }

    public function test_a_supplementary_letter_does_not_move_the_counter(): void
    {
        $this->newLetter();
        $this->newLetter();
        $this->newLetter();

        $this->newLetter(['nomor_urut' => 1]);
        $this->newLetter(['nomor_urut' => 2]);

        $this->assertSame(3, $this->counterValue($this->opd->id, 'surat', '2026'));
        $this->assertSame([4, 0], $this->numberOf($this->newLetter()));
    }

    public function test_sub_numbers_are_counted_per_parent(): void
    {
        $this->newLetter();
        $this->newLetter();

        $this->newLetter(['nomor_urut' => 1]);
        $this->newLetter(['nomor_urut' => 1]);
        $other = $this->newLetter(['nomor_urut' => 2]);

        $this->assertSame([2, 1], $this->numberOf($other));
    }

    public function test_it_rejects_a_supplementary_letter_without_a_parent_and_stores_nothing(): void
    {
        $this->newLetter();

        $this->assertTriggerRejects(
            fn () => $this->newLetter(['nomor_urut' => 99]),
            'Nomor induk susulan tidak ditemukan',
        );

        $this->assertSame(1, $this->letterCount());
        $this->assertSame(1, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_it_rejects_a_supplementary_letter_when_the_parent_belongs_to_another_year(): void
    {
        $this->newLetter();

        $this->assertTriggerRejects(
            fn () => $this->newLetter(['nomor_urut' => 1, 'tahun' => 2027, 'tanggal_surat' => '2027-01-05']),
            'Nomor induk susulan tidak ditemukan',
        );

        $this->assertSame(1, $this->letterCount());
    }

    public function test_it_rejects_a_supplementary_letter_when_the_parent_belongs_to_another_opd(): void
    {
        $other = $this->makeOpd('LAIN');
        $otherFormat = $this->insertFormat($other->id, $this->userId);
        $this->newLetter();

        $this->assertTriggerRejects(
            fn () => $this->newLetter(['nomor_urut' => 1], $other, $otherFormat),
            'Nomor induk susulan tidak ditemukan',
        );

        $this->assertSame(1, $this->letterCount());
    }

    public function test_a_cancelled_parent_still_counts_as_a_parent(): void
    {
        $parent = $this->newLetter();

        DB::table('nomor_surat')->where('id', $parent)->update([
            'status' => 'batal',
            'alasan_batal' => 'Salah ketik',
            'dibatalkan_oleh' => $this->userId,
            'batal_pada' => '2026-10-16 10:00:00',
        ]);

        $child = $this->newLetter(['nomor_urut' => 1]);

        $this->assertSame([1, 1], $this->numberOf($child));
        $this->assertSame('terbit', $this->rawRow('nomor_surat', $child)['status']);
    }

    public function test_the_trigger_does_not_read_the_allow_supplementary_setting(): void
    {
        $withFlagOff = $this->insertFormat($this->opd->id, $this->userId, '2026-02-01', false);
        $withFlagOn = $this->insertFormat($this->opd->id, $this->userId, '2026-03-01', true);
        $this->newLetter();

        $off = $this->newLetter(['nomor_urut' => 1], null, $withFlagOff);
        $on = $this->newLetter(['nomor_urut' => 1], null, $withFlagOn);

        $this->assertSame([1, 1], $this->numberOf($off));
        $this->assertSame([1, 2], $this->numberOf($on));
    }

    public function test_numbering_continues_from_a_manually_raised_counter(): void
    {
        $this->travelTo('2026-10-15 09:00:00');

        app(LetterNumberService::class)->setLastNumber($this->opd->id, 150);

        $id = $this->newLetter();

        $this->assertSame([151, 0], $this->numberOf($id));
        $this->assertSame(151, $this->counterValue($this->opd->id, 'surat', '2026'));
    }
}
