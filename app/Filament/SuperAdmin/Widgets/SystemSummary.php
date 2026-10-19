<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Filament\Concerns\ResolvesDateRange;
use App\Models\DisplayDevice;
use App\Models\Kunjungan;
use App\Models\NomorSurat;
use App\Models\Opd;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class SystemSummary extends Widget
{
    use InteractsWithPageFilters, ResolvesDateRange;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.super-admin.widgets.system-summary';

    protected function getViewData(): array
    {
        [$start, $end] = $this->dateRange();

        $letters = NomorSurat::query()->whereBetween('tanggal_terbit', [$start, $end]);

        $displays = DisplayDevice::query()
            ->where('aktif', true)
            ->whereHas('opd', fn (Builder $query) => $query->active());

        $displaysTotal = (clone $displays)->count();
        $displaysConnected = (clone $displays)->connected()->count();

        return [
            'cards' => [
                [
                    'label' => 'OPD Aktif',
                    'value' => Opd::active()->count(),
                    'unit' => 'dari '.Opd::count().' OPD',
                    'note' => null,
                    'icon' => 'heroicon-o-building-office-2',
                    'border' => 'border-l-primary',
                    'badge' => 'bg-primary/10 text-primary',
                ],
                [
                    'label' => 'Kunjungan',
                    'value' => number_format(Kunjungan::query()->whereBetween('waktu_datang', [$start, $end])->count(), 0, ',', '.'),
                    'unit' => 'Tamu',
                    'note' => 'Seluruh OPD pada rentang terpilih',
                    'icon' => 'heroicon-o-user-group',
                    'border' => 'border-l-tertiary',
                    'badge' => 'bg-tertiary/10 text-tertiary',
                ],
                [
                    'label' => 'Nomor Surat Terbit',
                    'value' => number_format((clone $letters)->where('status', NomorSurat::STATUS_TERBIT)->count(), 0, ',', '.'),
                    'unit' => 'Nomor',
                    'note' => (clone $letters)->where('status', NomorSurat::STATUS_BATAL)->count().' dibatalkan',
                    'icon' => 'heroicon-o-document-text',
                    'border' => 'border-l-secondary',
                    'badge' => 'bg-secondary/10 text-secondary',
                ],
                [
                    'label' => 'Display Terhubung',
                    'value' => "{$displaysConnected} / {$displaysTotal}",
                    'unit' => null,
                    'note' => 'Kondisi saat ini',
                    'progress' => $displaysTotal > 0 ? (int) round($displaysConnected / $displaysTotal * 100) : 0,
                    'icon' => 'heroicon-o-computer-desktop',
                    'border' => 'border-l-success-600',
                    'badge' => 'bg-success-600/10 text-success-600',
                ],
            ],
        ];
    }
}
