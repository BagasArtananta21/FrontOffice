<?php

namespace App\Filament\Admin\Resources\Kunjungans;

use App\Filament\Admin\Resources\Kunjungans\Pages\ManageKunjungans;
use App\Filament\Support\SweetAlert;
use App\Models\Kunjungan;
use App\Models\Pegawai;
use App\Models\Bidang;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Radio;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Utilities\{Get, Set};
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\CheckboxColumn;
use Illuminate\Database\QueryException;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;

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
                            ->autocomplete(false)
                            ->columnSpanFull(),

                        Radio::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(Kunjungan::JENIS_KELAMIN)
                            ->required()
                            ->inline(),

                        TextInput::make('no_hp')
                            ->label('Nomor HP')
                            ->tel()
                            ->maxLength(15)
                            ->autocomplete(false)
                            ->helperText('Diisi bila tamu perlu dihubungi kembali'),

                        TextInput::make('instansi_asal')
                            ->label('Instansi Asal')
                            ->autocomplete(false)
                            ->maxLength(255),

                        TextInput::make('alamat')
                            ->label('Alamat')
                            ->autocomplete(false)
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
                            ->maxDate(now())
                            ->disabledOn('edit'),

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
                            ->autocomplete(false)
                            ->columnSpanFull(),

                        Textarea::make('catatan_petugas')
                            ->label('Keterangan')
                            ->maxLength(1000)
                            ->rows(2)
                            ->helperText('Catatan dari petugas, tidak diisi tamu')
                            ->autocomplete(false)
                            ->columnSpanFull(),
                    ]),
            
            ]);

    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Tamu')
                ->columns(2)
                ->schema([
                    TextEntry::make('nomor_kunjungan')
                        ->label('Nomor Kunjungan'),

                    TextEntry::make('nama_tamu')
                        ->label('Nama Tamu'),

                    TextEntry::make('jenis_kelamin')
                        ->label('Jenis Kelamin')
                        ->formatStateUsing(fn (string $state) => Kunjungan::JENIS_KELAMIN[$state] ?? $state),

                    TextEntry::make('no_hp')
                        ->label('Nomor HP')
                        ->placeholder('—')
                        ->copyable(),

                    TextEntry::make('instansi_asal')
                        ->label('Instansi Asal')
                        ->placeholder('—'),

                    TextEntry::make('alamat')
                        ->label('Alamat')
                        ->placeholder('—')
                        ->columnSpanFull(),
                    
                    ImageEntry::make('tanda_tangan')
                        ->label('Tanda Tangan')
                        ->disk('local')
                        ->visibility('private')
                        ->imageHeight(120)
                        ->placeholder('input manual')
                        ->columnSpanFull(),
                ]),
                
            Section::make('Kunjungan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('waktu_datang')
                            ->label('Waktu Datang')
                            ->dateTime('l, d F Y · H:i'),
                        
                        TextEntry::make('sumber_input')
                            ->label('Sumber')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => $state === Kunjungan::SUMBER_FRONT_OFFICE ? 'Front Office' : 'Display')
                            ->color(fn (string $state) => $state === Kunjungan::SUMBER_FRONT_OFFICE ? 'gray' : 'info'),

                        TextEntry::make('bidang.nama_bidang')
                            ->label('Bidang yang Dituju')
                            ->placeholder('—'),

                        TextEntry::make('pegawai.nama_pegawai')
                            ->label('Pegawai yang Dituju')
                            ->placeholder('—'),

                        TextEntry::make('keperluan')
                            ->label('Keperluan')
                            ->columnSpanFull(),

                        TextEntry::make('catatan_petugas')
                            ->label('Keterangan')
                            ->placeholder('—')
                            ->columnSpanFull(),

                        TextEntry::make('petugas.name')
                            ->label('Ditampilkan/Dicatat Oleh')
                            ->placeholder('—'),
                    ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('waktu_datang', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['bidang', 'pegawai'])
                ->whereBetween('waktu_datang', [now()->startOfDay(), now()->endOfDay()]))
            ->columns([
                TextColumn::make('nomor_kunjungan')
                    ->label('Nomor Kunjungan')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('waktu_datang')
                    ->label('Jam')
                    ->dateTime('H:i')
                    ->sortable(),

                TextColumn::make('nama_tamu')
                    ->label('Nama Tamu')
                    ->searchable()
                    ->description(fn (Kunjungan $record) => $record->instansi_asal),
                
                TextColumn::make('no_hp')
                    ->label('Nomor HP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('alamat')
                    ->label('Alamat')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bidang.nama_bidang')
                    ->label('Bidang yang dituju')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('pegawai.nama_pegawai')
                    ->label('Pegawai yang dituju')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('keperluan')
                    ->label('Keperluan')
                    ->wrap()
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('sumber_input')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === Kunjungan::SUMBER_FRONT_OFFICE ? 'FrontOffice' : 'Display')
                    ->color(fn (string $state) => $state === Kunjungan::SUMBER_FRONT_OFFICE ? 'gray' : 'info'),

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
                        Kunjungan::SUMBER_FRONT_OFFICE => 'FrontOffice',
                        Kunjungan::SUMBER_DISPLAY => 'Display',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Detail')
                    ->modalheading(fn (Kunjungan $record) => "Detail Kunjungan - {$record->nama_tamu}")
                    ->modalWidth(Width::FourExtraLarge),
                EditAction::make()
                    ->modalHeading('Ubah Data Kunjungan')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalWidth(Width::SixExtraLarge)
                    ->successNotification(null)
                    ->using(function (Kunjungan $record, array $data, $livewire, EditAction $action){
                        try {
                            $record->update($data);
                            return $record;
                        } catch (QueryException $e) {
                            report ($e);
                            SweetAlert::error(
                                $livewire,
                                'Gagal memperbarui data Kunjungan',
                                'Terjadi Kesalahan saat memperbarui Data. Silahkan coba lagi.'
                            );
                            $action->halt();
                        }
                    })
                    ->after(function ($livewire, Kunjungan $record){
                        if (! $record->wasChanged()) {
                            SweetAlert::info(
                                $livewire, 
                                'Tidak ada perubahan', 
                                'Data kunjungan tetap seperti sebelumnya.'
                            );
                            return;
                        }
                        SweetAlert::success(
                            $livewire,
                            'Data Kunjungan berhasil diperbarui',
                            "Data kunjungan {$record->nama_tamu} sudah diperbarui."
                        );
                    }), 
            ])
            ->toolbarActions([])
            ->defaultPaginationPageOption(25)
            ->recordAction('view')
            ->emptyState(view('filament.admin.tables.kunjungan-empty'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageKunjungans::route('/'),
        ];
    }
}
