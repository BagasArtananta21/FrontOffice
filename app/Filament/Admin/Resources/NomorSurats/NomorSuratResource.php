<?php

namespace App\Filament\Admin\Resources\NomorSurats;

use App\Filament\Admin\Resources\NomorSurats\Pages\ManageNomorSurats;
use App\Filament\Support\SweetAlert;
use App\Models\NomorSurat;
use App\Models\Bidang;
use App\Models\User;
use App\Models\FormatNomorSurat;
use App\Services\LetterNumberFormatter;
use App\Services\LetterNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Carbon;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Livewire\Livewire;

class NomorSuratResource extends Resource
{
    protected static ?string $model = NomorSurat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $navigationLabel = 'Nomor Surat';

    protected static ?string $modelLabel = 'Nomor Surat';

    protected static ?string $pluralModelLabel = 'Nomor Surat';

    protected static ?string $slug = 'nomor-surat';

    public static function form(Schema $schema): Schema
    {
        $format = FormatNomorSurat::query()->berlakuPada(now())->first();
        $segments = self::numberSegments($format);

        return $schema->components([
            Section::make('Nomor Surat')
                ->description('Bagian yang terkunci diisi otomatis oleh sistem.')
                ->columns(max(count($segments) + 1, 1))
                ->columnSpanFull()
                ->schema($segments),

            Section::make('Detail Surat')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('nama_peminta')
                        ->label('Nama Peminta')
                        ->required()
                        ->maxLength(255)
                        ->autocomplete(false),

                    TextInput::make('tujuan_surat')
                        ->label('Tujuan Surat')
                        ->maxLength(255)
                        ->autocomplete(false),

                    TextInput::make('perihal')
                        ->label('Perihal')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->autocomplete(false),
                    
                    DatePicker::make('tanggal_surat')
                        ->label('Tertanggal')
                        ->required()
                        ->default(today())
                        ->maxDate(today())
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            if (blank($state)) {
                                return;
                            }

                            $tanggal = Carbon::parse($state);

                            $set('segmen_bulan_romawi', LetterNumberFormatter::ROMAN_MONTHS[$tanggal->month]);
                            $set('segmen_tahun', (string) $tanggal->year);
                        })

                        ->helperText('Tanggal mundur akan mendapat nomor susulan (contoh: 103.1) bila sudah ada nomor yang terbit setelah tanggal tersebut.'),
                ]),
        ]);
    }

    private static function numberSegments(?FormatNomorSurat $format): array
    {
        if (! $format) {
            return [];
        }

        return collect($format->susunan)
            ->values()
            ->map(fn (array $bagian, int $index) => match ($bagian['jenis'] ?? null) {
                'klasifikasi' => TextInput::make('kode_klasifikasi')
                    ->label('Kode klasifikasi')
                    ->required()
                    ->maxLength(20)
                    ->regex('/^\d+(\.\d+)*$/')
                    ->validationMessages(['regex' => 'Hanya angka dan titik, contoh: 443.32'])
                    ->autocomplete(false),

                'bidang' => Select::make('bidang_id')
                    ->label('Bidang')
                    ->options(fn () => Bidang::active()->orderBy('nama_bidang')->pluck('nama_bidang', 'id'))
                    ->searchable()
                    ->required()
                    ->columnSpan(2)
                    ->rule(fn () => Rule::exists('bidang', 'id')
                        ->where('opd_id', Auth::user()->opd_id)
                        ->where('aktif', true)),

                default => self::fixedSegment($index, $bagian, Auth::user()),
            })
            ->all();
    }

    private static function fixedSegment(int $index, array $bagian, User $user): TextInput
    {
        $jenis = $bagian['jenis'] ?? null;

        [$label, $value] = match ($jenis) {
            'urut' => ['Nomor Urut', 'Otomatis'],
            'kode_opd' => ['Kode OPD', $user->opd->kode_opd],
            'bulan_romawi' => ['Bulan', LetterNumberFormatter::ROMAN_MONTHS[today()->month]],
            'tahun' => ['Tahun', (string) today()->year],
            default => ['Teks', $bagian['teks'] ?? ''],
        };

        $name = in_array($jenis, ['bulan_romawi', 'tahun'], true)
            ? "segmen_{$jenis}"
            : "segmen_{$index}";

        return TextInput::make($name)
            ->label($label)
            ->default($value)
            ->disabled()
            ->dehydrated(false);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query
                ->orderByDesc('tahun')
                ->orderByDesc('nomor_urut')
                ->orderByDesc('sub_nomor'))
            ->modifyQueryUsing(fn (Builder $query) => $query->with('bidang'))
            ->columns([
                TextColumn::make('nomor_urut')
                    ->label('No')
                    ->formatStateUsing(fn (int $state, NomorSurat $record) => $record->sub_nomor > 0
                        ? "{$state}.{$record->sub_nomor}"
                        : (string) $state),

                TextColumn::make('kode_klasifikasi')
                    ->label('Kode')
                    ->searchable(),

                TextColumn::make('perihal')
                    ->label('Perihal')
                    ->wrap()
                    ->limit(60)
                    ->searchable(),

                TextColumn::make('bidang.nama_bidang')
                    ->label('Dari')
                    ->placeholder('-'),

                TextColumn::make('nomor_lengkap')
                    ->label('No Surat')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('tanggal_surat')
                    ->label('Tgl Surat')
                    ->date('d/m/Y'),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => $state === NomorSurat::STATUS_BATAL ? 'danger' : 'success')
                    ->formatStateUsing(fn (string $state) => $state === NomorSurat::STATUS_BATAL ? 'Dibatalkan' : 'Terbit'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Detail')
                    ->modalHeading(fn (NomorSurat $record) => "Detail Nomor Surat - {$record->nomor_lengkap}")
                    ->modalWidth(Width::TwoExtraLarge),
                
                Action::make('cancel')
                    ->label('Batalkan')
                    ->color('danger')
                    ->visible( fn (NomorSurat $record) => $record->status === NomorSurat::STATUS_TERBIT)
                    ->modalHeading( fn (NomorSurat $record) => "Batalkan Nomor {$record->nomor_lengkap}?")
                    ->modalDescription('Nomor yang dibatalkan tidak dipakai ulang dan tidak bisa dipulihkan.')
                    ->modalSubmitActionLabel('Batalkan Nomor')
                    ->schema([
                        Textarea::make('alasan_batal')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->maxLength(255)
                            ->rows(3),
                    ])
                    ->action(function (NomorSurat $record, array $data, $livewire ){
                        $berhasil = app(LetterNumberService::class)->cancel($record, $data['alasan_batal'], Auth::user());
                        
                        $berhasil 
                            ? SweetAlert::success(
                                $livewire,
                                'Nomor Surat Dibatalkan',
                                $record->nomor_lengkap,
                            )
                            : SweetAlert::warning(
                                $livewire,
                                'Sudah Dibatalkan',
                                'Nomor surat ini sudah dibatalkan sebelumnya.',
                            );

                    }),
            ])
            ->recordAction('view')
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Belum ada nomor surat');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextEntry::make('nomor_lengkap')
                        ->label('Nomor Surat')
                        ->copyable()
                        ->columnSpanFull(),

                    TextEntry::make('kode_klasifikasi')
                        ->label('Kode Klasifikasi'),

                    TextEntry::make('tanggal_surat')
                        ->label('Tanggal Surat')
                        ->date('d F Y'),

                    TextEntry::make('perihal')
                        ->label('Perihal')
                        ->columnSpanFull(),

                    TextEntry::make('tujuan_surat')
                        ->label('Tujuan Surat')
                        ->placeholder('-')
                        ->columnSpanFull(),

                    TextEntry::make('bidang.nama_bidang')
                        ->label('Dari Bidang')
                        ->placeholder('-'),

                    TextEntry::make('nama_peminta')
                        ->label('Nama Peminta')
                        ->placeholder('-'),
                    ]),
                Section::make()
                    ->columns(2)
                    ->schema([
                        
                        TextEntry::make('pembuat.name')
                        ->label('Dicatat Oleh'),
    
                        TextEntry::make('tanggal_terbit')
                            ->label('Diterbitkan Pada')
                            ->dateTime('d F Y · H:i'),
                        
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => $state === NomorSurat::STATUS_BATAL ? 'Dibatalkan' : 'Terbit')
                            ->color(fn (string $state) => $state === NomorSurat::STATUS_BATAL ? 'danger' : 'success'),
    
                        TextEntry::make('alasan_batal')
                            ->label('Alasan Pembatalan')
                            ->columnSpanFull()
                            ->visible(fn (NomorSurat $record) => $record->status === NomorSurat::STATUS_BATAL),
                            
                        TextEntry::make('pembatal.name')
                            ->label('Dibatalkan Oleh')
                            ->visible(fn (NomorSurat $record) => $record->status === NomorSurat::STATUS_BATAL),
                        
                        TextEntry::make('batal_pada')
                            ->label('Dibatalkan Pada')
                            ->dateTime('d F Y · H:i')
                            ->visible(fn (NomorSurat $record) => $record->status === NomorSurat::STATUS_BATAL),
                    ]),
        ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => ManageNomorSurats::route('/'),
        ];
    }
}
