<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\Concerns\HasDateRangeFilters;
use App\Filament\SuperAdmin\Widgets\AttentionList;
use App\Filament\SuperAdmin\Widgets\OpdRecapTable;
use App\Filament\SuperAdmin\Widgets\SystemSummary;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm, HasDateRangeFilters;

    protected static ?string $title = 'Rekap Sistem';

    protected static string|UnitEnum|null $navigationGroup = 'Pantau Sistem';

    public function getSubheading(): ?string
    {
        return 'Ringkasan seluruh OPD berdasarkan rentang tanggal yang dipilih.';
    }

    public function getHeaderWidgets(): array
    {
        return [
            AttentionList::class,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components($this->dateRangeFilterComponents());
    }

    public function getColumns(): int|array
    {
        return 3;
    }

    public function getWidgets(): array
    {
        return [
            SystemSummary::class,
            OpdRecapTable::class,
        ];
    }
}