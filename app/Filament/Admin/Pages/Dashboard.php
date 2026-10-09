<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\Concerns\ResolvesDateRange;
use App\Filament\Admin\Widgets\GuestByBidangChart;
use App\Filament\Admin\Widgets\GuestSummaryStats;
use App\Filament\Admin\Widgets\GuestTrendChart;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use App\Exports\VisitHistoryExport;
use App\Filament\Support\SweetAlert;
use App\Models\Kunjungan;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm, ResolvesDateRange;

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function ($livewire) {
                    [$start, $end] = $this->dateRange($this->filters ?? []);

                    $query = Kunjungan::query()
                        ->whereBetween('waktu_datang', [$start, $end])
                        ->orderBy('waktu_datang');

                    if (! $query->clone()->exists()) {
                        SweetAlert::warning(
                            $livewire,
                            'Tidak ada data untuk diekspor',
                            'Tidak ada kunjungan yang sesuai dengan filter saat ini.'
                        );

                        return;
                    }

                    return app(VisitHistoryExport::class)->download($query);
                }),
        ];
    }
}
