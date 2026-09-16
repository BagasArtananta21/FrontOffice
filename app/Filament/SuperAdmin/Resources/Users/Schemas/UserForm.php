<?php

namespace App\Filament\SuperAdmin\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('nip')
                    ->label('NIP')
                    ->maxLength(30)
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText('Kosongkan bila tidak ingin mengubah kata sandi.'),

                Select::make('role')
                    ->label('Peran')
                    ->options([
                        'admin_fo' => 'Admin FO',
                        'super_admin' => 'Super Admin',
                    ])
                    ->default('admin_fo')
                    ->required()
                    ->live(),

                Select::make('opd_id')
                    ->label('OPD')
                    ->relationship('opd', 'nama_opd', fn (Builder $query) => $query->active())
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get) => $get('role') === 'admin_fo')
                    ->required(fn (Get $get) => $get('role') === 'admin_fo'),

                Toggle::make('aktif')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
