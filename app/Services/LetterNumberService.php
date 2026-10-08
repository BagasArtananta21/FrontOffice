<?php

namespace App\Services;

use App\Exceptions\LetterFormatNotFoundException;
use App\Exceptions\LetterBaseNumberNotFoundException;
use App\Models\FormatNomorSurat;
use App\Models\NomorSurat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class LetterNumberService
{
    public function __construct(private LetterNumberFormatter $formatter)
    {
    }

    public function issue (array $data, User $petugas): NomorSurat
    {
        return DB::transaction(function () use ($data, $petugas) {
            $terbit = now();
            $format = FormatNomorSurat::query()->berlakuPada($terbit)->first();
            
            if ( ! $format) {
                throw new LetterFormatNotFoundException();
            }
            
            $tanggalSurat = Carbon::parse($data['tanggal_surat']);
            $batas = $tanggalSurat->copy()->endOfDay();
            $nomorInduk = 0;

            if ($tanggalSurat->lt(today())) {
                $induk = NomorSurat::query()
                    ->where('tahun', $tanggalSurat->year)
                    ->where('sub_nomor', 0);
                
                $sudahAdaSesudah = $induk->clone()->where('tanggal_terbit', '>', $batas)->exists();

                if ($sudahAdaSesudah) {
                    $nomorInduk = $induk->clone()->where('tanggal_terbit', '<=', $batas)->max('nomor_urut');
                    
                    if ($nomorInduk === null) {
                        throw new LetterBaseNumberNotFoundException();
                    }
                }
            }


            $nomorSurat = new NomorSurat($data);
            $nomorSurat->forceFill([
                'format_nomor_surat_id' => $format->id,
                'tahun' => $tanggalSurat->year,
                'nomor_urut' => $nomorInduk,
                'tanggal_terbit' => $terbit,
                'status' => NomorSurat::STATUS_TERBIT,
                'dibuat_oleh' => $petugas->id,
            ])->save();

            $nomorSurat->refresh();
            $nomorSurat->forceFill([
                'nomor_lengkap' => $this->formatter->format($format->susunan, [
                    'klasifikasi' => $nomorSurat->kode_klasifikasi,
                    'nomor_urut' => $nomorSurat->nomor_urut,
                    'sub_nomor' => $nomorSurat->sub_nomor,
                    'bidang' => $nomorSurat->bidang?->kode_bidang,
                    'kode_opd' => $nomorSurat->opd->kode_opd,
                    'tanggal' => $nomorSurat->tanggal_surat,
                ]),
            ])->save();

            return $nomorSurat;
        });
    }

    public function setLastNumber(string $opdId, int $nomorTerakhir): void
    {
        DB::table('nomor_counters')->upsert(
            [[
                'id' => (string) Str::uuid7(),
                'opd_id' => $opdId,
                'jenis' => 'surat',
                'periode' => (string) now()->year,
                'nomor_terakhir' => $nomorTerakhir,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['opd_id', 'jenis', 'periode'],
            ['nomor_terakhir', 'updated_at']
        );
    }
}