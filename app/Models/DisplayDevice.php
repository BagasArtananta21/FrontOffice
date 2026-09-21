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

    public const COOKIE = 'display_token';

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

    public function isConnected(): bool {
        return $this->terakhir_aktif !== null 
        && $this->terakhir_aktif->greaterThanOrEqualTo(now()->subSecond(30));
    }

    protected function casts(): array {
        return [
            'tampilkan_form' => 'boolean',
            'terakhir_aktif' => 'datetime',
            'aktif' => 'boolean'
        ];
    }
}
