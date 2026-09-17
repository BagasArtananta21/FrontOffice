<?php

namespace App\Filament\Resources\Bidangs;

use App\Filament\Resources\Bidangs\Pages\ManageBidangs;
use App\Filament\Support\SweetAlert;
use App\Models\Bidang;
use BackedEnum;
use Filament\Actions\EditAction;
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
use Illuminate\Database\QueryException;

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

                IconColumn::make('aktif')
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
                EditAction::make()
                ->successNotification(null)
                ->using(function (Bidang $record, array $data, $livewire, EditAction $action) {
                    try {
                        $record->update($data);
                        return $record;
                    } catch(QueryException $e) {
                        report($e);
                        SweetAlert::error(
                            $livewire,
                            'Gagal memperbarui Bidang',
                            'Terjadi kesalahan saat memperbarui Bidang. Silakan coba lagi'
                        );
                        $action->halt();
                    }
                })
                ->after(function ($livewire, Bidang $record) {
                    if (! $record->wasChanged()) {
                        SweetAlert::info(
                            $livewire, 
                            'Tidak ada perubahan', 
                            'Data bidang tetap seperti sebelumnya.');
                        return;
                    }
                    if ($record->wasChanged('aktif') && ! $record->aktif) {
                        SweetAlert::warning(
                            $livewire, 
                            'Bidang dinonaktifkan', 
                            "{$record->nama_bidang} tidak lagi muncul di pilihan tujuan tamu.");
                        return;
                    }

                    SweetAlert::success($livewire, 'Perubahan tersimpan', "Data {$record->nama_bidang} sudah diperbarui.");
                }),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBidangs::route('/'),
        ];
    }
}
