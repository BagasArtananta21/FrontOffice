<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\Support\SweetAlert;
use App\Services\SystemSettingService as Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use UnitEnum;

class SystemSettings extends Page
{
    protected static ?string $title = 'Pengaturan Sistem';

    protected static ?string $navigationLabel = 'Pengaturan Sistem';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $slug = 'pengaturan-sistem';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(app(Settings::class)->all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Slide layar idle')
                    ->columns(3)
                    ->schema([
                        self::numberField(Settings::SLIDE_MAX_COUNT, 'Jumlah maksimal slide per OPD', 'slide'),
                        self::numberField(Settings::SLIDE_MAX_SIZE_MB, 'Ukuran maksimal berkas slide', 'MB'),
                        self::numberField(Settings::SLIDE_DURATION_SECONDS, 'Lama tampil per slide', 'detik'),
                    ]),
                Section::make('Display tamu')
                    ->columns(3)
                    ->schema([
                        self::numberField(Settings::IDLE_SCREENSAVER_MINUTES, 'Waktu diam sebelum screensaver', 'menit'),
                        self::numberField(Settings::SIGNATURE_TIMEOUT_MINUTES, 'Batas waktu permintaan tanda tangan', 'menit'),
                        self::numberField(Settings::CONFIRMATION_SECONDS, 'Lama halaman konfirmasi', 'detik'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Simpan')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            app(Settings::class)->update($data);
        } catch (InvalidArgumentException $e) {
            report($e);
            SweetAlert::warning($this, 'Gagal Menyimpan Pengaturan', 'Nilai pengaturan tidak valid. Periksa kembali isian Anda.');

            return;
        }

        SweetAlert::success($this, 'Pengaturan Tersimpan', 'Perubahan berlaku untuk semua OPD.');
    }

    private static function numberField(string $key, string $label, string $unit): TextInput
    {
        $definition = Settings::DEFINITIONS[$key];

        return TextInput::make($key)
            ->label($label)
            ->integer()
            ->required()
            ->minValue($definition['min'])
            ->maxValue($definition['max'])
            ->suffix($unit)
            ->helperText("Antara {$definition['min']} dan {$definition['max']}. Bawaan {$definition['default']}.");
    }
}