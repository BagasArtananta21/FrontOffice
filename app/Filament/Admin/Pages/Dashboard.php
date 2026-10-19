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
use UnitEnum;
use Filament\Schemas\Components\Actions;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm, ResolvesDateRange;

    protected static ?string $title = 'Rekap Kunjungan';

    protected static string|UnitEnum|null $navigationGroup = 'Buku Tamu';

    public function getSubheading(): ?string
    {
        return 'Ringkasan kedatangan tamu berdasarkan rentang tanggal yang dipilih.';
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
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
        ]);
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
