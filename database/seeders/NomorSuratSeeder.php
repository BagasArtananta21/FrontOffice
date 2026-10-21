<?php

namespace Database\Seeders;

use App\Models\Bidang;
use App\Models\FormatNomorSurat;
use App\Models\NomorCounter;
use App\Models\NomorSurat;
use App\Models\Opd;
use App\Models\User;
use App\Services\LetterNumberFormatter;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Services\LetterNumberService;

class NomorSuratSeeder extends Seeder
{
    private const TOTAL = 100;

    private const DAYS_BACK = 90;

    private const LAST_NUMBER = 1256;

    private const KLASIFIKASI = ['000.1', '005', '100.3.4', '400.7', '443.32', '800.1.11', '900.1', '054', '094.2'];

    private const PERIHAL = [
        'Undangan rapat koordinasi',
        'Permohonan data statistik sektoral',
        'Pemberitahuan pemeliharaan jaringan',
        'Permohonan akun email dinas',
        'Rekomendasi pembuatan website desa',
        'Laporan kegiatan bulanan',
        'Permohonan liputan kegiatan',
        'Pemberitahuan perubahan jadwal layanan',
        'Permohonan narasumber',
        'Tindak lanjut hasil audit keamanan informasi',
        'Surat Tugas',
        'SPD',
    ];

    private const TUJUAN = [
        'Kepala Dinas Pendidikan',
        'Camat Sukasada',
        'Sekretaris Daerah',
        'Kepala Desa Panji',
        'Bappeda Kabupaten Buleleng',
        'BPS Kabupaten Buleleng',
    ];

    public function run(): void
    {
        $opd = Opd::where('kode_opd', 'DISKOMINFOSANTI')->firstOrFail();
        $adminFo = User::where('email', 'adminfo@example.com')->firstOrFail();

        app()->instance('current_opd_id', $opd->id);
        app(LetterNumberService::class)->setLastNumber($opd->id, self::LAST_NUMBER);

        $format = FormatNomorSurat::query()->berlakuPada(now())->firstOrFail();
        $bidangIds = Bidang::active()->pluck('id');
        $formatter = app(LetterNumberFormatter::class);

        $lastNumber = (int) NomorCounter::query()
            ->where('jenis', 'surat')
            ->where('periode', (string) now()->year)
            ->value('nomor_terakhir');

        $earliest = today()->subDays(self::DAYS_BACK)->max(now()->startOfYear());
        $workdays = collect(CarbonPeriod::create($earliest, today()))
            ->reject(fn (Carbon $day) => $day->isWeekend())
            ->values();

        $issuedAt = collect(range(1, self::TOTAL))
            ->map(fn () => $this->issuedAt($workdays->random()))
            ->sort()
            ->values();

        foreach ($issuedAt as $terbit) {
            $nomorSurat = new NomorSurat([
                'kode_klasifikasi' => fake()->randomElement(self::KLASIFIKASI),
                'bidang_id' => $bidangIds->random(),
                'perihal' => fake()->randomElement(self::PERIHAL),
                'tujuan_surat' => fake()->boolean(70) ? fake()->randomElement(self::TUJUAN) : null,
                'nama_peminta' => fake()->name(),
                'tanggal_surat' => $terbit->toDateString(),
            ]);

            $nomorSurat->forceFill([
                'format_nomor_surat_id' => $format->id,
                'tahun' => $terbit->year,
                'nomor_urut' => 0,
                'tanggal_terbit' => $terbit,
                'status' => NomorSurat::STATUS_TERBIT,
                'dibuat_oleh' => $adminFo->id,
            ])->save();

            $nomorSurat->refresh();
            $nomorSurat->forceFill([
                'nomor_lengkap' => $formatter->format($format->susunan, [
                    'klasifikasi' => $nomorSurat->kode_klasifikasi,
                    'nomor_urut' => $nomorSurat->nomor_urut,
                    'sub_nomor' => $nomorSurat->sub_nomor,
                    'bidang' => $nomorSurat->bidang?->kode_bidang,
                    'kode_opd' => $nomorSurat->opd->kode_opd,
                    'tanggal' => $nomorSurat->tanggal_surat,
                ]),
            ])->save();
        }

        app()->forgetInstance('current_opd_id');

        $this->command->info('Nomor surat dibuat: '.self::TOTAL.' (nomor '.($lastNumber + 1).' sampai '.($lastNumber + self::TOTAL).')');
    }

    private function issuedAt(Carbon $day): Carbon
    {
        $time = $day->copy()->setTime(8, 0)->addMinutes(fake()->numberBetween(0, 450));

        return $time->isFuture()
            ? now()->subMinutes(fake()->numberBetween(1, 120))
            : $time;
    }
}
