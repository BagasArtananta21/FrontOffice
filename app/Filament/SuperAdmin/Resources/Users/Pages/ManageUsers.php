<?php

namespace App\Filament\SuperAdmin\Resources\Users\Pages;

use App\Filament\SuperAdmin\Resources\Users\UserResource;
use App\Models\User;
use App\Filament\Support\SweetAlert;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->successNotification(null)
                ->using(function (array $data, $livewire, CreateAction $action) {
                    try {
                        $opd = new User();
                        $opd->forceFill($data)->save();

                        return $opd;
                    } catch (QueryException $e) {
                        report($e);
                        SweetAlert::error(
                            $livewire,
                            'Gagal Menambahkan Pengguna',
                            'Terjadi kesalahan saat menambahkan Pengguna. silahkan coba lagi'
                        );
                        $action->halt();
                    }
                })
                ->after(fn ($livewire, User $record) => SweetAlert::success(
                    $livewire,
                    'Pengguna berhasil ditambahkan',
                    "Data Pengguna {$record->nama_opd} sudah masuk ke daftar Pengguna"
                )),
        ];
    }
}
