<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOpd;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

#[Fillable(['kode_bidang', 'nama_bidang', 'aktif'])]
class Bidang extends Model
{
    use BelongsToOpd, HasUuids;

    protected $table = 'bidang';

    public function opd() : BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function shortLabel(): string
    {
        return filled($this->kode_bidang) 
            ? $this->kode_bidang
            : Str::limit($this->nama_bidang, 20);
    }

}
