<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Widgets\Concerns\ResolvesDateRange;
use App\Models\Kunjungan;
use Carbon\CarbonPeriod;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GuestSummaryStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters, ResolvesDateRange;
    
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = 4;

    protected function getStats(): array
    {
        [$start, $end] = $this->dateRange();

        $visits = Kunjungan::query()->whereBetween('waktu_datang', [$start, $end]);

        $total = (clone $visits)->count();
        $fromDisplay = (clone $visits)->where('sumber_input', Kunjungan::SUMBER_DISPLAY)->count();

        $days = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        $previousTotal = Kunjungan::query()
            ->whereBetween('waktu_datang', [$start->copy()->subDays($days), $start->copy()->subSecond()])
            ->count();
        
        $workdays = collect(CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()))
            ->filter->isWeekday()
            ->count();
        
        $topBidang = (clone $visits)
            ->with('bidang')
            ->whereNotNull('bidang_id')
            ->select('bidang_id')
            ->selectRaw('count(*) as total')
            ->groupBy('bidang_id')
            ->orderByDesc('total')
            ->first();
        
        return [
            Stat::make('Total Tamu', $total)
                ->description($this->comparision($total, $previousTotal)),
            
            Stat::make('Rata-rata per hari Kerja', $total > 0 ? number_format($total / $workdays, 1, ',', '.') : '0')
                ->description("{$workdays} hari kerja"),

            Stat::make('Mengisi sendiri di Display', $total > 0 ? round($fromDisplay / $total * 100).'%' : '-') 
                ->description("{$fromDisplay} dari {$total} tamu"),
            
            Stat::make('Bidang Tersering', $topBidang?->bidang?->kode_bidang ?? '-')
                ->description($topBidang ? "{$topBidang->total} kunjungan" : 'belum ada data')
        ];
    }

    private function comparision(int $current, int $previous): string
    {
        if ($previous === 0){
            return 'Tidak ada data periode sebelumnya';
        }

        $change = (int) round(($current - $previous) / $previous * 100);

        return ($change >= 0 ? '+' : '').$change.'% dari periode sebelumnya';
    }
}