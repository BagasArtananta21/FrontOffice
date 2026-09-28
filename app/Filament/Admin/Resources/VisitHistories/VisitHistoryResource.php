<?php

namespace App\Filament\Admin\Resources\VisitHistories;

use App\Filament\Admin\Resources\Kunjungans\KunjunganResource;
use App\Filament\Admin\Resources\VisitHistories\Pages\ManageVisitHistories;
use App\Models\Kunjungan;
use App\Models\Bidang;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Carbon\Carbon;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

class VisitHistoryResource extends Resource
{
    protected static ?string $model = Kunjungan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Riwayat Kunjungan';

    protected static ?string $title = 'Riwayat Kunjungan';

    protected static ?string $modelLabel = 'Kunjungan';

    protected static ?string $pluralModelLabel = 'Riwayat';

    protected static ?string $recordTitleAttribute = 'nama_tamu';

    protected static ?string $slug = 'riwayat-kunjungan';

    protected static ?int $navigationSort = 1;  

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Tamu')
                ->columns(2)
                ->schema([
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

                    IconEntry::make('sudah_dihubungi')
                        ->label('Sudah Dihubungi')
                        ->boolean(),

                    TextEntry::make('petugas.name')
                        ->label('Dicatat Oleh')
                        ->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['bidang', 'pegawai']))
            ->columns([
                TextColumn::make('waktu_datang')
                    ->label('Waktu Datang')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('nama_tamu')
                    ->label('Nama Tamu')
                    ->searchable()
                    ->description(fn (Kunjungan $record) => $record->instansi_asal),

                TextColumn::make('bidang.nama_bidang')
                    ->label('Bidang yang dituju')
                    ->wrap()
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('pegawai.nama_pegawai')
                    ->label('Pegawai yang dituju')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('keperluan')
                    ->label('Keperluan')
                    ->wrap()
                    ->limit(40)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('sudah_dihubungi')
                    ->label('Dihubungi')
                    ->boolean(),

                TextColumn::make('sumber_input')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === Kunjungan::SUMBER_FRONT_OFFICE ? 'Front Office' : 'Display')
                    ->color(fn (string $state) => $state === Kunjungan::SUMBER_FRONT_OFFICE ? 'gray' : 'info'),
            ])
            ->filters([
                Filter::make('rentang_tanggal')
                    ->label('Rentang tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari tanggal'),
                        DatePicker::make('until')->label('Sampai tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->where('waktu_datang', '>=', Carbon::parse($date)->startOfDay()))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->where('waktu_datang', '<=', Carbon::parse($date)->endOfDay()))),

                SelectFilter::make('bidang_id')
                    ->label('Bidang')
                    ->relationship('bidang', 'nama_bidang')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('sumber_input')
                    ->label('Sumber')
                    ->options([
                        Kunjungan::SUMBER_DISPLAY => 'Display',
                        Kunjungan::SUMBER_FRONT_OFFICE => 'Front Office',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Detail')
                    ->modalHeading(fn (Kunjungan $record) => "Detail Kunjungan - {$record->nama_tamu}")
                    ->modalWidth(Width::FourExtraLarge),
            ])
            ->recordAction('view')
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Belum ada riwayat kunjungan');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVisitHistories::route('/'),
        ];
    }
}
