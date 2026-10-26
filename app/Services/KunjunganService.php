<?php

namespace App\Services;

use App\Models\Kunjungan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\DisplayDevice;
use App\Exceptions\DisplayUnavailableException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class KunjunganService
{
     private const GUEST_FIELDS = [
        'nama_tamu', 'jenis_kelamin', 'no_hp', 'instansi_asal', 'alamat', 'bidang_id', 'pegawai_tujuan', 'keperluan',
     ];

     public function recordFromDisplay(array $data, ?string $petugasId): Kunjungan
     {
        $attributes = Arr::only($data, self::GUEST_FIELDS);
        $attributes['tanda_tangan'] = $this->storeSignature($data['tanda_tangan']);

        return $this->store($attributes, Kunjungan::SUMBER_DISPLAY, now(), $petugasId);
     }

     public function recordWithoutSignature(array $data, User $petugas, string $reason): Kunjungan
     {
         if (! array_key_exists($reason, Kunjungan::NO_SIGNATURE_REASONS)) {
            throw new InvalidArgumentException("Alasan tanpa tanda tangan [{$reason}] tidak dikenal.");
         }

         return $this->store(
            Arr::only($data, [...self::GUEST_FIELDS, 'catatan_petugas']),
            Kunjungan::SUMBER_FRONT_OFFICE,
            filled($data['waktu_datang'] ?? null) ? Carbon::parse($data['waktu_datang']) : now(),
            $petugas->id,
            ['alasan_tanpa_ttd' => $reason],
         );
     }

     private function storeSignature(string $dataUrl): string
     {
          $binary = base64_decode(Str::after($dataUrl, 'base64,'), true);
          $path = 'tanda-tangan/'.now()->format('Y/m').'/'.Str::uuid().'.png';

          Storage::disk('local')->put($path, $binary);

          return $path;
     }

     private function store(array $attributes, string $sumberInput, CarbonInterface $waktuDatang, ?string $dicatatOleh = null, array $sistemAttributes = []): Kunjungan
     {
        $kunjungan = new Kunjungan($attributes);

        $kunjungan->forceFill([
            'waktu_datang' => $waktuDatang,
            'sumber_input' => $sumberInput,
            'dicatat_oleh' => $dicatatOleh,
            ...$sistemAttributes,
        ])->save();

        return $kunjungan;
     }

     public function requestSignature(array $data, User $petugas): Kunjungan
   {
      return DB::transaction(function () use ($data, $petugas) {
         $device = DisplayDevice::query()
               ->availableForSignature()
               ->lockForUpdate()
               ->first();

         if (! $device) {
               throw new DisplayUnavailableException();
         }

         $kunjungan = $this->store(
               Arr::only($data, [...self::GUEST_FIELDS, 'catatan_petugas']),
               Kunjungan::SUMBER_FRONT_OFFICE,
               filled($data['waktu_datang'] ?? null) ? Carbon::parse($data['waktu_datang']) : now(),
               $petugas->id,
         );

         $device->forceFill(['kunjungan_menunggu_id' => $kunjungan->id])->save();

         return $kunjungan;
      });
   }

   public function recordSignature(DisplayDevice $device, string $signatureDataUrl): ?Kunjungan
   {
      return DB::transaction(function () use ($device, $signatureDataUrl) {
         $device = DisplayDevice::query()->whereKey($device->getKey())->lockForUpdate()->firstOrFail();
         $kunjungan = $device->kunjunganMenunggu;

         if (! $kunjungan || filled($kunjungan->tanda_tangan)) {
               return null;
         }

         $kunjungan->forceFill(['tanda_tangan' => $this->storeSignature($signatureDataUrl)])->save();
         $device->forceFill(['kunjungan_menunggu_id' => null])->save();

         return $kunjungan;
      });
   }

   public function cancelSignatureRequest(?string $reason = null): bool
   {
      if ($reason !== null && ! in_array($reason, [Kunjungan::REASON_GUEST_LEFT, Kunjungan::REASON_GUEST_REFUSED], true)) {
         throw new InvalidArgumentException("Alasan pembatalan [{$reason}] tidak dikenal.");
      }

      return DB::transaction(function () use ($reason) {
         $device = DisplayDevice::query()
            ->whereNotNull('kunjungan_menunggu_id')
            ->lockForUpdate()
            ->first();

         if (! $device) {
               return false;
         }

         $this->releaseSignatureRequest(
               $device,
               $device->isConnected()
                  ? ($reason ?? Kunjungan::REASON_GUEST_LEFT)
                  : Kunjungan::REASON_DISPLAY_UNAVAILABLE,
         );

         return true;
      });

   }

   public function expireSignatureRequest(DisplayDevice $device): bool
   {
      return DB::transaction(function () use ($device) {
         $device = DisplayDevice::query()->whereKey($device->getKey())->lockForUpdate()->firstOrFail();

         if (! $device->signatureRequestExpired()) {
            return false;
         }

         $this->releaseSignatureRequest(
            $device,
            $device->isConnected() ? Kunjungan::REASON_TIMEOUT : Kunjungan::REASON_DISPLAY_UNAVAILABLE,
         );

         return true;
         
      });
   }

   private function releaseSignatureRequest(DisplayDevice $device, string $reason): void
   {
      $device->kunjunganMenunggu?->forceFill(['alasan_tanpa_ttd' => $reason])->save();
      $device->forceFill(['kunjungan_menunggu_id' => null])->save();
   }

}
