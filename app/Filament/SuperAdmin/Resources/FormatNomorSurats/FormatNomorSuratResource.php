<?php

namespace App\Filament\SuperAdmin\Resources\FormatNomorSurats;

use App\Filament\SuperAdmin\Resources\FormatNomorSurats\Pages\ManageFormatNomorSurats;
use App\Filament\Support\SweetAlert;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\FormatNomorSurat;
use App\Services\LetterNumberFormatter;
use Closure;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\TextEntry;
use App\Models\Opd;
use Filament\Support\Enums\Width;
use Filament\Tables\Filters\SelectFilter;

class FormatNomorSuratResource extends Resource
{
    protected static ?string $model = FormatNomorSurat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static ?string $navigationLabel = 'Format Nomor Surat';

    protected static ?string $modelLabel = 'Format Nomor Surat';

    protected static ?string $pluralModelLabel = 'Format Nomor Surat';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'format-nomor-surat';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Versi Format')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('opd_id')
                        ->label('OPD')
                        ->relationship('opd', 'nama_opd', fn (Builder $query) => $query->active())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->disabledOn('edit'),
                    
                    DatePicker::make('berlaku_sejak')
                        ->label('Berlaku Sejak')
                        ->required()
                        ->default(now()->startOfYear())
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('opd_id', $get('opd_id')),
                        )
                        ->validationMessages([
                            'unique' => 'OPD ini sudah punya yang berlaku sejak tanggal tersebut.',
                        ]),
                    
                    TextInput::make('dasar_perubahan')
                        ->label('Dasar Perubahan')
                        ->placeholder('Peraturan Walikota No. 1 Tahun 2024')
                        ->maxLength(255)
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),
            Section::make('Susunan Nomor')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Repeater::make('susunan')
                        ->label('Bagian nomor, urut dari kiri ke kanan')
                        ->schema([
                        Select::make('jenis')
                            ->label('Bagian')
                            ->options(LetterNumberFormatter::SEGMENTS)
                            ->required()
                            ->live(),
                        
                        TextInput::make('teks')
                            ->label('Isi teks')
                            ->visible(fn (Get $get) => $get('jenis') === 'teks')
                            ->required(fn (Get $get) => $get('jenis') === 'teks')
                            ->maxLength(30),

                        ])
                    ->default([
                        ['jenis' => 'klasifikasi'],
                        ['jenis' => 'urut'],
                        ['jenis' => 'bidang'],
                        ['jenis' => 'kode_opd'],
                        ['jenis' => 'bulan_romawi'],
                        ['jenis' => 'tahun'],
                    ])
                    ->columns(2)
                    ->reorderable()
                    ->addActionLabel('Tambah Bagian')
                    ->minItems(1)
                    ->live()
                    ->rule(fn () => function (string $attribute, mixed $value, Closure $fail){
                        if (collect($value) -> where('jenis', 'urut') -> count() !== 1) {
                            $fail('Susunan harus memuat tepat satu "Nomor Urut".');
                        }
                    })
                    ->columnSpanFull(),
                
                TextEntry::make('contoh')
                    ->label('Contoh hasil')
                    ->state(fn (Get $get) => app(LetterNumberFormatter::class) -> preview(
                        $get('susunan') ?? [],
                        Opd::find($get('opd_id'))?->kode_opd,
                    ))
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['opd', 'pembuat']))
            ->defaultSort('berlaku_sejak', 'desc')
            ->columns([
                TextColumn::make('opd.nama_opd')
                    ->label('OPD')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('berlaku_sejak')
                    ->label('Berlaku Sejak')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (FormatNomorSurat $record) => $record->statusLabel())
                    ->color(fn (string $state) => match ($state) {
                        'Berlaku' => 'success',
                        'Terjadwal' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('contoh')
                    ->label('Contoh Nomor Terbit')
                    ->fontFamily('mono')
                    ->state(fn (FormatNomorSurat $record) => app(LetterNumberFormatter::class)->preview(
                        $record->susunan,
                        $record->opd->kode_opd,
                    )),
                
                TextColumn::make('dasar_perubahan')
                    ->label('Dasar Perubahan')
                    ->placeholder('—')
                    ->wrap(),
                
                TextColumn::make('pembuat.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('opd_id')
                    ->label('OPD')
                    ->relationship('opd', 'nama_opd')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth(Width::FourExtraLarge)
                    ->successNotification(null)
                    ->visible(fn (FormatNomorSurat $record) => ! $record->nomorSurat()->exists())
                    ->after(fn ($livewire, FormatNomorSurat $record) => SweetAlert::success(
                        $livewire,
                        'Format berhasil diperbarui',
                        "Format {$record->opd->nama_opd} berlaku sejak {$record->berlaku_sejak->translatedFormat('d F Y')} sudah diperbarui."
                    )),
            ])
            ->emptyStateHeading('Belum ada format nomor surat');

    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFormatNomorSurats::route('/'),
        ];
    }
}
