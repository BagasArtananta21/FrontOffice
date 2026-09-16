<?php

namespace Database\Seeders;

use App\Models\Opd;
use Illuminate\Database\Seeder;

class OpdSeeder extends Seeder
{
    public function run(): void
    {
        Opd::updateOrCreate(
            ['kode_opd' => 'DISKOMINFOSANTI'],
            [
                'nama_opd' => 'Dinas Komunikasi, Informatika, Persandian dan Statistik Kabupaten Buleleng',
                'alamat_opd' => 'Alamat placeholder',
            ],
        );
    }
}
