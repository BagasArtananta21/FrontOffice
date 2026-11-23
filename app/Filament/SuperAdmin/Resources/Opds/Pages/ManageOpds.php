<?php

namespace App\Filament\SuperAdmin\Resources\Opds\Pages;

use App\Filament\SuperAdmin\Resources\Opds\OpdResource;
use App\Models\Opd;
use App\Filament\Support\SweetAlert;
use App\Services\DisplayDeviceService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ManageOpds extends ManageRecords
{
    protected static string $resource = OpdResource::class;

    protected static ?string $title = 'Kelola OPD';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->successNotification(null)
                ->using(function (array $data, $livewire, CreateAction $action) {
                    try {
                        return DB::transaction(function () use ($data) {
                            $opd = new Opd();
                            $opd->forceFill($data)->save();
                            
                            app(DisplayDeviceService::class)->provisionFor($opd);

                            return $opd;
                        });
                    } catch (QueryException|RuntimeException $e) {
                        report($e);
                        SweetAlert::error(
                            $livewire,
                            'Gagal Menambahkan OPD',
                            'Terjadi kesalahan saat menambahkan OPD. Silakan coba lagi.'
                        );
                        $action->halt();
                    }
                })
                ->after(fn ($livewire, Opd $record) => SweetAlert::success(
                    $livewire,
                    'OPD berhasil ditambahkan',
                    "{$record->nama_opd} sudah masuk ke daftar OPD"
                ))
                ->createAnother(false),
        ];
    }
}
