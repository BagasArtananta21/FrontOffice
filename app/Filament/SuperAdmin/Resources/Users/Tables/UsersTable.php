<?php

namespace App\Filament\SuperAdmin\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\ToggleColumn;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('nip')
                    ->label('NIP')
                    ->placeholder('—'),

                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'super_admin' ? 'Super Admin' : 'Admin FO')
                    ->color(fn (string $state) => $state === 'super_admin' ? 'secondary' : 'info'),

                TextColumn::make('opd.nama_opd')
                    ->label('OPD')
                    ->placeholder('—'),

                ToggleColumn::make('aktif')
                    ->label('Aktif'),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Peran')
                    ->options([
                        'admin_fo' => 'Admin FO',
                        'super_admin' => 'Super Admin',
                    ]),

                TernaryFilter::make('aktif')->label('Status aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
