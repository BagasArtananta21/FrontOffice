<?php

namespace App\Filament\Admin\Resources\SlideIdles;

use App\Filament\Admin\Resources\SlideIdles\Pages\ManageSlideIdles;
use App\Filament\Support\SweetAlert;
use App\Models\SlideIdle;
use App\Services\IdleSlideService;
use App\Services\SystemSettingService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;

class SlideIdleResource extends Resource
{
    protected static ?string $model = SlideIdle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Slide Display';

    protected static string|UnitEnum|null $navigationGroup = 'Buku Tamu';

    protected static ?string $modelLabel = 'Slide';

    protected static ?string $pluralModelLabel = 'Slide Layar idle';

    protected static ?string $slug = 'slide-layar-idle';

    public static function form(Schema $schema): Schema
    {
        $maxMb = fn (): int => app(SystemSettingService::class)->get(SystemSettingService::SLIDE_MAX_SIZE_MB);

        return $schema->components([
            FileUpload::make('gambar')
                ->label('Gambar')
                ->required()
                ->acceptedFileTypes(['image/png', 'image/jpeg'])
                ->maxSize(fn (): int => $maxMb() * 1024)
                ->storeFiles(false)
                ->helperText(fn (): string => 'PNG atau JPEG, maksimal '.$maxMb().' MB. Ukuran yang disarankan 1920 x 1080 piksel (Landscape).'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->reorderable('urutan')
            ->columns([
                ImageColumn::make('gambar')->label('Slide')->disk(SlideIdle::DISK)->height(72),
                TextColumn::make('urutan')->label('Urutan'),
            ])
            ->recordActions([
                Action::make('remove')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->tooltip('Hapus slide')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Slide')
                    ->modalDescription('Slide dihapus dari layar. Display memuat setelah di refresh.')
                    ->modalSubmitActionLabel('Hapus')
                    ->action(function (SlideIdle $record, $livewire): void {
                        app(IdleSlideService::class)->remove($record);
                        SweetAlert::success(
                            $livewire,
                            'Slide Dihapus',
                            'Slide sudah dihapus dari layar idle.'
                        );
                    })
            ])
            ->emptyStateIcon(Heroicon::OutlinedPhoto)
            ->emptyStateHeading('Belum ada slide')
            ->emptyStateDescription('Tanpa slide, layar display menampilkan logo dan nama instansi.');

    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSlideIdles::route('/'),
        ];
    }
}
