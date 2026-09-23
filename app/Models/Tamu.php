<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOpd;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama_tamu', 'jenis_kelamin', 'no_hp', 'instansi_asal', 'alamat'])]
class Tamu extends Model
{
    use BelongsToOpd, HasUuids;
    
    protected $table = 'tamu';

    public const JENIS_KELAMIN = [
        'laki_laki' => 'Laki-laki',
        'perempuan' => 'Perempuan',
    ];


    public function kunjungan(): HasMany 
    {
        return $this->hasMany(Kunjungan::class);
    }
}
