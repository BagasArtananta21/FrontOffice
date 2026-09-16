<?php

namespace App\Filament\SuperAdmin\Resources\Opds\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OpdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas OPD')
                    ->description('Kode OPD dipakai pada format nomor surat, jadi sebaiknya tidak diubah setelah dipakai.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('kode_opd')
                            ->label('Kode OPD')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->placeholder('DISKOMINFOSANTI'),

                        TextInput::make('nama_opd')
                            ->label('Nama OPD')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('alamat_opd')
                            ->label('Alamat')
                            ->rows(3)
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('Logo & Status')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('logo-opd')
                            ->maxSize(1024)
                            ->helperText('PNG atau JPG, maksimal 1 MB.'),

                        Toggle::make('aktif')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Kalau dinonaktifkan, seluruh admin FO milik OPD ini tidak bisa login.'),
                    ]),
            ]);
    }
}
