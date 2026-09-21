<?php

namespace App\Filament\SuperAdmin\Resources\DisplayDevices;

use App\Filament\SuperAdmin\Resources\DisplayDevices\Pages\ManageDisplayDevices;
use App\Filament\Support\SweetAlert;
use App\Models\DisplayDevice;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Livewire\Livewire;
use Filament\Actions\Action;
use Illuminate\Support\Str;

class DisplayDeviceResource extends Resource
{
    protected static ?string $model = DisplayDevice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?string $title = 'Kelola Perangkat Display';

    protected static ?string $navigationLabel = 'Kelola Display';

    protected static ?string $modelLabel = 'Display';

    protected static ?string $pluralModelLabel = 'Display';
    
    protected static ?string $slug = 'display';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Nama Display')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('PC Lobi'),
                
                Select::make('opd_id')
                    ->label('OPD')
                    ->relationship('opd', 'nama_opd', fn (Builder $query)=> $query->active())
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Semua data tamu dari perangkat ini akan masuk ke OPD yang dipilih'),

                Toggle::make('aktif')
                    ->label('Aktif')
                    ->default(true)
                    ->inline(false)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ManageDevice')
            ->columns([
                TextColumn::make('nama')
                    ->label('Perangkat')
                    ->searchable(),

                TextColumn::make('opd.nama_opd')
                    ->label('OPD')
                    ->searchable(),

                TextColumn::make('terakhir_aktif')
                    ->label('Koneksi')
                    ->badge()
                    ->formatStateUsing(fn (DisplayDevice $record) => $record->isConnected() ? 'Terhubung' : 'Tidak terhubung')
                    ->color(fn (DisplayDevice $record) => $record->isConnected() ? 'success' : 'gray'),
                    
                TextColumn::make('tampilkan_form')
                    ->label('Tampilan')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Form Tamu' : 'Idle')
                    ->color(fn (bool $state) => $state ? 'info' : 'gray'),

                IconColumn::make('aktif')
                    ->label('Status')
                    ->boolean(),
                
                TextColumn::make('terakhir_aktif')
                    ->label('Terakhir Aktif')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true)

            ])
            ->filters([
                TernaryFilter::make('aktif')
                    ->label('Status')
            ])
            ->recordActions([
                EditAction::make()->successNotification(null)
                    ->using(function (DisplayDevice $record, array $data, $livewire, EditAction $action){
                        try {
                            $record->forceFill($data)->save();
                            return $record;
                        } catch (QueryException $e) {
                            report($e);
                            SweetAlert::error(
                                $livewire,
                                'Gagal Menyimpan Perubahan', 
                                'Silahkan coba lagi'
                            );
                            $action->halt();
                        }
                    })
                    ->after(function ($livewire, DisplayDevice $record) {
                        if (! $record->wasChanged()){
                            SweetAlert::info(
                                $livewire, 
                                'Tidak ada perubahan', 
                            );
                            return;
                        }
                        if ($record->wasChanged('aktif') && ! $record->aktif) {
                            SweetAlert::warning(
                                $livewire, 
                                'Perangkat dinonaktifkan', 
                                'Perangkat ini tidak akan menerima data tamu baru sampai diaktifkan kembali'
                            );
                            return;
                        }
                        SweetAlert::success(
                            $livewire, 
                            'Perubahan tersimpan', 
                            "Data {$record->nama} sudah diperbarui."
                        );
                    }),
                Action::make('regenerateToken')
                    ->label('Buat Ulang Token')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Buat Ulang Token Perangkat')
                    ->modalDescription('Token lama akan tidak berlaku. Display yang sedang tersambung otomatis akan terputus dan harus dipasangkan ulang.')
                    ->modalSubmitActionLabel('Buat Token Baru')
                    ->action(function (DisplayDevice $record, $livewire) {
                        $token = Str::random(40);
                        $record->forceFill(['token_hash' => hash('sha256', $token)])->save();
                        SweetAlert::token(
                            $livewire,
                            'Token Perangkat Diperbarui',
                            $token
                        );
                    }),
            ])
            ->emptyStateHeading('Belum ada perangkat display');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDisplayDevices::route('/'),
        ];
    }
}
