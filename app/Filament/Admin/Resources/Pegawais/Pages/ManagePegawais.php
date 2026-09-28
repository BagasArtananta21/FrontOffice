<?php

namespace App\Filament\Admin\Resources\Pegawais\Pages;

use App\Models\Pegawai;
use App\Filament\Admin\Resources\Pegawais\PegawaiResource;
use App\Filament\Support\SweetAlert;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use illuminate\Database\QueryException;

class ManagePegawais extends ManageRecords
{
    protected static string $resource = PegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->successNotificationTitle('null')
                ->using(function (array $data, string $model, $livewire, CreateAction $action){
                    try {
                        return $model::create($data);
                    } catch (QueryException $e) {
                        report ($e);
                        SweetAlert::error(
                            $livewire,
                            'Gagal menambahkan Pegawai',
                            'Terjadi Kesalahan saat menambahkan Pegawai. Silahkan coba lagi.'
                        );
                        $action->halt();
                    }
                })
                ->after(fn ($livewire, Pegawai $record) => SweetAlert::success(
                    $livewire,
                    'Pegawai berhasil ditambahkan',
                    "Data {$record->nama_pegawai} sudah masuk ke daftar pegawai."
                ))

        ];
    }
}
