<?php

namespace App\Filament\Admin\Resources\Bidangs\Pages;

use App\Filament\Admin\Resources\Bidangs\BidangResource;
use App\Filament\Support\SweetAlert;
use App\Models\Bidang;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\QueryException;

class ManageBidangs extends ManageRecords
{
    protected static string $resource = BidangResource::class;

    protected static ?string $title = 'Kelola Bidang';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->successNotification(null)
                ->using(function (array $data, string $model, $livewire, CreateAction $action){
                    try {
                        return $model::create($data);
                    } catch (QueryException $e) {
                        report($e);
                        SweetAlert::error(
                            $livewire, 
                            'Gagal menambahkan Bidang', 
                            'Terjadi kesalahan saat menambahkan Bidang. Silakan coba lagi'
                        );
                        $action->halt();
                    }
                })
                ->after(fn ($livewire, Bidang $record) => SweetAlert::success(
                    $livewire, 
                    'Bidang berhasil ditambahkan', 
                    "{$record->nama_bidang} sudah masuk ke daftar Bidang"
                )),
        ];
    }
}
