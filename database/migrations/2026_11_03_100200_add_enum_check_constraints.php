<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONSTRAINTS = [
        'kunjungan' => [
            'kunjungan_jenis_kelamin_check' => "jenis_kelamin IN ('laki_laki', 'perempuan')",
            'kunjungan_sumber_input_check' => "sumber_input IN ('display', 'frontoffice')",
            'kunjungan_alasan_tanpa_ttd_check' => "alasan_tanpa_ttd IN ('display_tidak_tersedia', 'tamu_sudah_pergi', 'tamu_menolak', 'waktu_habis')",
        ],
        'nomor_surat' => [
            'nomor_surat_status_check' => "status IN ('terbit', 'batal')",
        ],
    ];

    public function up(): void
    {
        foreach (self::CONSTRAINTS as $table => $constraints) {
            foreach ($constraints as $name => $condition) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$condition})");
            }
        }
    }

    public function down(): void
    {
        foreach (self::CONSTRAINTS as $table => $constraints) {
            foreach (array_keys($constraints) as $name) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$name}");
            }
        }
    }
};
