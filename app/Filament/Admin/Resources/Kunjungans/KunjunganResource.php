<?php

namespace App\Filament\Admin\Resources\Kunjungans;

use App\Filament\Admin\Resources\Kunjungans\Pages\ManageKunjungans;
use App\Models\Kunjungan;
use App\Models\Pegawai;
use App\Models\Bidang;
use App\Models\Tamu;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Utilities\{Get, Set};
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;

class KunjunganResource extends Resource
{
    protected static ?string $model = Kunjungan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'keperluan';

    protected static ?string $navigationLabel = 'Tamu Hari Ini';

    protected static ?string $modelLabel = 'Kunjungan';

    protected static ?string $pluralModelLabel = 'Kunjungan';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'kunjungan';

    public static function form(Schema $schema): Schema
    {
        return $schema->
            components([
                Section::make('Data Tamu')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_tamu')
                            ->label('Nama Tamu')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Radio::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(Tamu::JENIS_KELAMIN)
                            ->required()
                            ->inline(),

                        TextInput::make('no_hp')
                            ->label('Nomor HP')
                            ->tel()
                            ->maxLength(15)
                            ->helperText('Diisi bila tamu perlu dihubungi kembali'),

                        TextInput::make('instansi_asal')
                            ->label('Instansi Asal')
                            ->maxLength(255),

                        TextInput::make('alamat')
                            ->label('Alamat')
                            ->maxLength(255),
                    ]),

                Section::make('Kunjungan')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('waktu_datang')
                            ->label('Waktu Datang')
                            ->seconds(false)
                            ->default(now())
                            ->required()
                            ->maxDate(now()),

                        Select::make('bidang_id')
                            ->label('Bidang yang Dituju')
                            ->options(fn () => Bidang::active()->orderBy('nama_bidang')->pluck('nama_bidang', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('pegawai_id', null))
                            ->rule(fn () => Rule::exists('bidang', 'id')->where('opd_id', Auth::user()->opd_id)),

                        Select::make('pegawai_id')
                            ->label('Pegawai yang Dituju')
                            ->options(function (Get $get) {
                                return Pegawai::active()
                                    ->when(
                                        $get('bidang_id'),
                                        fn (Builder $query, $bidangId) => $query->where(
                                            fn (Builder $sub) => $sub->where('bidang_id', $bidangId)->orWhereNull('bidang_id'),
                                        ),
                                    )
                                    ->orderBy('nama_pegawai')
                                    ->pluck('nama_pegawai', 'id');
                            })
                            ->searchable()
                            ->rule(fn () => Rule::exists('pegawai', 'id')->where('opd_id', Auth::user()->opd_id)),

                        Textarea::make('keperluan')
                            ->label('Keperluan')
                            ->required()
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('catatan_petugas')
                            ->label('Keterangan')
                            ->maxLength(1000)
                            ->rows(2)
                            ->helperText('Catatan dari petugas, tidak diisi tamu')
                            ->columnSpanFull(),
                    ]),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('waktu_datang', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['tamu', 'bidang', 'pegawai'])
                ->whereBetween('waktu_datang', [now()->startOfDay(), now()->endOfDay()]))
            ->columns([
                TextColumn::make('waktu_datang')
                    ->label('Jam')
                    ->dateTime('H:i')
                    ->sortable(),

                TextColumn::make('tamu.nama_tamu')
                    ->label('Nama Tamu')
                    ->searchable()
                    ->description(fn (Kunjungan $record) => $record->tamu->instansi_asal),
                
                TextColumn::make('tamu.no_hp')
                    ->label('Nomor HP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('tamu.alamat')
                    ->label('Alamat')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bidang.nama_bidang')
                    ->label('Bidang')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('pegawai.nama_pegawai')
                    ->label('Pegawai')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('keperluan')
                    ->label('Keperluan')
                    ->wrap()
                    ->limit(60)
                    ->searchable(),

                ToggleColumn::make('sudah_dihubungi')
                    ->label('Dihubungi'),

                TextColumn::make('sumber_input')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'manual' ? 'Manual' : 'Display')
                    ->color(fn (string $state) => $state === 'manual' ? 'gray' : 'info'),

                TextColumn::make('petugas.name')
                    ->label('Dicatat Oleh')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('bidang_id')
                    ->label('Bidang')
                    ->relationship('bidang', 'nama_bidang')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('sumber_input')
                    ->label('Sumber')
                    ->options([
                        'display' => 'Display',
                        'manual' => 'Manual',
                    ]),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Belum ada tamu hari ini');

    }

    public static function getPages(): array
    {
        return [
            'index' => ManageKunjungans::route('/'),
        ];
    }
}
