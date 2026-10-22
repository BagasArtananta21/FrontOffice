<?php

namespace App\Filament\Admin\Resources\Kunjungans;

use App\Filament\Admin\Resources\Kunjungans\Pages\ManageKunjungans;
use App\Filament\Support\SweetAlert;
use App\Models\Kunjungan;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Enums\Width;
use Illuminate\Database\QueryException;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use UnitEnum;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Arr;
use App\Models\DisplayDevice;

class KunjunganResource extends Resource
{
    protected static ?string $model = Kunjungan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'keperluan';

    protected static ?string $navigationLabel = 'Tamu Hari Ini';

    protected static string|UnitEnum|null $navigationGroup = 'Buku Tamu';

    protected static ?string $modelLabel = 'Kunjungan';

    protected static ?string $pluralModelLabel = 'Kunjungan';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'kunjungan';

    public const SIGNATURE_DISPLAY = 'display';

    public const SIGNATURE_NONE = 'none';

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::formComponents());
    }
                
    public static function formComponents(): array
    {
        return [
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
                        ->autocomplete(false)
                        ->mutateStateForValidationUsing(fn (?string $state) => Kunjungan::normalizePhone($state))
                        ->dehydrateStateUsing(fn (?string $state) => Kunjungan::normalizePhone($state))
                        ->regex(Kunjungan::NO_HP_PATTERN)
                        ->validationMessages(['regex' => 'Format Nomor HP tidak valid.'])
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
                        ->rule(fn () => Rule::exists('bidang', 'id')->where('opd_id', Auth::user()->opd_id)),
    
                    TextInput::make('pegawai_tujuan')
                        ->label('Pegawai yang Dituju')
                        ->maxLength(255)
                        ->autocomplete(false),
    
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
        ];
    }

    public static function signatureSection(): Section
    {
        return Section::make('Tanda Tangan')
            ->columnSpanFull()
            ->schema([
                Radio::make('signature_method')
                    ->label('Cara tanda tangan')
                    ->options([
                        self::SIGNATURE_DISPLAY => 'Minta tanda tangan di display',
                        self::SIGNATURE_NONE => 'Tanpa tanda tangan',
                    ])
                    ->default(fn () => self::signatureDisplayState() === 'available' ? self::SIGNATURE_DISPLAY : self::SIGNATURE_NONE)
                    ->disableOptionWhen(fn (string $value) => $value === self::SIGNATURE_DISPLAY && self::signatureDisplayState() !== 'available')
                    ->helperText(fn () => match (self::signatureDisplayState()) {
                        'available' => 'Tamu memeriksa data lalu tanda tangan di display.',
                        'busy' => 'Display sedang dipakai. Tunggu sampai kembali ke layar idle.',
                        default => 'Display tidak terhubung.',
                    })
                    ->required()
                    ->live(),

                Select::make('alasan_tanpa_ttd')
                    ->label('Alasan tanpa tanda tangan')
                    ->options(fn () => Arr::only(
                        Kunjungan::NO_SIGNATURE_REASONS,
                        self::signatureDisplayState() === 'offline'
                            ? [Kunjungan::REASON_DISPLAY_UNAVAILABLE, Kunjungan::REASON_GUEST_LEFT, Kunjungan::REASON_GUEST_REFUSED]
                            : [Kunjungan::REASON_GUEST_LEFT, Kunjungan::REASON_GUEST_REFUSED],
                    ))
                    ->visible(fn (Get $get) => $get('signature_method') === self::SIGNATURE_NONE)
                    ->required(fn (Get $get) => $get('signature_method') === self::SIGNATURE_NONE),
            ]);
    }

    private static function signatureDisplayState(): string
    {
        return once(function () {
            $displays = DisplayDevice::query()->where('aktif', true);

            if (! (clone $displays)->connected()->exists()) {
                return 'offline';
            }

            return (clone $displays)->availableForSignature()->exists() ? 'available' : 'busy';
        });
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
                        ->placeholder(fn (Kunjungan $record) => $record->isWaitingForSignature()
                            ? 'Menunggu tanda tangan dari tamu.'
                            : 'Tanpa tanda tangan · '.(Kunjungan::NO_SIGNATURE_REASONS[$record->alasan_tanpa_ttd] ?? 'Tidak Ada Alasan Tercatat'))
                        ->imageHeight(120)
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

                        TextEntry::make('pegawai_tujuan')
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
                ->with(['bidang'])
                ->withExists('displayMenunggu')
                ->whereBetween('waktu_datang', [now()->startOfDay(), now()->endOfDay()]))
            ->columns([
                TextColumn::make('nomor_kunjungan')
                    ->label('Nomor Kunjungan')
                    ->placeholder('—')
                    ->sortable()
                    ->visibleFrom('md'),

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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
                
                TextColumn::make('alamat')
                    ->label('Alamat')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bidang.nama_bidang')
                    ->label('Bidang yang dituju')
                    ->wrap()
                    ->placeholder('—')
                    ->searchable()
                    ->visibleFrom('md'),

                TextColumn::make('pegawai_tujuan')
                    ->label('Pegawai yang dituju')
                    ->placeholder('—')
                    ->searchable()
                    ->visibleFrom('md'),

                TextColumn::make('keperluan')
                    ->label('Keperluan')
                    ->wrap()
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('sumber_input')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === Kunjungan::SUMBER_FRONT_OFFICE ? 'FrontOffice' : 'Display')
                    ->color(fn (string $state) => $state === Kunjungan::SUMBER_FRONT_OFFICE ? 'gray' : 'info')
                    ->visibleFrom('md'),

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
                    ->modalHeading(fn (Kunjungan $record) => "Detail Kunjungan - {$record->nama_tamu}")
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
            ->emptyStateIcon(Heroicon::OutlinedUserGroup)
            ->emptyStateHeading('Belum ada Tamu hari ini')
            ->emptyStateDescription('Tamu yang mengisi form di display atau diinput manual akan muncul disini');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageKunjungans::route('/'),
        ];
    }
}
