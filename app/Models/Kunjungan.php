<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOpd;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nama_tamu', 'jenis_kelamin', 'instansi_asal', 'no_hp', 'alamat', 'bidang_id', 'pegawai_id', 'keperluan', 'tanda_tangan', 'catatan_petugas', 'waktu_datang', 'sudah_dihubungi'])]
class Kunjungan extends Model
{
    use BelongsToOpd, HasUuids;
    
    protected $table = 'kunjungan';

    public const SUMBER_DISPLAY = 'display';
    public const SUMBER_FRONT_OFFICE = 'frontoffice';

    public const JENIS_KELAMIN = [
        'laki_laki' => 'Laki-laki',
        'perempuan' => 'Perempuan',
    ];

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function petugas(): BelongsTo
    {
    return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    protected function casts(): array
    {
        return [
            'waktu_datang' => 'datetime',
            'sudah_dihubungi' => 'boolean',
        ];
    }

}
