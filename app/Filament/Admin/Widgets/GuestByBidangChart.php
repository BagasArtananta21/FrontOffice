<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Widgets\Concerns\ResolvesDateRange;
use App\Models\Kunjungan;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\ChartWidget;

class GuestByBidangChart extends ChartWidget
{
    use InteractsWithPageFilters, ResolvesDateRange;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Kunjungan Per Bidang';

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData():array
    {
        [$start, $end] = $this->dateRange();

        $rows = Kunjungan::query()
            ->with('bidang')
            ->whereBetween('waktu_datang', [$start, $end])
            ->select('bidang_id')
            ->selectRaw('count(*) as total' )
            ->groupBy('bidang_id')
            ->orderByDesc('total')
            ->get();

        return [
            'datasets' => [[
                'label' => 'Kunjungan',
                'data' => $rows->pluck('total')->all(),
                'backgroundColor' => '#1E3A8A',
                'borderRadius' => 4,
                'maxBarThickness' => 24,
            ]],
            'labels' => $rows->map(fn (Kunjungan $row) => $row->bidang?->shortLabel() ?? 'Tanpa Bidang')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                'y' => ['grid' => ['display' => false]],
            ],
        ];
    }
}