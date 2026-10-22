<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOpd;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public const REASON_DISPLAY_UNAVAILABLE = 'display_tidak_tersedia';
    public const REASON_GUEST_LEFT = 'tamu_sudah_pergi';
    public const REASON_GUEST_REFUSED = 'tamu_menolak';

    public const NO_SIGNATURE_REASONS = [
        self::REASON_DISPLAY_UNAVAILABLE => 'Display tidak tersedia',
        self::REASON_GUEST_LEFT => 'Tamu sudah pergi',
        self::REASON_GUEST_REFUSED => 'Tamu menolak',
    ];
    
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

    public function displayMenunggu(): HasOne
    {
        return $this->hasOne(DisplayDevice::class, 'kunjungan_menunggu_id');
    }

    public function isWaitingForSignature(): bool
    {
        if (filled($this->tanda_tangan)) {
            return false;
        }

        return $this->display_menunggu_exists ?? $this->displayMenunggu()->exists();
    }

    public function signatureStatusLabel(): string
    {
        return match (true) {
            filled($this->tanda_tangan) => 'Sudah',
            $this->isWaitingForSignature() => 'Menunggu',
            default => self::NO_SIGNATURE_REASONS[$this->alasan_tanpa_ttd] ?? 'Tidak Ada',
        };
    }

    protected function casts(): array
    {
        return [
            'waktu_datang' => 'datetime',
        ];
    }

}
