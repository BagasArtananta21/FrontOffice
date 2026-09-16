<?php

namespace App\Filament\SuperAdmin\Resources\Opds\Tables;

use App\Models\Opd;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\ToggleColumn;

class OpdsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nama_opd')
            ->columns([
                ImageColumn::make('logo')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->imageHeight(32)
                    ->defaultImageUrl(asset('images/logo-placeholder.png')),

                TextColumn::make('kode_opd')
                    ->label('Kode')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_opd')
                    ->label('Nama OPD')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (Opd $record) => $record->alamat_opd),

                TextColumn::make('users_count')
                    ->label('Jumlah User')
                    ->counts('users')
                    ->alignCenter(),

                ToggleColumn::make('aktif')
                    ->label('Aktif'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('aktif')->label('Status aktif'),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (Opd $record, Array $data): Opd {
                        $record->forceFill($data)->save();

                        return $record;
                    })
            ]);
    }
}
