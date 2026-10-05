<?php

namespace Database\Seeders;

use App\Models\FormatNomorSurat;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Seeder;

class FormatNomorSuratSeeder extends Seeder
{
    public function run(): void
    {
        $opd = Opd::where('kode_opd', 'DISKOMINFOSANTI')->firstOrFail();
        $superAdmin = User::where('email', 'superadmin@example.com')->firstOrFail();

        app()->instance('current_opd_id', $opd->id);

        FormatNomorSurat::firstOrNew(['berlaku_sejak' => '2026-01-01'])
            ->forceFill([
                'susunan' => [
                    ['jenis' => 'klasifikasi'],
                    ['jenis' => 'urut'],
                    ['jenis' => 'bidang'],
                    ['jenis' => 'kode_opd'],
                    ['jenis' => 'bulan_romawi'],
                    ['jenis' => 'tahun'],
                ],
                'dasar_perubahan' => 'Format awal Diskominfosanti',
                'dibuat_oleh' => $superAdmin->id,
            ])->save();

        app()->forgetInstance('current_opd_id');
    }
}
