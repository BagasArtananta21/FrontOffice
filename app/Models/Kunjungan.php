<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOpd;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nama_tamu', 'jenis_kelamin', 'instansi_asal', 'no_hp', 'alamat', 'bidang_id', 'pegawai_tujuan', 'keperluan', 'tanda_tangan', 'catatan_petugas', 'waktu_datang'])]
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

    public const NO_HP_PATTERN = '/^0[0-9]{8,13}$/';
    
    public static function normalizePhone(?string $value): ?string
    {
        $cleaned = preg_replace('/[\s\-.()]/', '', (string) $value);

        return $cleaned === '' ? null : preg_replace('/^(\+62|62)/', '0', $cleaned);
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
        ];
    }

}
