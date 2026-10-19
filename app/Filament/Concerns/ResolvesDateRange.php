<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Carbon;
use Carbon\CarbonInterface;

trait ResolvesDateRange
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function presetRange(string $preset): array
    {
        $start = match ($preset) {
            'today' => now()->startOfDay(),
            'this_week' => now()->startOfWeek(CarbonInterface::MONDAY),
            'this_month' => now()->startOfMonth(),
        };

        return [$start, now()->endOfDay()];
    }

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
