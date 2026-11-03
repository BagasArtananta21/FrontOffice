<?php

namespace Tests\Feature\Trigger;

use App\Models\Kunjungan;
use App\Models\NomorSurat;
use App\Models\Opd;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class EnumCheckConstraintTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    private Opd $opd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = $this->makeOpd('UJI');
    }
    
    private function assertCheckRejects(Closure $statement, string $constraint): void
    {
        try {
            $statement();
        } catch (QueryException $e) {
            $this->assertStringContainsString($constraint, $e->getMessage());

            return;
        }

        $this->fail("Constraint {$constraint} seharusnya menolak pernyataan ini.");
    }

    private function visit(array $overrides = []): string
    {
        return $this->insertVisit($this->opd->id, '2026-10-15 09:00:00', $overrides);
    }

    public static function acceptedGenders(): array
    {
        return array_map(fn (string $value) => [$value], array_keys(Kunjungan::JENIS_KELAMIN));
    }

    public static function acceptedSources(): array
    {
        return [[Kunjungan::SUMBER_DISPLAY], [Kunjungan::SUMBER_FRONT_OFFICE]];
    }

    public static function acceptedReasons(): array
    {
        return array_map(fn (string $value) => [$value], array_keys(Kunjungan::NO_SIGNATURE_REASONS));
    }

    #[DataProvider('acceptedGenders')]
    public function test_it_accepts_every_gender_the_application_knows(string $gender): void
    {
        $id = $this->visit(['jenis_kelamin' => $gender]);

        $this->assertSame($gender, $this->rawRow('kunjungan', $id)['jenis_kelamin']);
    }

    #[DataProvider('acceptedSources')]
    public function test_it_accepts_every_input_source_the_application_knows(string $source): void
    {
        $id = $this->visit(['sumber_input' => $source]);

        $this->assertSame($source, $this->rawRow('kunjungan', $id)['sumber_input']);
    }

    #[DataProvider('acceptedReasons')]
    public function test_it_accepts_every_reason_without_signature_the_application_knows(string $reason): void
    {
        $id = $this->visit(['alasan_tanpa_ttd' => $reason]);

        $this->assertSame($reason, $this->rawRow('kunjungan', $id)['alasan_tanpa_ttd']);
    }

    public function test_it_accepts_a_visit_without_a_reason(): void
    {
        $id = $this->visit(['alasan_tanpa_ttd' => null]);

        $this->assertNull($this->rawRow('kunjungan', $id)['alasan_tanpa_ttd']);
    }

    public function test_it_rejects_an_unknown_gender_on_insert(): void
    {
        $this->assertCheckRejects(fn () => $this->visit(['jenis_kelamin' => 'L']), 'kunjungan_jenis_kelamin_check');

        $this->assertSame(0, DB::table('kunjungan')->count());
    }

    public function test_it_rejects_an_unknown_gender_on_update(): void
    {
        $id = $this->visit();

        $this->assertCheckRejects(
            fn () => DB::table('kunjungan')->where('id', $id)->update(['jenis_kelamin' => 'L']),
            'kunjungan_jenis_kelamin_check',
        );

        $this->assertSame('laki_laki', $this->rawRow('kunjungan', $id)['jenis_kelamin']);
    }

    public function test_it_rejects_an_unknown_input_source_on_insert(): void
    {
        $this->assertCheckRejects(fn () => $this->visit(['sumber_input' => 'api']), 'kunjungan_sumber_input_check');

        $this->assertSame(0, DB::table('kunjungan')->count());
    }

    public function test_it_rejects_an_unknown_reason_without_signature_on_insert(): void
    {
        $this->assertCheckRejects(
            fn () => $this->visit(['alasan_tanpa_ttd' => 'iseng']),
            'kunjungan_alasan_tanpa_ttd_check',
        );

        $this->assertSame(0, DB::table('kunjungan')->count());
    }

    public function test_it_rejects_an_unknown_reason_without_signature_on_update(): void
    {
        $id = $this->visit();

        $this->assertCheckRejects(
            fn () => DB::table('kunjungan')->where('id', $id)->update(['alasan_tanpa_ttd' => 'iseng']),
            'kunjungan_alasan_tanpa_ttd_check',
        );

        $this->assertNull($this->rawRow('kunjungan', $id)['alasan_tanpa_ttd']);
    }

    /**
     * The status triggers answer first, so the constraint cannot be reached from
     * SQL while they exist. It is the guard that remains if a server refuses
     * triggers, so only its presence and wording can be checked here.
     */
    public function test_the_letter_status_constraint_exists_and_names_both_statuses(): void
    {
        $clause = DB::selectOne(
            'select check_clause as clause from information_schema.check_constraints where constraint_schema = database() and constraint_name = ?',
            ['nomor_surat_status_check'],
        )?->clause;

        $this->assertNotNull($clause, 'Constraint nomor_surat_status_check tidak ditemukan.');
        $this->assertStringContainsString(NomorSurat::STATUS_TERBIT, $clause);
        $this->assertStringContainsString(NomorSurat::STATUS_BATAL, $clause);
    }
}
