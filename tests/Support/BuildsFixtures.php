<?php

namespace Tests\Support;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Small data builders for tests. The insert* helpers go straight to the
 * query builder so global scopes, model events and fillable rules never get
 * in the way (the numbering triggers live in the database anyway).
 */
trait BuildsFixtures
{
    private int $fixtureCounter = 0;

    protected function makeOpd(string $kodeOpd = 'UJI', bool $aktif = true): Opd
    {
        $opd = Opd::create(['kode_opd' => $kodeOpd, 'nama_opd' => "OPD {$kodeOpd}"]);

        if (! $aktif) {
            $opd->forceFill(['aktif' => false])->save();
        }

        return $opd->refresh();
    }

    protected function makeUser(?Opd $opd, string $role = 'admin_fo', bool $aktif = true, string $password = 'kata-sandi-uji-123'): User
    {
        $this->fixtureCounter++;

        $user = new User();
        $user->forceFill([
            'name' => "Pengguna Uji {$this->fixtureCounter}",
            'email' => 'pengguna'.$this->fixtureCounter.'-'.Str::lower(Str::random(6)).'@example.com',
            'password' => $password,
            'role' => $role,
            'opd_id' => $opd?->id,
            'aktif' => $aktif,
        ])->save();

        return $user->refresh();
    }

    protected function newId(): string
    {
        return (string) Str::uuid7();
    }

    protected function insertBidang(string $opdId, string $kodeBidang = 'PKP', bool $aktif = true): string
    {
        $id = $this->newId();

        DB::table('bidang')->insert([
            'id' => $id,
            'opd_id' => $opdId,
            'kode_bidang' => $kodeBidang,
            'nama_bidang' => "Bidang {$kodeBidang}",
            'aktif' => $aktif,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * @param  array<int, array<string, string>>|null  $susunan
     */
    protected function insertFormat(
        string $opdId,
        string $creatorId,
        string $berlakuSejak = '2026-01-01',
        bool $izinkanSusulan = false,
        ?array $susunan = null,
    ): string {
        $id = $this->newId();

        DB::table('format_nomor_surat')->insert([
            'id' => $id,
            'opd_id' => $opdId,
            'susunan' => json_encode($susunan ?? self::defaultSegments()),
            'berlaku_sejak' => $berlakuSejak,
            'izinkan_susulan' => $izinkanSusulan,
            'dibuat_oleh' => $creatorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * Same layout as the Diskominfosanti seeder: KLASIFIKASI/URUT/BIDANG/KODEOPD/BULAN/TAHUN.
     *
     * @return array<int, array<string, string>>
     */
    protected static function defaultSegments(): array
    {
        return [
            ['jenis' => 'klasifikasi'],
            ['jenis' => 'urut'],
            ['jenis' => 'bidang'],
            ['jenis' => 'kode_opd'],
            ['jenis' => 'bulan_romawi'],
            ['jenis' => 'tahun'],
        ];
    }

    /**
     * Inserts a visit and lets the kunjungan_before_insert trigger assign the number.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function insertVisit(string $opdId, string $waktuDatang, array $overrides = []): string
    {
        $id = $this->newId();

        DB::table('kunjungan')->insert([
            'id' => $id,
            'opd_id' => $opdId,
            'nama_tamu' => 'Tamu Uji',
            'jenis_kelamin' => 'laki_laki',
            'keperluan' => 'Keperluan uji',
            'waktu_datang' => $waktuDatang,
            'sumber_input' => 'frontoffice',
            'created_at' => now(),
            'updated_at' => now(),
            ...$overrides,
        ]);

        return $id;
    }

    /**
     * Inserts a letter number. Requires opd_id, format_nomor_surat_id and
     * dibuat_oleh in $attributes; nomor_urut stays 0 unless overridden, so the
     * nomor_surat_before_insert trigger hands out the next number.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function insertLetter(array $attributes): string
    {
        $id = $this->newId();

        DB::table('nomor_surat')->insert([
            'id' => $id,
            'tahun' => 2026,
            'kode_klasifikasi' => '443.32',
            'perihal' => 'Perihal uji',
            'tanggal_surat' => '2026-10-15',
            'tanggal_terbit' => '2026-10-15 09:00:00',
            'created_at' => now(),
            'updated_at' => now(),
            ...$attributes,
        ]);

        return $id;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function insertDisplay(string $opdId, string $plainToken, array $overrides = []): string
    {
        $id = $this->newId();

        DB::table('display_devices')->insert([
            'id' => $id,
            'opd_id' => $opdId,
            'nama' => 'PC Lobi',
            'token_hash' => hash('sha256', $plainToken),
            'tampilkan_form' => false,
            'aktif' => true,
            'terakhir_aktif' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            ...$overrides,
        ]);

        return $id;
    }

    protected function setCounter(string $opdId, string $jenis, string $periode, int $nomorTerakhir): void
    {
        DB::table('nomor_counters')->updateOrInsert(
            ['opd_id' => $opdId, 'jenis' => $jenis, 'periode' => $periode],
            [
                'id' => $this->newId(),
                'nomor_terakhir' => $nomorTerakhir,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    protected function counterValue(string $opdId, string $jenis, string $periode): ?int
    {
        $value = DB::table('nomor_counters')
            ->where(['opd_id' => $opdId, 'jenis' => $jenis, 'periode' => $periode])
            ->value('nomor_terakhir');

        return $value === null ? null : (int) $value;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rawRow(string $table, string $id): array
    {
        $row = DB::table($table)->where('id', $id)->first();

        $this->assertNotNull($row, "Baris {$id} tidak ditemukan di tabel {$table}.");

        return (array) $row;
    }
}
