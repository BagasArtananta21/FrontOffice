<?php

namespace App\Filament\Admin\Resources\Pegawais;

use App\Filament\Admin\Resources\Pegawais\Pages\ManagePegawais;
use App\Models\Pegawai;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Database\QueryException;
use App\Filament\Support\SweetAlert;

class PegawaiResource extends Resource
{
    protected static ?string $model = Pegawai::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'nama_pegawai';

    protected static ?string $navigationLabel = 'Kelola Pegawai';

    protected static ?string $modelLabel = 'Pegawai';

    protected static ?string $pluralModelLabel = 'Pegawai';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'pegawai';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_pegawai')
                    ->label('Nama Pegawai')
                    ->required()
                    ->maxLength(255),

                TextInput::make('nip')
                    ->label('NIP')
                    ->maxLength(18)
                    ->rules(['nullable', 'digits_between:8,18'])
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('opd_id', Auth::user()->opd_id),
                    )
                    ->helperText('Boleh dikosongkan untuk pegawai non-ASN'),

                TextInput::make('jabatan')
                    ->label('Jabatan')
                    ->maxLength(255),

                Select::make('bidang_id')
                    ->label('Bidang')
                    ->relationship('bidang', 'nama_bidang', fn (Builder $query) => $query->active())
                    ->searchable()
                    ->preload()
                    ->rule(fn () => Rule::exists('bidang', 'id')->where('opd_id', Auth::user()->opd_id))
                    ->helperText('Kosongkan untuk pimpinan yang tidak berada di bawah bidang tertentu'),

                Toggle::make('aktif')
                    ->label('Aktif')
                    ->default(true)
                    ->inline(false)
                    ->helperText('Pegawai nonaktif tidak muncul di pilihan tujuan tamu'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_pegawai')
            ->columns([
                TextColumn::make('nama_pegawai')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Pegawai $record) => $record->jabatan),
                
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bidang.nama_bidang')
                    ->label('Bidang')
                    ->badge()
                    ->sortable()
                    ->placeholder('Tanpa Bidang'),

                IconColumn::make('aktif')
                    ->label('Aktif'),
                
                    TextColumn::make('created_at')
                    ->label('Dibuat Pada')  
                    ->datetime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->datetime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('bidang_id')
                    ->label('Bidang')
                    ->relationship('bidang', 'nama_bidang')
                    ->searchable()
                    ->preload(),
                
                TernaryFilter::make('aktif')
                    ->label('Aktif'),
            ])
            ->defaultPaginationPageOption(25)

            ->emptyStateHeading('Belum ada pegawai')

            ->recordActions([
                EditAction::make()
                    ->successNotification(null)
                    ->using(function (Pegawai $record, array $data, $livewire, EditAction $action) {
                        try {
                            $record->update($data);
                            return $record;
                        } catch (QueryException $e) {
                            report($e);
                            SweetAlert::error(
                                $livewire,
                                'Gagal memperbarui Pegawai',
                                'Terjadi kesalahan saat memperbarui Pegawai. Silakan coba lagi'
                            );
                            $action->halt();
                        }
                    })
                    ->after(function ($livewire, Pegawai $record) {
                        if (! $record->wasChanged()) {
                            SweetAlert::info($livewire, 'Tidak ada perubahan', 'Data pegawai tetap seperti sebelumnya.');
                            return;
                        }

                        if ($record->wasChanged('aktif') && ! $record->aktif) {
                            SweetAlert::warning(
                                $livewire,
                                'Pegawai dinonaktifkan',
                                "{$record->nama_pegawai} tidak lagi muncul di pilihan tujuan tamu."
                            );
                            return;
                        }

                        SweetAlert::success(
                            $livewire, 
                            'Perubahan tersimpan', 
                            "Data {$record->nama_pegawai} sudah diperbarui."
                        );
                    }),
            ]);
            
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePegawais::route('/'),
        ];
    }
}
