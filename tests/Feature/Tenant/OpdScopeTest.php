<?php

namespace Tests\Feature\Tenant;

use App\Models\Bidang;
use App\Models\DisplayDevice;
use App\Models\FormatNomorSurat;
use App\Models\Kunjungan;
use App\Models\NomorCounter;
use App\Models\NomorSurat;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BuildsTwoTenants;
use Tests\TestCase;

class OpdScopeTest extends TestCase
{
    use BuildsTwoTenants, RefreshDatabase;

    private const MODELS = [
        'bidang' => Bidang::class,
        'kunjungan' => Kunjungan::class,
        'display device' => DisplayDevice::class,
        'format nomor surat' => FormatNomorSurat::class,
        'nomor surat' => NomorSurat::class,
        'nomor counter' => NomorCounter::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
    }

    public static function models(): array
    {
        $cases = [];

        foreach (self::MODELS as $label => $class) {
            $cases[$label] = [$class];
        }

        return $cases;
    }

    public static function modelsPerTenant(): array
    {
        $cases = [];

        foreach (self::MODELS as $label => $class) {
            foreach (['a', 'b'] as $tenant) {
                $cases["{$label} as {$tenant}"] = [$class, $tenant];
            }
        }

        return $cases;
    }

    private function table(string $class): string
    {
        return (new $class())->getTable();
    }

    private function unsavedModel(string $class): Model
    {
        $attributes = match ($class) {
            Bidang::class => ['kode_bidang' => 'BARU', 'nama_bidang' => 'Bidang Baru', 'aktif' => true],
            Kunjungan::class => [
                'nama_tamu' => 'Tamu Baru',
                'jenis_kelamin' => 'laki_laki',
                'keperluan' => 'Uji',
                'waktu_datang' => '2026-11-03 09:00:00',
                'sumber_input' => 'frontoffice',
            ],
            DisplayDevice::class => ['nama' => 'PC Baru', 'token_hash' => hash('sha256', 'token-baru'), 'aktif' => true],
            FormatNomorSurat::class => [
                'susunan' => [['jenis' => 'urut']],
                'berlaku_sejak' => '2030-01-01',
                'dibuat_oleh' => $this->superAdmin->id,
            ],
            NomorSurat::class => [
                'format_nomor_surat_id' => $this->rowIdsOf('format_nomor_surat', $this->opdA)[0],
                'tahun' => 2030,
                'kode_klasifikasi' => '800',
                'perihal' => 'Perihal baru',
                'tanggal_surat' => '2030-02-01',
                'tanggal_terbit' => '2030-02-01 09:00:00',
                'dibuat_oleh' => $this->superAdmin->id,
            ],
            NomorCounter::class => ['jenis' => 'surat', 'periode' => '2031', 'nomor_terakhir' => 1],
        };

        return (new $class())->forceFill($attributes);
    }

    private function assertCreationRefused(Model $model): void
    {
        $table = $model->getTable();
        $before = DB::table($table)->count();

        try {
            $model->save();
            $this->fail('Pembuatan data seharusnya ditolak.');
        } catch (AuthorizationException) {
            $this->assertSame($before, DB::table($table)->count());
        }
    }

    #[DataProvider('modelsPerTenant')]
    public function test_it_returns_only_rows_of_the_logged_in_users_opd(string $class, string $tenant): void
    {
        [$own, $other, $user] = $tenant === 'a'
            ? [$this->opdA, $this->opdB, $this->adminA]
            : [$this->opdB, $this->opdA, $this->adminB];
        $table = $this->table($class);
        $this->assertNotEmpty($this->rowIdsOf($table, $own));
        $this->assertNotEmpty($this->rowIdsOf($table, $other));

        $this->actingAs($user);

        $this->assertSame($this->rowIdsOf($table, $own), $class::query()->orderBy('id')->pluck('id')->all());
        $this->assertSame(count($this->rowIdsOf($table, $own)), $class::count());
    }

    #[DataProvider('modelsPerTenant')]
    public function test_find_of_another_opds_id_returns_null_and_find_of_an_own_id_works(string $class, string $tenant): void
    {
        [$own, $other, $user] = $tenant === 'a'
            ? [$this->opdA, $this->opdB, $this->adminA]
            : [$this->opdB, $this->opdA, $this->adminB];
        $table = $this->table($class);

        $this->actingAs($user);

        foreach ($this->rowIdsOf($table, $other) as $foreignId) {
            $this->assertNull($class::find($foreignId));
            $this->assertNotNull($class::withoutGlobalScope('opd')->find($foreignId));
        }

        foreach ($this->rowIdsOf($table, $own) as $ownId) {
            $this->assertSame($ownId, $class::find($ownId)?->id);
        }
    }

    #[DataProvider('models')]
    public function test_it_cannot_update_or_delete_rows_of_another_opd(string $class): void
    {
        $table = $this->table($class);
        $foreign = $this->rowIdsOf($table, $this->opdB);
        $snapshot = DB::table($table)->whereIn('id', $foreign)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();

        $this->actingAs($this->adminA);

        $this->assertSame(0, $class::query()->whereKey($foreign)->update(['updated_at' => '2020-01-01 00:00:00']));
        $this->assertSame(0, $class::query()->whereKey($foreign)->delete());

        $after = DB::table($table)->whereIn('id', $foreign)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $this->assertSame($snapshot, $after);
    }

