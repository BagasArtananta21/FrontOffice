<?php

namespace App\Filament\SuperAdmin\Resources\Opds\Pages;

use App\Filament\SuperAdmin\Resources\Opds\OpdResource;
use App\Models\Opd;
use App\Filament\Support\SweetAlert;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class ManageOpds extends ManageRecords
{
    protected static string $resource = OpdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->successNotification(null)
                ->using(function (array $data, $livewire, CreateAction $action) {
                    try {
                        $opd = new Opd();
                        $opd->forceFill($data)->save();

                        return $opd;
                    } catch (QueryException $e) {
                        report($e);
                        SweetAlert::error(
                            $livewire,
                            'Gagal Menambahkan OPD',
                            'Terjadi kesalahan saat menambahkan OPD. silahkan coba lagi'
                        );
                        $action->halt();
                    }
                })
                ->after(fn ($livewire, Opd $record) => SweetAlert::success(
                    $livewire,
                    'OPD berhasil ditambahkan',
                    "{$record->nama_opd} sudah masuk ke daftar OPD"
                )),
        ];
    }
}
