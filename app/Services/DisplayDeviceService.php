<?php

namespace App\Services;

use App\Models\Opd;
use App\Models\DisplayDevice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DisplayDeviceService
{
    public const LENGTH = 6;

    private const MAX_ATTEMPTS = 10;

    public const DEFAULT_NAME = 'Display Lobby';

    public function assignNewPin(DisplayDevice $device): string
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $pin = $this->randomPin();
            $hmac = self::hmac($pin);

            $taken = DisplayDevice::withoutGlobalScope('opd')
                ->where('pin_hmac', $hmac)
                ->exists();

            if (! $taken) {
                $device->forceFill(['pin' => $pin, 'pin_hmac' => $hmac])->save();

                return $pin;
            }
        }

        throw new RuntimeException('Gagal membuat PIN unik setelah '.self::MAX_ATTEMPTS.' percobaan.');
    }

    public function findActiveByPin(?string $pin): ?DisplayDevice
    {
        if (blank($pin)) {
            return null;
        }

        return DisplayDevice::withoutGlobalScope('opd')
            ->where('pin_hmac', self::hmac($pin))
            ->where('aktif', true)
            ->whereHas('opd', fn (Builder $query) => $query->active())
            ->first();
    }

    public static function hmac(string $pin): string
    {
        return hash_hmac('sha256', $pin, config('app.key'));
    }

    private function randomPin(): string
    {
        return str_pad((string) random_int(0, 10 ** self::LENGTH - 1), self::LENGTH, '0', STR_PAD_LEFT);
    }

    public function provisionFor(Opd $opd): DisplayDevice
    {
        return DB::transaction(function () use ($opd) {
        $device = new DisplayDevice();
            $device->forceFill([
                'opd_id' => $opd->id,
                'nama' => self::DEFAULT_NAME,
                'aktif' => true,
            ])->save();

            $this->assignNewPin($device);

            return $device;
        });
    }

    public function pair(?string $pin): ?string
    {
        $device = $this->findActiveByPin($pin);

        if ($device === null) {
            return null;
        }

        $token = Str::random(40);

        $claimed = DisplayDevice::withoutGlobalScope('opd')
            ->whereKey($device->id)
            ->whereNull('token_hash')
            ->update(['token_hash' => hash('sha256', $token)]);
        
        return $claimed === 1 ? $token : null;
    }

    public function unpair(DisplayDevice $device): void
    {
        DB::transaction(function () use ($device) {
            $locked = DisplayDevice::query()->whereKey($device->id)->lockForUpdate()->firstOrFail();
    
            if ($locked->kunjungan_menunggu_id !== null) {
                throw new InvalidArgumentException('Batalakan permintaan tanda tangan dulu sebelum memutuskan display.');
            }
    
            $locked->forceFill([
                'token_hash' => null,
                'terakhir_aktif' => null,
            ])->save();
        });
    }

    public function requestReload(DisplayDevice $device): void
    {
        DisplayDevice::query()
            ->whereKey($device->id)
            ->toBase()
            ->update(['muat_ulang_diminta_pada' => now()]);
    }
}
