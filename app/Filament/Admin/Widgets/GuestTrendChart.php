<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Widgets\Concerns\ResolvesDateRange;
use App\Models\Kunjungan;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class GuestTrendChart extends ChartWidget
{
    use InteractsWithPageFilters, ResolvesDateRange;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    protected ?string $heading = 'Tren Kunjungan Harian';

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array 
    {
        [$start, $end] = $this->dateRange();

        $perDay = Kunjungan::query()
            ->whereBetween('waktu_datang', [$start, $end])
            ->pluck('waktu_datang')
            ->countBy(fn (CarbonInterface $time)=>$time->toDateString());
        
        $labels = [];
        $values = [];

        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $day){
            $labels[] = $day->translatedFormat('d M');
            $values[] = $perDay->get($day->toDateString(), 0);
        }

        return [
            'datasets' => [[
                'label' => 'Tamu',
                'data' => $values,
                'borderColor' => '#1E3A8A',
                'backgroundColor' => 'rgba(30, 58, 138, 0.08)',
                'borderWidth' => 2,
                'fill' => true,
                'tension' => 0,
                'pointRadius' => 0,
                'pointHoverRadius' => 4,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['grid' => ['display' => false]],
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}