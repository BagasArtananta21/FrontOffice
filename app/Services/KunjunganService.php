<?php

namespace App\Services;

use App\Models\Kunjungan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Storage;

class KunjunganService
{
   private const GUEST_FIELDS = [
        'nama_tamu', 'jenis_kelamin', 'no_hp', 'instansi_asal', 'alamat', 'bidang_id', 'pegawai_id', 'keperluan',
   ];

   public function recordFromDisplay(array $data): Kunjungan
   {
        $attributes = Arr::only($data, self::GUEST_FIELDS);
        $attributes['tanda_tangan'] = $this->storeSignature($data['tanda_tangan']);

        return $this->store($attributes, Kunjungan::SUMBER_DISPLAY, now());
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

   private function storeSignature(string $dataUrl): string
   {
          $binary = base64_decode(Str::after($dataUrl, 'base64,'), true);
          $path = 'tanda-tangan/'.now()->format('Y/m').'/'.Str::uuid().'.png';

          Storage::disk('local')->put($path, $binary);

          return $path;
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
