<?php

namespace App\Services;

use App\Models\Kunjungan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class KunjunganService
{
   private const GUEST_FIELDS = [
        'nama_tamu', 'jenis_kelamin', 'no_hp', 'instansi_asal', 'alamat', 'bidang_id', 'pegawai_id', 'keperluan',
   ];

   public function recordFromDisplay(array $data): Kunjungan
   {
        return $this->store(
            Arr::only($data, self::GUEST_FIELDS),
            Kunjungan::SUMBER_DISPLAY,
            now(),
        );
   }

   public function recordByStaff(array $data, User $petugas): Kunjungan
   {
        return $this->store(
            Arr::only($data, [...self::GUEST_FIELDS, 'catatan_petugas']),
            Kunjungan::SUMBER_FRONT_OFFICE,
            filled($data['waktu_datang'] ?? null) ? Carbon::parse($data['waktu_datang']) : now(),
            $petugas->id,
        );
   }    

   private function store(array $attributes, string $sumberInput, CarbonInterface $waktuDatang, ?string $dicatatOleh = null): Kunjungan
   {
        $kunjungan = new Kunjungan($attributes);

        $kunjungan->forceFill([
            'waktu_datang' => $waktuDatang,
            'sumber_input' => $sumberInput,
            'dicatat_oleh' => $dicatatOleh,
        ])->save();

        return $kunjungan;
   }
}