    #[DataProvider('models')]
    public function test_without_any_context_nothing_is_visible(string $class): void
    {
        $this->assertGreaterThan(0, $class::withoutGlobalScope('opd')->count());

        $this->assertSame(0, $class::count());
    }

    #[DataProvider('models')]
    public function test_a_bound_opd_context_scopes_queries_without_a_user(string $class): void
    {
        app()->instance('current_opd_id', $this->opdB->id);
        $table = $this->table($class);

        $this->assertSame($this->rowIdsOf($table, $this->opdB), $class::query()->orderBy('id')->pluck('id')->all());
    }

    #[DataProvider('models')]
    public function test_a_super_admin_without_context_sees_every_opd(string $class): void
    {
        $this->actingAs($this->superAdmin);
        $table = $this->table($class);

        $this->assertSame(DB::table($table)->count(), $class::count());
        $this->assertGreaterThan(count($this->rowIdsOf($table, $this->opdA)), $class::count());
    }

    #[DataProvider('models')]
    public function test_a_bound_context_narrows_a_super_admin_to_that_opd(string $class): void
    {
        $this->actingAs($this->superAdmin);
        app()->instance('current_opd_id', $this->opdA->id);
        $table = $this->table($class);

        $this->assertSame($this->rowIdsOf($table, $this->opdA), $class::query()->orderBy('id')->pluck('id')->all());
    }

    public function test_across_opd_scope_works_for_a_super_admin(): void
    {
        $this->actingAs($this->superAdmin);

        $this->assertSame(4, Bidang::acrossOpd()->count());
    }

    public function test_across_opd_scope_is_refused_for_an_admin_fo(): void
    {
        $this->actingAs($this->adminA);

        $this->expectException(AuthorizationException::class);

        Bidang::acrossOpd();
    }

    public function test_across_opd_scope_is_refused_without_a_user(): void
    {
        $this->expectException(AuthorizationException::class);

        Bidang::acrossOpd();
    }

    #[DataProvider('models')]
    public function test_creating_fills_opd_id_from_the_logged_in_user(string $class): void
    {
        $this->actingAs($this->adminB);

        $model = $this->unsavedModel($class);
        $model->save();

        $this->assertSame($this->opdB->id, $model->opd_id);
        $this->assertSame($this->opdB->id, DB::table($model->getTable())->where('id', $model->id)->value('opd_id'));
    }

    #[DataProvider('models')]
    public function test_creating_fills_opd_id_from_a_bound_context(string $class): void
    {
        app()->instance('current_opd_id', $this->opdA->id);

        $model = $this->unsavedModel($class);
        $model->save();

        $this->assertSame($this->opdA->id, DB::table($model->getTable())->where('id', $model->id)->value('opd_id'));
    }

    #[DataProvider('models')]
    public function test_creating_accepts_an_explicit_opd_id_that_matches_the_context(string $class): void
    {
        $this->actingAs($this->adminA);

        $model = $this->unsavedModel($class);
        $model->forceFill(['opd_id' => $this->opdA->id])->save();

        $this->assertSame($this->opdA->id, DB::table($model->getTable())->where('id', $model->id)->value('opd_id'));
    }

    #[DataProvider('models')]
    public function test_creating_refuses_an_explicit_opd_id_that_differs_from_the_context(string $class): void
    {
        $this->actingAs($this->adminA);

        $model = $this->unsavedModel($class)->forceFill(['opd_id' => $this->opdB->id]);

        $this->assertCreationRefused($model);
    }

    #[DataProvider('models')]
    public function test_creating_without_any_context_is_refused(string $class): void
    {
        $this->assertCreationRefused($this->unsavedModel($class));
    }

    #[DataProvider('models')]
    public function test_creating_is_refused_for_an_admin_without_an_opd(string $class): void
    {
        $this->actingAs($this->makeUser(null, 'admin_fo'));

        $this->assertCreationRefused($this->unsavedModel($class));
    }

    #[DataProvider('models')]
    public function test_a_super_admin_creates_into_the_opd_named_explicitly(string $class): void
    {
        $this->actingAs($this->superAdmin);

        $model = $this->unsavedModel($class)->forceFill(['opd_id' => $this->opdB->id]);
        $model->save();

        $this->assertSame($this->opdB->id, DB::table($model->getTable())->where('id', $model->id)->value('opd_id'));
    }

    #[DataProvider('models')]
    public function test_a_super_admin_must_name_an_opd_when_creating(string $class): void
    {
        $this->actingAs($this->superAdmin);

        $this->assertCreationRefused($this->unsavedModel($class));
    }

    #[DataProvider('models')]
    public function test_a_bound_context_still_refuses_a_different_explicit_opd_for_a_super_admin(string $class): void
    {
        $this->actingAs($this->superAdmin);
        app()->instance('current_opd_id', $this->opdA->id);

        $this->assertCreationRefused($this->unsavedModel($class)->forceFill(['opd_id' => $this->opdB->id]));
    }
}
