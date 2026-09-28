<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Opd;
use App\Models\Bidang;

class BidangSeeder extends Seeder
{
    public const BIDANG = [
        'Tata Kelola dan Sumber Daya Manusia Sistem Pemerintahan Berbasis Elektronik',
        'Infrastruktur dan Layanan Sistem Pemerintahan Berbasis Elektronik',
        'Persandian dan Statistik',
        'Pengelolaan Komunikasi Publik',
        'Pengelolaan dan Layanan Informasi Publik',
    ];

    public function run(): void
    {
        $opd = Opd::where('kode_opd', 'DISKOMINFOSANTI')->firstOrFail();

        app()->instance('current_opd_id', $opd->id);

        foreach (self::BIDANG as $nama) {
            Bidang::updateOrCreate([
                'nama_bidang' => $nama,
                'aktif' => true,
            ]);
        }

        app()->forgetInstance('current_opd_id');
    }
}
