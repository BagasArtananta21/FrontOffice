<?php

namespace Database\Seeders;

use App\Models\Bidang;
use App\Models\FormatNomorSurat;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Seeder;

class DinkesSeeder extends Seeder
{
    public function run(): void
    {
        $opd = Opd::updateOrCreate(
            ['kode_opd' => 'DINKES'],
            [
                'nama_opd' => 'Dinas Kesehatan Kabupaten Buleleng',
                'alamat_opd' => 'Jalan Veteran',
            ],
        );

        $adminFo = User::firstOrNew(['email' => 'dinkes@example.com']);
        $adminFo->fill([
            'name' => 'Admin FO Dinkes',
            'password' => 'password',
        ]);
        $adminFo->role = 'admin_fo';
        $adminFo->opd_id = $opd->id;
        $adminFo->aktif = true;
        $adminFo->save();

        $superAdmin = User::where('email', 'superadmin@example.com')->firstOrFail();

        app()->instance('current_opd_id', $opd->id);

        foreach (range(1, 5) as $index) {
            Bidang::updateOrCreate(
                ['kode_bidang' => "TEST{$index}"],
                ['nama_bidang' => "Bidang {$index} Test", 'aktif' => true],
            );
        }

        FormatNomorSurat::firstOrNew(['berlaku_sejak' => '2026-01-01'])
            ->forceFill([
                'susunan' => [
                    ['jenis' => 'klasifikasi'],
                    ['jenis' => 'urut'],
                    ['jenis' => 'bulan_romawi'],
                    ['jenis' => 'tahun'],
                ],
                'dasar_perubahan' => 'Format awal Dinkes (data uji)',
                'izinkan_susulan' => false,
                'dibuat_oleh' => $superAdmin->id,
            ])->save();

        app()->forgetInstance('current_opd_id');
    }
}
