<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\GuestByBidangChart;
use App\Filament\Admin\Widgets\GuestSummaryStats;
use App\Filament\Admin\Widgets\GuestTrendChart;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use App\Exports\VisitHistoryExport;
use App\Filament\Concerns\HasDateRangeFilters;
use App\Filament\Support\SweetAlert;
use App\Models\Kunjungan;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm, HasDateRangeFilters;

    protected static ?string $title = 'Rekap Kunjungan';

    protected static string|UnitEnum|null $navigationGroup = 'Buku Tamu';

    public function getSubheading(): ?string
    {
        return 'Ringkasan kedatangan tamu berdasarkan rentang tanggal yang dipilih.';
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components( $this -> dateRangeFilterComponents());
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
                ->color('success')
                ->outlined()
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
