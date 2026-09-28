<?php

namespace App\Services;

use App\Models\Kunjungan;

class KunjunganService
{
   public function record(array $data, string $sumberInput, ?string $dicatatOleh = null): Kunjungan {
        $kunjungan = new Kunjungan($data);

        $kunjungan->forceFill([
            'waktu_datang' => $data['waktu_datang'] ?? now(),
            'sumber_input' => $sumberInput,
            'dicatat_oleh' => $dicatatOleh,
        ])->save();

        return $kunjungan;
    }

}