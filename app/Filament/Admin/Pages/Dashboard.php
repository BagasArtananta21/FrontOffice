<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Support\SweetAlert;
use App\Models\DisplayDevice;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array {
        return [
            Action::make('toggleDisplayForm')
                ->label(fn () => $this->displayDevice()?->tampilkan_form ? 'kembali ke layar idle' : 'Tampilkan form tamu')
                ->color(fn () => $this->displayDevice()?->tampilkan_form ? 'gray' : 'primary')
                ->action(function () {
                    $device = $this->displayDevice();

                    if(! $device){
                        SweetAlert::warning(
                            $this,
                            'Display Belum Terdaftar',
                            'Belum ada perangkat display aktif untuk OPD ini'
                        );
                        return;
                    }
                    
                    $device->update(['tampilkan_form' => ! $device->tampilkan_form]);

                    if(! $device->tampilkan_form) {
                        SweetAlert::info(
                            $this,
                            'Display kembali ke Layar Idle'
                        );
                        return;
                    }


                    if(! $device->isConnected()){
                        SweetAlert::warning(
                            $this,
                            'Display Tidak Terhubung',
                            'Form akan tampil begitu display terhubung kembali'
                        );
                        return;
                    }
                    
                    SweetAlert::success(
                        $this,
                        'Form Tamu Ditampilkan',
                        'Form tamu akan tampil di layar display dalam beberapa detik'
                    );
                }),
        ];
    }

    private function displayDevice(): ?DisplayDevice {
        return DisplayDevice::query()->where('aktif', true)->first();
    }
}