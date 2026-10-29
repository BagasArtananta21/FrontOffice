<?php

namespace Tests\Feature\Trigger;

use App\Models\Opd;
use App\Services\LetterNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\AssertsTriggerErrors;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class CounterUpdateTriggerTest extends TestCase
{
    use AssertsTriggerErrors, BuildsFixtures, RefreshDatabase;

    private const MESSAGE = 'Nomor terakhir tidak boleh diturunkan';

    private Opd $opd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = $this->makeOpd('UJI');
    }

    private function updateCounter(string $jenis, string $periode, int $nomorTerakhir): void
    {
        DB::table('nomor_counters')
            ->where(['opd_id' => $this->opd->id, 'jenis' => $jenis, 'periode' => $periode])
            ->update(['nomor_terakhir' => $nomorTerakhir]);
    }

    public function test_it_allows_raising_the_counter(): void
    {
        $this->setCounter($this->opd->id, 'surat', '2026', 10);

        $this->updateCounter('surat', '2026', 11);
        $this->assertSame(11, $this->counterValue($this->opd->id, 'surat', '2026'));

        $this->updateCounter('surat', '2026', 500);
        $this->assertSame(500, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_it_allows_writing_the_same_value_again(): void
    {
        $this->setCounter($this->opd->id, 'surat', '2026', 10);

        $this->updateCounter('surat', '2026', 10);

        $this->assertSame(10, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_it_rejects_lowering_the_counter_by_one(): void
    {
        $this->setCounter($this->opd->id, 'surat', '2026', 10);

        $this->assertTriggerRejects(fn () => $this->updateCounter('surat', '2026', 9), self::MESSAGE);

        $this->assertSame(10, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_it_rejects_resetting_the_counter_to_zero(): void
    {
        $this->setCounter($this->opd->id, 'surat', '2026', 10);

        $this->assertTriggerRejects(fn () => $this->updateCounter('surat', '2026', 0), self::MESSAGE);

        $this->assertSame(10, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_it_rejects_lowering_a_visit_counter_too(): void
    {
        $this->setCounter($this->opd->id, 'kunjungan', '202610', 25);

        $this->assertTriggerRejects(fn () => $this->updateCounter('kunjungan', '202610', 24), self::MESSAGE);

        $this->assertSame(25, $this->counterValue($this->opd->id, 'kunjungan', '202610'));
    }

    public function test_it_rejects_lowering_every_counter_in_a_bulk_update_and_keeps_all_values(): void
    {
        $this->setCounter($this->opd->id, 'surat', '2026', 10);
        $this->setCounter($this->opd->id, 'kunjungan', '202610', 25);

        $this->assertTriggerRejects(fn () => DB::table('nomor_counters')->update(['nomor_terakhir' => 5]), self::MESSAGE);

        $this->assertSame(10, $this->counterValue($this->opd->id, 'surat', '2026'));
        $this->assertSame(25, $this->counterValue($this->opd->id, 'kunjungan', '202610'));
    }

    public function test_numbering_continues_after_a_manual_raise_and_never_goes_back(): void
    {
        $this->travelTo('2026-10-15 09:00:00');
        $userId = $this->makeUser(null, 'super_admin')->id;
        $formatId = $this->insertFormat($this->opd->id, $userId);
        $service = app(LetterNumberService::class);

        $service->setLastNumber($this->opd->id, 40);
        $first = $this->insertLetter(['opd_id' => $this->opd->id, 'format_nomor_surat_id' => $formatId, 'dibuat_oleh' => $userId]);
        $service->setLastNumber($this->opd->id, 200);
        $second = $this->insertLetter(['opd_id' => $this->opd->id, 'format_nomor_surat_id' => $formatId, 'dibuat_oleh' => $userId]);

        $this->assertSame(41, (int) $this->rawRow('nomor_surat', $first)['nomor_urut']);
        $this->assertSame(201, (int) $this->rawRow('nomor_surat', $second)['nomor_urut']);
        $this->assertSame(201, $this->counterValue($this->opd->id, 'surat', '2026'));

        $this->assertTriggerRejects(fn () => $service->setLastNumber($this->opd->id, 100), self::MESSAGE);
        $this->assertSame(201, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_a_manual_raise_leaves_other_opds_and_other_years_alone(): void
    {
        $this->travelTo('2026-10-15 09:00:00');
        $other = $this->makeOpd('LAIN');
        $this->setCounter($other->id, 'surat', '2026', 7);
        $this->setCounter($this->opd->id, 'surat', '2025', 90);

        app(LetterNumberService::class)->setLastNumber($this->opd->id, 150);

        $this->assertSame(150, $this->counterValue($this->opd->id, 'surat', '2026'));
        $this->assertSame(7, $this->counterValue($other->id, 'surat', '2026'));
        $this->assertSame(90, $this->counterValue($this->opd->id, 'surat', '2025'));
    }
}
