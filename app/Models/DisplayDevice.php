<?php

namespace App\Models;


use App\Models\Concerns\BelongsToOpd;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

#[Fillable(['nama', 'tampilkan_form', 'aktif'])]
#[Hidden(['token_hash'])]
class DisplayDevice extends Model
{
    use BelongsToOpd, HasUuids;
    protected $table = 'display_devices';

    public const CONNECTED_WITHIN_SECONDS = 30;

    public const SIGNATURE_TIMEOUT_MINUTES = 5;

    public const COOKIE = 'display_token';

    public function scopeConnected(Builder $query): Builder {
        return $query->where('terakhir_aktif', '>=', now()->subSeconds(self::CONNECTED_WITHIN_SECONDS));
    }

    public function scopeAvailableForSignature(Builder $query): Builder
    {
        return $query->where('aktif', true)
            ->connected()
            ->where('tampilkan_form', false)
            ->whereNull('kunjungan_menunggu_id');
    }

    public function isConnected(): bool
    {
        return $this->terakhir_aktif !== null
            && $this->terakhir_aktif->greaterThanOrEqualTo(now()->subSeconds(self::CONNECTED_WITHIN_SECONDS));
    }

    public static function findActiveByToken(?string $token): ?self {
        if (blank($token)){
            return null;
        }

        return static::withoutGlobalScope('opd')
            ->where('token_hash', hash('sha256', $token))
            ->where('aktif', true)
            ->whereHas('opd', fn (Builder $query) => $query->active())
            ->first();
    }

    public function opd(): BelongsTo{
        return $this->belongsTo(Opd::class);
    } 

    public function kunjunganMenunggu(): BelongsTo{
        return $this->belongsTo(Kunjungan::class, 'kunjungan_menunggu_id');
    }

    public function waitingKey(): ?string
    {
        $kunjungan = $this->kunjunganMenunggu;

        return $kunjungan ? $kunjungan->id.'@'.$kunjungan->updated_at->timestamp : null;    
    }

    public function signatureRequestExpired(): bool
    {
        $kunjungan = $this->kunjunganMenunggu;

        return $kunjungan !== null
            && $kunjungan->updated_at->lt(now()->subMinutes(self::SIGNATURE_TIMEOUT_MINUTES));
    }

    protected function casts(): array {
        return [
            'tampilkan_form' => 'boolean',
            'terakhir_aktif' => 'datetime',
            'aktif' => 'boolean'
        ];
    }
}
