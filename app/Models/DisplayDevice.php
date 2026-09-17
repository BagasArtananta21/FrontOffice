<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOpd;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama', 'ip_address', 'tampilkan_form', 'aktif'])]
#[Hidden(['token_hash'])]
class DisplayDevice extends Model
{
    use BelongsToOpd, HasUuids;
    protected $table = 'display_devices';

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
