<?php

namespace App\Filament\Admin\Resources\Kunjungans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KunjungansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('opd_id')
                    ->searchable(),
                TextColumn::make('bidang.id')
                    ->searchable(),
                TextColumn::make('pegawai.id')
                    ->searchable(),
                TextColumn::make('nama_tamu')
                    ->searchable(),
                TextColumn::make('jenis_kelamin')
                    ->searchable(),
                TextColumn::make('instansi_asal')
                    ->searchable(),
                TextColumn::make('no_hp')
                    ->searchable(),
                TextColumn::make('waktu_datang')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('waktu_keluar')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                IconColumn::make('sudah_dihubungi')
                    ->boolean(),
                TextColumn::make('sumber_input')
                    ->searchable(),
                TextColumn::make('dicatat_oleh')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
