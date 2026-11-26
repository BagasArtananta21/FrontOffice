<?php

namespace App\Filament\Admin\Resources\SlideIdles\Pages;

use App\Exceptions\SlideStorageException;
use App\Filament\Admin\Resources\SlideIdles\SlideIdleResource;
use App\Filament\Support\SweetAlert;
use App\Models\SlideIdle;
use App\Services\IdleSlideService;
use App\Services\SystemSettingService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use InvalidArgumentException;

class ManageSlideIdles extends ManageRecords
{
    protected static string $resource = SlideIdleResource::class;

    protected function getHeaderActions(): array
    {
        $limitReached = fn (): bool => SlideIdle::query()->count()
            >= app(SystemSettingService::class)->get(SystemSettingService::SLIDE_MAX_COUNT);

            return [
                CreateAction::make()
                    ->label('Tambah Slide')
                    ->successNotification(null)
                    ->disabled($limitReached)
                    ->tooltip(fn (): ?string => $limitReached()
                        ? 'Jumlah slide sudah mencapai batas. Hapus salah satu slide lama.'
                        : null)
                    ->using(function (array $data, $livewire, CreateAction $action){
                        try {
                            return app(IdleSlideService::class)->add($data['gambar']);
                        } catch (InvalidArgumentException $e){
                            SweetAlert::warning(
                                $livewire,
                                'Slide Tidak Dapat Ditambahkan',
                                $e->getMessage()
                            );
                            $action->halt();
                        } catch (SlideStorageException $e) {
                            report($e);
                            SweetAlert::error(
                                $livewire,
                                'Gagal Menambahkan Slide',
                                'Terjadi kesalahan saat menyimpan slide. Silahkan coba lagi.'
                            );
                            $action->halt();
                        }
                    })
                    ->after(fn ($livewire) => SweetAlert::success(
                        $livewire,
                        'Slide Ditambahkan',
                        'Slide tampil di display setelah display dimuat ulang.'
                    )),
            ];
    }
}
