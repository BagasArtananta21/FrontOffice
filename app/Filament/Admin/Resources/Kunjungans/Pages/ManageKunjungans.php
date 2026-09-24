<?php

namespace App\Filament\Admin\Resources\Kunjungans\Pages;

use App\Filament\Admin\Resources\Kunjungans\KunjunganResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use App\Filament\Support\SweetAlert;
use App\Services\KunjunganService;
use App\Models\Kunjungan;
use Illuminate\Support\Facades\Auth;
use App\Filament\Admin\Widgets\GuestStats;
use App\Filament\Admin\Widgets\DisplayStatus;

class ManageKunjungans extends ManageRecords
{
    protected static string $resource = KunjunganResource::class;

    protected static ?string $title = 'Tamu Hari Ini';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Input Tamu Manual')
                ->modalHeading('Input Tamu Manual')
                ->modalSubmitActionLabel('Simpan')
                ->createAnother(false)
                ->modalWidth(Width::SixExtraLarge)
                ->successNotificationTitle(null)
                ->using(function (array $data, $livewire, CreateAction $action){
                    try {
                        return app(KunjunganService::class)->record(
                            $data,
                            Kunjungan::SUMBER_MANUAL,
                            Auth::id(),
                        );
                    } catch (\Exception $e) {
                        report ($e);
                        SweetAlert::error(
                            $livewire,
                            'Gagal menambahkan Tamu',
                            'Terjadi Kesalahan saat menambahkan Tamu. Silahkan coba lagi.'
                        );
                        $action->halt();
                    }
                })
                ->after(fn ($livewire, Kunjungan $record) => SweetAlert::success(
                    $livewire,
                    'Tamu berhasil ditambahkan',
                    "Data {$record->tamu->nama_tamu} sudah masuk ke daftar kunjungan."
                )),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GuestStats::class,
            DisplayStatus::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }
}
