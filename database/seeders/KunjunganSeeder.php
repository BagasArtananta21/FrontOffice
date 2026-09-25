<?php

namespace Database\Seeders;

use App\Models\Bidang;
use App\Models\Kunjungan;
use App\Models\Opd;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class KunjunganSeeder extends Seeder
{
    private const TOTAL = 200;

    private const TOTAL_TODAY = 15;

    private const KEPERLUAN = [
        'Koordinasi pengembangan aplikasi',
        'Konsultasi pembuatan website OPD',
        'Permohonan data statistik sektoral',
        'Pengajuan liputan kegiatan',
        'Rapat persiapan kegiatan',
        'Menyerahkan surat undangan',
        'Konsultasi jaringan internet desa',
        'Permohonan akun email dinas',
        'Audiensi dengan Kepala Dinas',
        'Pengambilan dokumen',
        'Wawancara penelitian mahasiswa',
        'Permohonan informasi publik',
        'Pengajuan magang/PKL',
        'Koordinasi persandian',
        'Keluhan layanan aplikasi',
    ];

    private const INSTANSI = [
        'Dinas Pendidikan Kab. Buleleng',
        'Bappeda Kab. Buleleng',
        'BPS Kabupaten Buleleng',
        'Universitas Pendidikan Ganesha',
        'STIKOM Bali',
        'Kantor Desa Panji',
        'Kantor Camat Sukasada',
        'PT Telkom Indonesia',
        'RRI Singaraja',
        'Bali Post',
    ];

    private const CATATAN = [
        'Pegawai sedang rapat, tamu diminta menunggu',
        'Sudah ada janji sebelumnya',
        'Diarahkan ke ruang rapat lantai 2',
        'Pegawai sedang dinas luar',
    ];

    public function run(): void
    {
        $opd = Opd::where('kode_opd', 'DISKOMINFOSANTI')->firstOrFail();
        $adminFo = User::where('email', 'adminfo@example.com')->firstOrFail();

        app()->instance('current_opd_id', $opd->id);

        $bidangIds = Bidang::active()->pluck('id');
        $pegawai = Pegawai::active()->get(['id', 'bidang_id']);

        foreach (range(1, self::TOTAL) as $index) {
            $bidangId = fake()->boolean(80) ? $bidangIds->random() : null;

            $candidates = $pegawai->filter(
                fn (Pegawai $item) => $bidangId === null
                    || $item->bidang_id === $bidangId
                    || $item->bidang_id === null
            );

            $fromDisplay = fake()->boolean(80);

            $kunjungan = new Kunjungan([
                'nama_tamu' => fake()->name(),
                'jenis_kelamin' => fake()->randomElement(array_keys(Kunjungan::JENIS_KELAMIN)),
                'no_hp' => fake()->boolean(60) ? '08'.fake()->numerify('##########') : null,
                'instansi_asal' => fake()->boolean(60) ? fake()->randomElement(self::INSTANSI) : null,
                'alamat' => fake()->boolean(30) ? fake()->address() : null,
                'bidang_id' => $bidangId,
                'pegawai_id' => fake()->boolean(60) && $candidates->isNotEmpty() ? $candidates->random()->id : null,
                'keperluan' => fake()->randomElement(self::KEPERLUAN),
                'catatan_petugas' => fake()->boolean(15) ? fake()->randomElement(self::CATATAN) : null,
                'sudah_dihubungi' => fake()->boolean(70),
                'waktu_datang' => $this->arrivalTime($index <= self::TOTAL_TODAY),
            ]);

            $kunjungan->forceFill([
                'sumber_input' => $fromDisplay ? Kunjungan::SUMBER_DISPLAY : Kunjungan::SUMBER_FRONT_OFFICE,
                'dicatat_oleh' => $fromDisplay ? null : $adminFo->id,
            ])->save();
        }

        app()->forgetInstance('current_opd_id');

        $this->command->info('Kunjungan dibuat: '.self::TOTAL);
    }

    private function arrivalTime(bool $today): Carbon
    {
        if ($today) {
            return now()->subMinutes(fake()->numberBetween(5, 300));
        }

        $date = today()->subDays(fake()->numberBetween(1, 60));

        while ($date->isWeekend()) {
            $date->subDay();
        }

        return $date->setTime(8, 0)->addMinutes(fake()->numberBetween(0, 450));
    }
}
