<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOpd;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('bidang_id', 'keperluan', 'jumlah_orang', 'waktu_datang')]
class Kunjungan extends Model
{
    use BelongsToOpd, HasUuids;
    
    protected $table = 'kunjungan';

    public const SUMBER_DISPLAY = 'display';
    public const SUMBER_MANUAL = 'manual';

    public function tamu(): BelongsTo
    {
        return $this->belongsTo(Tamu::class);
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    protected function casts(): array
    {
        return [
            'waktu_datang' => 'datetime',
            'waktu_keluar' => 'datetime',
            'jumlah_orang' => 'integer',
            'sudah_dihubungi' => 'boolean',
        ];
    }

}
