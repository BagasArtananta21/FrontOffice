<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Kunjungan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GuestStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '10s';

    protected int|string|array $columnSpan = 2;

    protected int|array|null $columns = 2;

    protected function getStats(): array
    {
        $hariIni = Kunjungan::query()
            ->whereBetween('waktu_datang', [now()->startOfDay(), now()->endOfDay()])
            ->count();

        $bulanIni = Kunjungan::query()
            ->whereBetween('waktu_datang', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        return [
            Stat::make('Tamu Hari Ini', $hariIni)
                ->description(now()->translatedFormat('l, d F Y'))
                ->color('primary'),

            Stat::make('Tamu Bulan Ini', $bulanIni)
                ->description(now()->translatedFormat('F Y'))
                ->color('info'),
        ];
    }
}
