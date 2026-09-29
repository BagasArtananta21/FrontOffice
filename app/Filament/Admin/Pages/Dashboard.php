<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\GuestByBidangChart;
use App\Filament\Admin\Widgets\GuestSummaryStats;
use App\Filament\Admin\Widgets\GuestTrendChart;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Rekap Kunjungan';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    DatePicker::make('startDate')
                        ->label('Dari tanggal')
                        ->default(now()->startOfMonth())
                        ->maxDate(now()),

                    DatePicker::make('endDate')
                        ->label('Sampai tanggal')
                        ->default(now())
                        ->minDate(fn (Get $get) => $get('startDate'))
                        ->maxDate(now()),
                ]),
        ]);
    }

    public function getColumns(): int|array
    {
        return 3;
    }

    public function getWidgets(): array
    {
        return [
            GuestSummaryStats::class,
            GuestTrendChart::class,
            GuestByBidangChart::class,
        ];
    }
}
