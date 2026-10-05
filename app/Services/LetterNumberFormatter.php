<?php

namespace App\Services;

use Carbon\CarbonInterface;

class LetterNumberFormatter
{
    public const SEPARATOR = '/';

    public const SEGMENTS = [
        'klasifikasi' => 'Kode klasifikasi',
        'urut' => 'Nomor urut',
        'bidang' => 'Kode bidang',
        'kode_opd' => 'Kode OPD',
        'bulan_romawi' => 'Bulan (romawi)',
        'tahun' => 'Tahun',
        'teks' => 'Teks tetap',
    ];

    private const ROMAN_MONTHS = [
        1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII',
    ];

    /**
     * @param array<int|string, array{jenis?: ?string, teks?: ?string}> $susunan
     * @param array{klasifikasi: string, nomor_urut: int, sub_nomor: int, bidang: ?string, kode_opd: string, tanggal: CarbonInterface} $nilai
     */
    public function format(array $susunan, array $nilai): string
    {
        return collect($susunan)
            ->map(fn (array $bagian) => match ($bagian['jenis'] ?? null) {
                'klasifikasi' => $nilai['klasifikasi'],
                'urut' => $this->formatUrut($nilai['nomor_urut'], $nilai['sub_nomor']),
                'bidang' => $nilai['bidang'] ?? '',
                'kode_opd' => $nilai['kode_opd'],
                'bulan_romawi' => self::ROMAN_MONTHS[$nilai['tanggal']->month],
                'tahun' => (string) $nilai['tanggal']->year,
                'teks' => $bagian['teks'] ?? '',
                default => '',
            })
            ->filter(fn (string $teks) => $teks !== '')
            ->implode(self::SEPARATOR);
    }

    public function preview(array $susunan, ?string $kodeOpd = null): string
    {
        return $this->format($susunan, [
            'klasifikasi' => '001',
            'nomor_urut' => 1,
            'sub_nomor' => 0,
            'bidang' => 'KODEBIDANG',
            'kode_opd' => $kodeOpd ?? 'KODEOPD',
            'tanggal' => now(),
        ]);
    }

    private function formatUrut(int $nomorUrut, int $subNomor): string
    {
        return $subNomor > 0 ? "{$nomorUrut}.{$subNomor}" : (string) $nomorUrut;
    }
}
