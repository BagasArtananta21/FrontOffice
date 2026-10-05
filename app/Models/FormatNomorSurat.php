<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOpd;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

#[Fillable(['susunan', 'berlaku_sejak', 'dasar_perubahan'])]
class FormatNomorSurat extends Model
{
    use BelongsToOpd, HasUuids;

    protected $table = 'format_nomor_surat';

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function nomorSurat(): HasMany
    {
        return $this->hasMany(NomorSurat::class);
    }

    public function scopeBerlakuPada(Builder $query, CarbonInterface $tanggal): Builder
    {
        return $query->where('berlaku_sejak', '<=', $tanggal->toDateString())
            ->orderByDesc('berlaku_sejak');
    }

    public function statusLabel(): string
    {
        if ($this->berlaku_sejak -> isFuture()) {
            return 'Terjadwal';
        }


        $berlaku = static::query()
            ->withoutGlobalScope('opd')
            ->where('opd_id', $this->opd_id)
            ->berlakuPada(now())
            ->value('id');
        
        return $berlaku === $this->id ? 'Berlaku' : 'Riwayat';
    }

    protected function casts(): array
    {
        return [
            'susunan' => 'array',
            'berlaku_sejak' => 'date',
        ];
    }

}
