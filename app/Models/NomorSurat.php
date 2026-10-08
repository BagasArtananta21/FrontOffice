<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOpd;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kode_klasifikasi', 'bidang_id', 'perihal', 'tujuan_surat', 'nama_peminta', 'tanggal_surat'])]
class NomorSurat extends Model
{
    use BelongsToOpd, HasUuids;

    public const STATUS_TERBIT = 'terbit';
    public const STATUS_BATAL = 'batal';

    protected $table = 'nomor_surat';

    public function format(): BelongsTo
    {
        return $this->belongsTo(FormatNomorSurat::class, 'format_nomor_surat_id');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'nomor_urut' => 'integer',
            'sub_nomor' => 'integer',
            'tanggal_surat' => 'date',
            'tanggal_terbit' => 'datetime',
        ];
    }
}
