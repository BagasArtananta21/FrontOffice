<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\Bidang;
use App\Models\Pegawai;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class PegawaiSeeder extends Seeder
{
    public function run(): void
    {
        $opd = Opd::where('kode_opd', 'DISKOMINFOSANTI')->firstOrFail();

        app()->instance('current_opd_id', $opd->id);

        $bidang = Bidang::pluck('id', 'nama_bidang');
        $path = database_path('data/data_pegawai.csv');

        if (! is_readable($path)) {
            throw new RuntimeException("File tidak ditemukan: {$path}");
        }

        $handle = fopen($path, 'r');
        $jumlah = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $nama = trim($row[3] ?? '');
            $nip = trim($row[4] ?? '');
            $jabatan = trim($row[5] ?? '');

            if ($nama === '' || ! ctype_digit($nip)) {
                continue;
            }

            Pegawai::updateOrCreate(
                ['nip' => $nip],
                [
                    'nama_pegawai' => trim($nama, " ,"),
                    'jabatan' => $jabatan,
                    'bidang_id' => $this->bidangId($jabatan, $bidang),
                    'aktif' => true,
                ],
            );

            $jumlah++;
        }

        fclose($handle);

        app()->forgetInstance('current_opd_id');

        $this->command->info("Pegawai tersimpan: {$jumlah}");
    }

    private function bidangId(string $jabatan, Collection $bidang): ?string
    {
        if (! Str::startsWith(Str::lower($jabatan), 'kepala bidang ')) {
            return null;
        }

        return $bidang->get(Str::after($jabatan, 'Kepala Bidang '));
    }
}
