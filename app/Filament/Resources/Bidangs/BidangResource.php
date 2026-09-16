<?php

namespace App\Filament\Resources\Bidangs;

use App\Filament\Resources\Bidangs\Pages\ManageBidangs;
use App\Models\Bidang;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Support\Facades\Auth;

class BidangResource extends Resource
{
    protected static ?string $model = Bidang::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nama_bidang';

    protected static ?string $navigationLabel = 'Master Bidang';

    protected static ?string $modelLabel = 'Bidang';

    protected static ?string $pluralModelLabel = 'Bidang';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'bidang';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_bidang')
                    ->label('Nama Bidang')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true, 
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('opd_id', Auth::user()->opd_id),
                    ),

                TextInput::make('kode_bidang')
                    ->label('Kode Bidang')
                    ->required()
                    ->maxLength(20),
                    
                Toggle::make('aktif')
                    ->label('Aktif')
                    ->default(true)
                    ->inline(false)
                    ->helperText('Bidang nonaktif tidak muncul di pilihan tujuan tamu')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nama_bidang')
            ->columns([
                TextColumn::make('kode_bidang')
                    ->label('Kode Bidang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_bidang')
                    ->label('Nama Bidang')
                    ->searchable()
                    ->sortable(),

                ToggleColumn::make('aktif')
                    ->label('Aktif'),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('aktif')
                    ->label('Aktif')
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBidangs::route('/'),
        ];
    }
}
