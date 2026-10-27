<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Kunjungan;
use Filament\Widgets\Widget;

class GuestStats extends Widget
{
    protected int|string|array $columnSpan = 2;

    protected string $view = 'filament.admin.widgets.guest-stats';

    protected function getViewData(): array
    {
        return [
            'today' => Kunjungan::query()
                ->whereBetween('waktu_datang', [now()->startOfDay(), now()->endOfDay()])
                ->count(),
            'month' => Kunjungan::query()
                ->whereBetween('waktu_datang', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'todayLabel' => now()->translatedFormat('l, d F Y'),
            'monthLabel' => now()->translatedFormat('F Y'),
        ];
    }
}
