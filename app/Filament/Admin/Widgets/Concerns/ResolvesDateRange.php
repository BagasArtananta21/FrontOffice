<?php

namespace App\Filament\Admin\Widgets\Concerns;

use Illuminate\Support\Carbon;

trait ResolvesDateRange
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function dateRange(?array $filters = null): array
    {
        $filters ??= $this->pageFilters ?? [];

        $start = filled($filters['startDate'] ?? null)
            ? Carbon::parse($filters['startDate'])->startOfDay()
            : now()->startOfMonth();

        $end = filled($filters['endDate'] ?? null)
            ? Carbon::parse($filters['endDate'])->endOfDay()
            : now()->endOfDay();

        return [$start, $end];
    }
}
