<?php

namespace Tests\Feature\Numbering;

use App\Models\Opd;
use App\Services\LetterNumberService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class SetLastNumberTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    private const LIMIT = 99999;

    private Opd $opd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = Opd::create(['kode_opd' => 'UJI', 'nama_opd' => 'OPD Uji']);
    }

    private function service(): LetterNumberService
    {
        return app(LetterNumberService::class);
    }

    private function assertCounterIs(int $expected): void
    {
        $this->assertDatabaseHas('nomor_counters', [
            'opd_id' => $this->opd->id,
            'jenis' => 'surat',
            'periode' => (string) now()->year,
            'nomor_terakhir' => $expected,
        ]);
    }

    public function test_it_stores_a_number_within_the_limit(): void
    {
        $this->service()->setLastNumber($this->opd->id, 150);

        $this->assertCounterIs(150);
    }

    public function test_it_accepts_the_maximum(): void
    {
        $this->service()->setLastNumber($this->opd->id, self::LIMIT);

        $this->assertCounterIs(self::LIMIT);
    }

    public function test_it_rejects_a_number_above_the_maximum(): void
    {
        try {
            $this->service()->setLastNumber($this->opd->id, self::LIMIT + 1);
            $this->fail('Service seharusnya menolak angka di atas batas.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('nomor_counters', 0);
        }
    }

    public function test_it_rejects_a_negative_number(): void
    {
        try {
            $this->service()->setLastNumber($this->opd->id, -1);
            $this->fail('Service seharusnya menolak angka negatif.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('nomor_counters', 0);
        }
    }

    public function test_it_still_refuses_to_lower_the_counter(): void
    {
        $this->service()->setLastNumber($this->opd->id, 200);

        try {
            $this->service()->setLastNumber($this->opd->id, 100);
            $this->fail('Trigger seharusnya menolak penurunan counter.');
        } catch (QueryException $e) {
            $this->assertSame('45000', $e->getCode());
        }

        $this->assertCounterIs(200);
    }

    public function test_it_refuses_another_opd_when_a_tenant_context_is_bound(): void
    {
        $other = Opd::create(['kode_opd' => 'LAIN', 'nama_opd' => 'OPD Lain']);
        app()->instance('current_opd_id', $this->opd->id);

        try {
            $this->service()->setLastNumber($other->id, 150);
            $this->fail('Service seharusnya menolak OPD di luar konteks aktif.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('nomor_counters', 0);
        }
    }

    public function test_it_refuses_another_opd_for_a_logged_in_officer(): void
    {
        $other = Opd::create(['kode_opd' => 'LAIN', 'nama_opd' => 'OPD Lain']);
        $this->actingAs($this->makeUser($this->opd));

        try {
            $this->service()->setLastNumber($other->id, 150);
            $this->fail('Service seharusnya menolak OPD milik petugas lain.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('nomor_counters', 0);
        }
    }

    public function test_it_accepts_the_own_opd_for_a_logged_in_officer(): void
    {
        $this->actingAs($this->makeUser($this->opd));

        $this->service()->setLastNumber($this->opd->id, 150);

        $this->assertCounterIs(150);
    }
}
