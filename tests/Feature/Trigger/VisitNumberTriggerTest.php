<?php

namespace Tests\Feature\Trigger;

use App\Models\Opd;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AssertsTriggerErrors;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class VisitNumberTriggerTest extends TestCase
{
    use AssertsTriggerErrors, BuildsFixtures, RefreshDatabase;

    private Opd $opd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = $this->makeOpd('UJI');
    }

    private function visitNumber(string $id): string
    {
        return $this->rawRow('kunjungan', $id)['nomor_kunjungan'];
    }

    public function test_it_gives_the_first_visit_of_a_month_number_0001(): void
    {
        $id = $this->insertVisit($this->opd->id, '2026-10-15 09:30:00');

        $this->assertSame('20261015-0001', $this->visitNumber($id));
        $this->assertSame(1, $this->counterValue($this->opd->id, 'kunjungan', '202610'));
    }

    public function test_it_numbers_visits_sequentially_within_a_month_across_days(): void
    {
        $first = $this->insertVisit($this->opd->id, '2026-10-15 09:30:00');
        $second = $this->insertVisit($this->opd->id, '2026-10-15 10:00:00');
        $third = $this->insertVisit($this->opd->id, '2026-10-16 08:00:00');

        $this->assertSame('20261015-0001', $this->visitNumber($first));
        $this->assertSame('20261015-0002', $this->visitNumber($second));
        $this->assertSame('20261016-0003', $this->visitNumber($third));
        $this->assertSame(3, $this->counterValue($this->opd->id, 'kunjungan', '202610'));
    }

    public function test_it_restarts_at_0001_in_a_new_month_and_leaves_the_old_counter_alone(): void
    {
        $this->insertVisit($this->opd->id, '2026-10-30 09:00:00');
        $this->insertVisit($this->opd->id, '2026-10-31 09:00:00');

        $november = $this->insertVisit($this->opd->id, '2026-11-02 09:00:00');

        $this->assertSame('20261102-0001', $this->visitNumber($november));
        $this->assertSame(1, $this->counterValue($this->opd->id, 'kunjungan', '202611'));
        $this->assertSame(2, $this->counterValue($this->opd->id, 'kunjungan', '202610'));
    }

    #[DataProvider('monthBoundaries')]
    public function test_it_assigns_the_month_by_the_visit_time_at_the_boundary(string $waktuDatang, string $expectedNumber, string $expectedPeriod): void
    {
        $id = $this->insertVisit($this->opd->id, $waktuDatang);

        $this->assertSame($expectedNumber, $this->visitNumber($id));
        $this->assertSame(1, $this->counterValue($this->opd->id, 'kunjungan', $expectedPeriod));
    }

    public static function monthBoundaries(): array
    {
        return [
            'last second of october' => ['2026-10-31 23:59:59', '20261031-0001', '202610'],
            'first second of november' => ['2026-11-01 00:00:00', '20261101-0001', '202611'],
            'last second of the year' => ['2026-12-31 23:59:59', '20261231-0001', '202612'],
            'first second of the next year' => ['2027-01-01 00:00:00', '20270101-0001', '202701'],
        ];
    }

    public function test_it_uses_waktu_datang_and_not_the_current_date(): void
    {
        $this->travelTo('2026-10-29 10:00:00');

        $id = $this->insertVisit($this->opd->id, '2024-03-05 09:00:00');

        $this->assertSame('20240305-0001', $this->visitNumber($id));
        $this->assertSame(1, $this->counterValue($this->opd->id, 'kunjungan', '202403'));
        $this->assertNull($this->counterValue($this->opd->id, 'kunjungan', '202610'));
        $this->assertSame(1, DB::table('nomor_counters')->count());
    }

    public function test_a_back_dated_visit_continues_the_counter_of_its_own_month(): void
    {
        $this->insertVisit($this->opd->id, '2026-10-05 09:00:00');
        $this->insertVisit($this->opd->id, '2026-10-06 09:00:00');
        $this->insertVisit($this->opd->id, '2026-11-02 09:00:00');

        $backDated = $this->insertVisit($this->opd->id, '2026-10-03 09:00:00');

        $this->assertSame('20261003-0003', $this->visitNumber($backDated));
        $this->assertSame(3, $this->counterValue($this->opd->id, 'kunjungan', '202610'));
        $this->assertSame(1, $this->counterValue($this->opd->id, 'kunjungan', '202611'));
    }

    public function test_it_keeps_a_separate_sequence_per_opd(): void
    {
        $other = $this->makeOpd('LAIN');

        $a1 = $this->insertVisit($this->opd->id, '2026-10-15 09:00:00');
        $a2 = $this->insertVisit($this->opd->id, '2026-10-15 09:10:00');
        $b1 = $this->insertVisit($other->id, '2026-10-15 09:20:00');
        $a3 = $this->insertVisit($this->opd->id, '2026-10-15 09:30:00');
        $b2 = $this->insertVisit($other->id, '2026-10-15 09:40:00');

        $this->assertSame('20261015-0001', $this->visitNumber($a1));
        $this->assertSame('20261015-0002', $this->visitNumber($a2));
        $this->assertSame('20261015-0003', $this->visitNumber($a3));
        $this->assertSame('20261015-0001', $this->visitNumber($b1));
        $this->assertSame('20261015-0002', $this->visitNumber($b2));
        $this->assertSame(3, $this->counterValue($this->opd->id, 'kunjungan', '202610'));
        $this->assertSame(2, $this->counterValue($other->id, 'kunjungan', '202610'));
    }

    public function test_it_overrides_a_number_supplied_by_the_client(): void
    {
        $id = $this->insertVisit($this->opd->id, '2026-10-15 09:00:00', ['nomor_kunjungan' => 'MANUAL-0042']);

        $this->assertSame('20261015-0001', $this->visitNumber($id));
    }

    public function test_it_accepts_the_last_slot_9999_and_then_rejects_the_next_visit(): void
    {
        $this->setCounter($this->opd->id, 'kunjungan', '202610', 9998);

        $last = $this->insertVisit($this->opd->id, '2026-10-15 09:00:00');

        $this->assertSame('20261015-9999', $this->visitNumber($last));
        $this->assertSame(9999, $this->counterValue($this->opd->id, 'kunjungan', '202610'));

        $this->assertTriggerRejects(
            fn () => $this->insertVisit($this->opd->id, '2026-10-16 09:00:00'),
            'Kapasitas nomor kunjungan',
        );

        $this->assertSame(9999, $this->counterValue($this->opd->id, 'kunjungan', '202610'));
        $this->assertSame(1, DB::table('kunjungan')->count());
    }

    public function test_a_full_month_does_not_block_the_next_month(): void
    {
        $this->setCounter($this->opd->id, 'kunjungan', '202610', 9999);

        $this->assertTriggerRejects(
            fn () => $this->insertVisit($this->opd->id, '2026-10-20 09:00:00'),
            'Kapasitas nomor kunjungan',
        );

        $november = $this->insertVisit($this->opd->id, '2026-11-01 09:00:00');

        $this->assertSame('20261101-0001', $this->visitNumber($november));
    }

    public function test_a_full_month_of_one_opd_does_not_block_another_opd(): void
    {
        $other = $this->makeOpd('LAIN');
        $this->setCounter($this->opd->id, 'kunjungan', '202610', 9999);

        $id = $this->insertVisit($other->id, '2026-10-20 09:00:00');

        $this->assertSame('20261020-0001', $this->visitNumber($id));
    }

    public function test_the_number_is_unique_per_opd_and_may_repeat_across_opds(): void
    {
        $other = $this->makeOpd('LAIN');

        $a = $this->insertVisit($this->opd->id, '2026-10-15 09:00:00');
        $b = $this->insertVisit($other->id, '2026-10-15 09:00:00');

        $this->assertSame($this->visitNumber($a), $this->visitNumber($b));
        $this->assertNotSame(
            $this->rawRow('kunjungan', $a)['opd_id'],
            $this->rawRow('kunjungan', $b)['opd_id'],
        );

        $second = $this->insertVisit($this->opd->id, '2026-10-15 10:00:00');

        try {
            DB::table('kunjungan')->where('id', $second)->update(['nomor_kunjungan' => $this->visitNumber($a)]);
            $this->fail('Unique (opd_id, nomor_kunjungan) seharusnya menolak nomor ganda.');
        } catch (QueryException $e) {
            $this->assertSame('23000', (string) $e->getCode());
        }

        $this->assertSame('20261015-0002', $this->visitNumber($second));
    }
}
