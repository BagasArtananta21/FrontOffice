<?php

namespace Tests\Feature\Trigger;

use App\Models\NomorCounter;
use App\Models\Opd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\AssertsTriggerErrors;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class CounterDeleteTriggerTest extends TestCase
{
    use AssertsTriggerErrors, BuildsFixtures, RefreshDatabase;

    private const MESSAGE = 'Counter penomoran tidak boleh dihapus';

    private Opd $opd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = $this->makeOpd('UJI');
    }

    private function deleteCounter(string $jenis, string $periode): void
    {
        DB::table('nomor_counters')
            ->where(['opd_id' => $this->opd->id, 'jenis' => $jenis, 'periode' => $periode])
            ->delete();
    }

    public function test_it_rejects_deleting_a_letter_counter(): void
    {
        $this->setCounter($this->opd->id, 'surat', '2026', 10);

        $this->assertTriggerRejects(fn () => $this->deleteCounter('surat', '2026'), self::MESSAGE);

        $this->assertSame(10, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_it_rejects_deleting_a_visit_counter(): void
    {
        $this->setCounter($this->opd->id, 'kunjungan', '202610', 25);

        $this->assertTriggerRejects(fn () => $this->deleteCounter('kunjungan', '202610'), self::MESSAGE);

        $this->assertSame(25, $this->counterValue($this->opd->id, 'kunjungan', '202610'));
    }

    public function test_it_rejects_a_bulk_delete_and_keeps_every_counter(): void
    {
        $this->setCounter($this->opd->id, 'surat', '2026', 10);
        $this->setCounter($this->opd->id, 'kunjungan', '202610', 25);

        $this->assertTriggerRejects(fn () => DB::table('nomor_counters')->delete(), self::MESSAGE);

        $this->assertSame(10, $this->counterValue($this->opd->id, 'surat', '2026'));
        $this->assertSame(25, $this->counterValue($this->opd->id, 'kunjungan', '202610'));
    }

    public function test_it_rejects_deleting_through_the_model(): void
    {
        $this->setCounter($this->opd->id, 'surat', '2026', 10);
        $counter = NomorCounter::query()->withoutGlobalScope('opd')->where('opd_id', $this->opd->id)->firstOrFail();

        $this->assertTriggerRejects(fn () => $counter->delete(), self::MESSAGE);

        $this->assertSame(10, $this->counterValue($this->opd->id, 'surat', '2026'));
    }

    public function test_deleting_a_counter_cannot_restart_the_visit_numbering(): void
    {
        $this->insertVisit($this->opd->id, '2026-10-15 09:00:00');

        $this->assertTriggerRejects(fn () => $this->deleteCounter('kunjungan', '202610'), self::MESSAGE);

        $second = $this->insertVisit($this->opd->id, '2026-10-15 09:10:00');

        $this->assertSame('20261015-0002', $this->rawRow('kunjungan', $second)['nomor_kunjungan']);
        $this->assertSame(2, $this->counterValue($this->opd->id, 'kunjungan', '202610'));
    }

    public function test_deleting_a_counter_cannot_restart_the_letter_numbering(): void
    {
        $userId = $this->makeUser(null, 'super_admin')->id;
        $formatId = $this->insertFormat($this->opd->id, $userId);
        $this->setCounter($this->opd->id, 'surat', '2026', 5);

        $this->assertTriggerRejects(fn () => $this->deleteCounter('surat', '2026'), self::MESSAGE);

        $letter = $this->insertLetter(['opd_id' => $this->opd->id, 'format_nomor_surat_id' => $formatId, 'dibuat_oleh' => $userId]);

        $this->assertSame(6, (int) $this->rawRow('nomor_surat', $letter)['nomor_urut']);
    }
}
