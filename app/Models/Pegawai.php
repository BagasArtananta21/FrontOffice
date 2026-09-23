<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOpd;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

#[Fillable(['bidang_id', 'nip', 'nama_pegawai', 'jabatan', 'aktif'])]
class Pegawai extends Model
{
    use BelongsToOpd, HasUuids;
    
    protected $table = 'pegawai';

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }
}
