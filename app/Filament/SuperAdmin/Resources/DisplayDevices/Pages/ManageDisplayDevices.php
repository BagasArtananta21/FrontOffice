<?php

namespace App\Filament\SuperAdmin\Resources\DisplayDevices\Pages;

use App\Filament\SuperAdmin\Resources\DisplayDevices\DisplayDeviceResource;
use App\Filament\Support\SweetAlert;
use App\Models\DisplayDevice;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class ManageDisplayDevices extends ManageRecords
{
    protected static string $resource = DisplayDeviceResource::class;

    protected static ?string $title = 'Kelola Perangkat Display';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Perangkat')
                ->modalheading('Tambah Perangkat Display')
                ->modalSubmitActionLabel('Simpan')
                ->createAnother(false)
                ->successNotification(null)
                ->using(function (array $data, $livewire, CreateAction $action){
                    $token = Str::random(40);
                    $device = null;

                    try {
                        $device = new DisplayDevice();
                        $device->forceFill([
                            ...$data,
                            'token_hash' => hash('sha256', $token),
                        ]) -> save();
                    } catch (QueryException $e) {
                        report($e);
                        SweetAlert::error(
                            $livewire,
                            'Gagal Menambah Perangkat', 
                            'Silahkan coba lagi'
                        );
                        $action->halt();
                    }
                    SweetAlert::token(
                        $livewire,
                        "Token {$device->nama} Berhasil Dibuat",
                        $token
                    );

                    return $device;
                }),

        ];
    }
}
