<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Size;

trait HasDateRangeFilters
{
    use ResolvesDateRange;

    protected function dateRangeFilterComponents(): array
    {
        return [
            Section::make()
                ->columnSpanFull()
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    DatePicker::make('startDate')
                        ->label('Dari Tanggal')
                        ->default(now()->startOfMonth())
                        ->maxDate(now()),

                    DatePicker::make('endDate')
                        ->label('Sampai Tanggal')
                        ->default(now())
                        ->minDate(fn (Get $get) => $get('startDate'))
                        ->maxDate(now()),

                    Actions::make([
                        $this->datePresetAction('today', 'Hari Ini'),
                        $this->datePresetAction('this_week', 'Minggu Ini'),
                        $this->datePresetAction('this_month', 'Bulan Ini'),
                    ])->verticallyAlignEnd(),
                ]),
        ];
    }

    private function datePresetAction(string $preset, string $label): Action
    {
        return Action::make("preset_{$preset}")
            ->label($label)
            ->size(Size::Small)
            ->color(fn () => $this->isDatePresetActive($preset) ? 'primary' : 'gray')
            ->action(fn () => $this->applyDatePreset($preset));
    }

    private function applyDatePreset(string $preset): void
    {
        [$start, $end] = static::presetRange($preset);

        $this->getFiltersForm()->fill([
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
        ]);

        $this->updatedFilters();
    }

    private function isDatePresetActive(string $preset): bool
    {
        [$start, $end] = $this->dateRange($this->filters ?? []);
        [$presetStart, $presetEnd] = static::presetRange($preset);

        return $start->isSameDay($presetStart) && $end->isSameDay($presetEnd);
    }
}
