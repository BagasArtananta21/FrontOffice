<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Testing\Fluent\Concerns\Has;

#[Fillable(['kode_opd', 'nama_opd', 'alamat_opd', 'logo'])]
class Opd extends Model
{
    use HasUuids;

    protected $table = 'opd';

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function bidang(): HasMany
    {
        return $this->hasMany(Bidang::class);
    }

    public function displayDevices(): HasMany
    {
        return $this->hasMany(DisplayDevice::class);
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
}
