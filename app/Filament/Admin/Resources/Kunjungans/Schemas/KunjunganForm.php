<?php

namespace App\Filament\Admin\Resources\Kunjungans\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class KunjunganForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('opd_id')
                    ->required(),
                Select::make('bidang_id')
                    ->relationship('bidang', 'id'),
                Select::make('pegawai_id')
                    ->relationship('pegawai', 'id'),
                TextInput::make('nama_tamu')
                    ->required(),
                TextInput::make('jenis_kelamin')
                    ->required(),
                TextInput::make('instansi_asal'),
                TextInput::make('no_hp'),
                Textarea::make('alamat')
                    ->columnSpanFull(),
                Textarea::make('keperluan')
                    ->columnSpanFull(),
                DateTimePicker::make('waktu_datang')
                    ->required(),
                DateTimePicker::make('waktu_keluar'),
                TextInput::make('status')
                    ->required()
                    ->default('di_dalam'),
                Toggle::make('sudah_dihubungi')
                    ->required(),
                Textarea::make('catatan_petugas')
                    ->columnSpanFull(),
                TextInput::make('sumber_input')
                    ->required()
                    ->default('display'),
                TextInput::make('dicatat_oleh'),
            ]);
    }
}
