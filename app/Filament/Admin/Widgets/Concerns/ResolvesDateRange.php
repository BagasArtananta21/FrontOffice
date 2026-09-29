<?php

namespace App\Filament\Admin\Widgets\Concerns;

use Illuminate\Support\Carbon;

trait ResolvesDateRange
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function dateRange(): array
    {
        $start = filled($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])->startOfDay()
            : now()->startOfMonth();

        $end = filled($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])->endOfDay()
            : now()->endOfDay();

        return [$start, $end];
    }
}
