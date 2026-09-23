<?php

namespace App\Services;

use App\Models\Kunjungan;
use App\Models\Tamu;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class KunjunganService
{
    public function record(array $data, string $sumberInput, ?string $dicatatOleh = null): Kunjungan {
        return DB::transaction(function () use ($data, $sumberInput, $dicatatOleh){
            $tamu = Tamu::firstOrCreate(
                [
                    'nama_tamu' => $data['nama_tamu'],
                    'instansi_asal' => $data['instansi_asal'] ?? null,
                ],
                Arr::only($data, ['jenis_kelamin', 'no_hp', 'alamat']),
            );

            if (filled($data['no_hp'] ?? null) && $tamu->no_hp !== $data['no_hp']) {
                $tamu->update(['no_hp' => $data['no_hp']]);
            }

            $kunjungan = $tamu->kunjungan()->make([
                'bidang_id' => $data['bidang_id'],
                'pegawai_id' => $data['pegawai_id'],
                'keperluan' => $data['keperluan'],
                'waktu_datang' => $data['waktu_datang'] ?? now(),
            ]);

            $kunjungan->forceFill([
                'sumber_input' => $sumberInput,
                'dicatat_oleh' => $dicatatOleh,
            ])->save();

            return $kunjungan;
        });
    }
}