<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Widgets\Concerns\ResolvesDateRange;
use App\Models\Kunjungan;
use Carbon\CarbonPeriod;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class GuestSummaryStats extends Widget
{
    use InteractsWithPageFilters, ResolvesDateRange;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.guest-summary-stats';

    protected function getViewData(): array
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
            'total' => $total,
            'change' => $previousTotal > 0 ? (int) round(($total - $previousTotal) / $previousTotal * 100) : null,
            'average' => $workdays > 0 ? number_format($total / $workdays, 1, ',', '.') : '0',
            'workdays' => $workdays,
            'fromDisplay' => $fromDisplay,
            'displayShare' => $total > 0 ? (int) round($fromDisplay / $total * 100) : null,
            'topBidang' => $topBidang?->bidang,
            'topBidangTotal' => (int) $topBidang?->total,
        ];
    }
}
