<?php

namespace Tests\Feature\Tenant;

use App\Models\Opd;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class UniqueConstraintTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    private Opd $opdA;

    private Opd $opdB;

    private string $creatorId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opdA = $this->makeOpd('AAA');
        $this->opdB = $this->makeOpd('BBB');
        $this->creatorId = $this->makeUser(null, 'super_admin')->id;
    }

    private function assertDuplicateRejected(callable $statement): void
    {
        try {
            $statement();
            $this->fail('Constraint unik seharusnya menolak data ganda.');
        } catch (QueryException $e) {
            $this->assertSame('23000', (string) $e->getCode());
        }
    }

    public function test_kode_bidang_may_repeat_across_opds(): void
    {
        $this->insertBidang($this->opdA->id, 'PKP');
        $this->insertBidang($this->opdB->id, 'PKP');

        $this->assertSame(2, DB::table('bidang')->where('kode_bidang', 'PKP')->count());
    }

    public function test_kode_bidang_cannot_repeat_inside_one_opd(): void
    {
        $this->insertBidang($this->opdA->id, 'PKP');

        $this->assertDuplicateRejected(fn () => $this->insertBidang($this->opdA->id, 'PKP'));

        $this->assertSame(1, DB::table('bidang')->where('opd_id', $this->opdA->id)->count());
        $this->insertBidang($this->opdA->id, 'SDP');
        $this->assertSame(2, DB::table('bidang')->where('opd_id', $this->opdA->id)->count());
    }

    public function test_a_format_start_date_may_repeat_across_opds_but_not_inside_one(): void
    {
        $this->insertFormat($this->opdA->id, $this->creatorId, '2026-01-01');
        $this->insertFormat($this->opdB->id, $this->creatorId, '2026-01-01');

        $this->assertDuplicateRejected(fn () => $this->insertFormat($this->opdA->id, $this->creatorId, '2026-01-01'));

        $this->assertSame(1, DB::table('format_nomor_surat')->where('opd_id', $this->opdA->id)->count());
    }

    public function test_a_counter_is_unique_per_opd_kind_and_period(): void
    {
        $this->setCounter($this->opdA->id, 'surat', '2026', 5);
        $this->setCounter($this->opdB->id, 'surat', '2026', 9);
        $this->setCounter($this->opdA->id, 'surat', '2027', 1);
        $this->setCounter($this->opdA->id, 'kunjungan', '202610', 3);

        $this->assertDuplicateRejected(fn () => DB::table('nomor_counters')->insert([
            'id' => $this->newId(),
            'opd_id' => $this->opdA->id,
            'jenis' => 'surat',
            'periode' => '2026',
            'nomor_terakhir' => 1,
        ]));

        $this->assertSame(5, $this->counterValue($this->opdA->id, 'surat', '2026'));
        $this->assertSame(9, $this->counterValue($this->opdB->id, 'surat', '2026'));
        $this->assertSame(4, DB::table('nomor_counters')->count());
    }

    public function test_kode_opd_is_unique_across_the_whole_system(): void
    {
        $this->assertDuplicateRejected(fn () => $this->makeOpd('AAA'));

        $this->assertSame(1, DB::table('opd')->where('kode_opd', 'AAA')->count());
    }

    public function test_email_is_unique_across_opds(): void
    {
        $first = $this->makeUser($this->opdA);

        $this->assertDuplicateRejected(function () use ($first) {
            $second = $this->makeUser($this->opdB);
            DB::table('users')->where('id', $second->id)->update(['email' => $first->email]);
        });
    }

    public function test_display_token_hash_is_unique_across_opds(): void
    {
        $this->insertDisplay($this->opdA->id, 'token-sama');

        $this->assertDuplicateRejected(fn () => $this->insertDisplay($this->opdB->id, 'token-sama'));

        $this->assertSame(1, DB::table('display_devices')->count());
    }
}
