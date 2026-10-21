<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\NomorSurats\NomorSuratResource;
use App\Models\NomorCounter;
use App\Models\NomorSurat;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;

class LetterNumberStats extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.letter-number-stats';

    #[Reactive]
    public ?array $tableFilters = null;

    #[On('nomor-surat-updated')]
    public function refreshStats(): void
    {
        //
    }

    protected function getViewData(): array
    {
        $year = now()->year;

        $lastNumber = (int) NomorCounter::query()
            ->where('jenis', 'surat')
            ->where('periode', (string) $year)
            ->value('nomor_terakhir');
        
        $lastLetterNumber = NomorSurat::query()
            ->where('tahun', $year)
            ->where('sub_nomor', 0)
            ->orderByDesc('nomor_urut')
            ->first(['nomor_urut', 'nomor_lengkap', 'status']);
        
        $lastNote = match (true) {
            $lastLetterNumber && $lastLetterNumber->nomor_urut === $lastNumber => $lastLetterNumber->status === NomorSurat::STATUS_BATAL
                ? "{$lastLetterNumber->nomor_lengkap} (dibatalkan)"
                : $lastLetterNumber->nomor_lengkap,
            $lastNumber > 0 => 'Dilanjutkan dari buku agenda',
            default => "Belum ada nomor tahun ini",
        };

        $basis = ($this->tableFilters['rentang_tanggal']['basis'] ?? 'surat') === 'terbit' ? 'terbit' : 'surat';
        $column = NomorSuratResource::dateFilterColumn($basis); 

        $thisMonth = NomorSurat::query()
            ->whereBetween($column, [now()->startOfMonth(), now()->endOfMonth()]);

        return [
            'year' => $year,
            'lastNumber' => $lastNumber,
            'lastNote' => $lastNote,
            'monthLabel' => now()->translatedFormat('F Y'),
            'monthTitle' => $basis === 'terbit' ? 'Diterbitkan Bulan Ini' : 'Bertanggal Bulan Ini',
            'basisNote' => $basis === 'terbit' ? 'berdasarkan tanggal terbit' : 'berdasarkan tanggal surat',
            'issuedThisMonth' => (clone $thisMonth)->where('status', NomorSurat::STATUS_TERBIT)->count(),
            'cancelledThisMonth' => (clone $thisMonth)->where('status', NomorSurat::STATUS_BATAL)->count(),
        ];
    }
}