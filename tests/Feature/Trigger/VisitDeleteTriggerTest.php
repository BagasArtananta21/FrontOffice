<?php

namespace Tests\Feature\Trigger;

use App\Models\Kunjungan;
use App\Models\Opd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\AssertsTriggerErrors;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class VisitDeleteTriggerTest extends TestCase
{
    use AssertsTriggerErrors, BuildsFixtures, RefreshDatabase;

    private const MESSAGE = 'Data kunjungan tidak boleh dihapus';

    private Opd $opd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = $this->makeOpd('UJI');
    }

    private function visit(array $overrides = []): string
    {
        return $this->insertVisit($this->opd->id, '2026-10-15 09:00:00', $overrides);
    }

    public function test_it_rejects_deleting_a_visit(): void
    {
        $id = $this->visit();
        $before = $this->rawRow('kunjungan', $id);

        $this->assertTriggerRejects(fn () => DB::table('kunjungan')->where('id', $id)->delete(), self::MESSAGE);

        $this->assertSame($before, $this->rawRow('kunjungan', $id));
    }

    public function test_it_rejects_deleting_a_visit_with_a_signature(): void
    {
        $id = $this->visit(['tanda_tangan' => 'tanda-tangan/2026/10/a.png']);

        $this->assertTriggerRejects(fn () => DB::table('kunjungan')->where('id', $id)->delete(), self::MESSAGE);

        $this->assertDatabaseHas('kunjungan', ['id' => $id, 'tanda_tangan' => 'tanda-tangan/2026/10/a.png']);
    }

    public function test_it_rejects_deleting_through_the_model(): void
    {
        $id = $this->visit();
        $visit = Kunjungan::query()->withoutGlobalScope('opd')->findOrFail($id);

        $this->assertTriggerRejects(fn () => $visit->delete(), self::MESSAGE);

        $this->assertDatabaseHas('kunjungan', ['id' => $id]);
    }

    public function test_it_rejects_a_bulk_delete_and_keeps_every_row(): void
    {
        $this->visit();
        $this->visit();
        $this->visit();

        $this->assertTriggerRejects(fn () => DB::table('kunjungan')->delete(), self::MESSAGE);

        $this->assertSame(3, DB::table('kunjungan')->count());
    }

    public function test_it_keeps_the_pointer_of_a_display_that_waits_for_the_visit(): void
    {
        $id = $this->visit();
        $deviceId = $this->insertDisplay($this->opd->id, 'token-uji', ['kunjungan_menunggu_id' => $id]);

        $this->assertTriggerRejects(fn () => DB::table('kunjungan')->where('id', $id)->delete(), self::MESSAGE);

        $this->assertSame($id, $this->rawRow('display_devices', $deviceId)['kunjungan_menunggu_id']);
    }

    public function test_a_rejected_delete_leaves_the_numbering_alone(): void
    {
        $first = $this->visit();

        $this->assertTriggerRejects(fn () => DB::table('kunjungan')->where('id', $first)->delete(), self::MESSAGE);

        $second = $this->visit();

        $this->assertSame('20261015-0001', $this->rawRow('kunjungan', $first)['nomor_kunjungan']);
        $this->assertSame('20261015-0002', $this->rawRow('kunjungan', $second)['nomor_kunjungan']);
        $this->assertSame(2, $this->counterValue($this->opd->id, 'kunjungan', '202610'));
    }
}
