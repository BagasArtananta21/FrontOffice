<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Filament\Concerns\ResolvesDateRange;
use App\Models\NomorSurat;
use App\Models\Opd;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OpdRecapTable extends TableWidget
{
    use InteractsWithPageFilters, ResolvesDateRange;
    
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Ringkasan OPD')
            ->query(function () {
                [$start, $end] = $this->dateRange();

                return Opd::query()->withCount([
                    'kunjungan' => fn (Builder $query) => $query->whereBetween('waktu_datang', [$start, $end]),
                    'nomorSurat as nomor_terbit_count' => fn (Builder $query) =>$query
                        ->where('status', NomorSurat::STATUS_TERBIT)
                        ->whereBetween('tanggal_terbit', [$start, $end]),
                    'nomorSurat as nomor_batal_count' => fn (Builder $query) => $query
                        ->where('status', NomorSurat::STATUS_BATAL)
                        ->whereBetween('tanggal_terbit', [$start, $end]),
                    'displayDevices as display_total_count' => fn (Builder $query) => $query->where('aktif', true),
                    'displayDevices as display_connected_count' => fn (Builder $query) => $query->where('aktif', true)->connected(),
                    'users as admin_count' => fn (Builder $query) => $query->where('role', 'admin_fo')->where('aktif', true),
                ]);
            })
            ->defaultSort('kunjungan_count', 'desc')
            ->columns([
                TextColumn::make('nama_opd')
                    ->label('OPD')
                    ->description(fn (Opd $record) => $record->aktif ? $record->kode_opd : "{$record->kode_opd} · Nonaktif")
                    ->searchable(),

                TextColumn::make('kunjungan_count')
                    ->label('Kunjungan')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('nomor_terbit_count')
                    ->label('Nomor Terbit')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('nomor_batal_count')
                    ->label('Nomor Batal')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state) => $state > 0 ? 'danger' : null),

                TextColumn::make('display')
                    ->label('Display')
                    ->badge()
                    ->state(fn (Opd $record) => match (true) {
                        $record->display_total_count === 0 => 'Belum ada',
                        $record->display_connected_count > 0 => "Terhubung {$record->display_connected_count}/{$record->display_total_count}",
                        default => 'Terputus',
                    })
                    ->color(fn (Opd $record) => $record->display_connected_count > 0 ? 'success' : 'gray'),

                TextColumn::make('admin_count')
                    ->label('Admin Aktif')
                    ->numeric()
                    ->color(fn (int $state) => $state === 0 ? 'danger' : null),
            ])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Belum ada OPD');
    }
}