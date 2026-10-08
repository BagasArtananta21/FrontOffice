<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Opd;
use App\Models\Bidang;

class BidangSeeder extends Seeder
{
    public const BIDANG = [
        'Tata Kelola dan Sumber Daya Manusia Sistem Pemerintahan Berbasis Elektronik' => 'TATAKELOLA',
        'Infrastruktur dan Layanan Sistem Pemerintahan Berbasis Elektronik' => 'INFRASTRUKTUR',
        'Persandian dan Statistik' => 'PERSANDIAN',
        'Pengelolaan Komunikasi Publik' => 'PKP',
        'Pengelolaan dan Layanan Informasi Publik' => 'PLIP',
        'Kesekretariatan' => 'SEKRE',
    ];

    public function run(): void
    {
        $opd = Opd::where('kode_opd', 'DISKOMINFOSANTI')->firstOrFail();

        app()->instance('current_opd_id', $opd->id);

        foreach (self::BIDANG as $nama => $kode) {
            Bidang::updateOrCreate(
                ['nama_bidang' => $nama],
                ['kode_bidang' => $kode, 'aktif' => true]
            );
        }

        app()->forgetInstance('current_opd_id');
    }
}
