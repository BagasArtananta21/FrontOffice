<?php

namespace App\Filament\SuperAdmin\Resources\FormatNomorSurats\Pages;

use App\Models\FormatNomorSurat;
use App\Filament\SuperAdmin\Resources\FormatNomorSurats\FormatNomorSuratResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Filament\Support\Enums\Width;
use App\Filament\Support\SweetAlert;


class ManageFormatNomorSurats extends ManageRecords
{
    protected static string $resource = FormatNomorSuratResource::class;

    protected static ?string $title = 'Format Nomor Surat';

protected function getHeaderActions(): array
{
    return [
        CreateAction::make()
            ->label('Tambah Versi Format')
            ->modalHeading('Tambah Versi Format')
            ->modalSubmitActionLabel('Simpan')
            ->modalWidth(Width::FourExtraLarge)
            ->createAnother(false)
            ->successNotification(null)
            ->using(function (array $data, $livewire, CreateAction $action) {
                try {
                    $format = new FormatNomorSurat(Arr::except($data, ['opd_id']));
                    $format->forceFill([
                        'opd_id' => $data['opd_id'],
                        'dibuat_oleh' => Auth::id(),
                    ])->save();

                    return $format;
                } catch (QueryException $e) {
                    report($e);
                    SweetAlert::error(
                        $livewire,
                        'Gagal menyimpan format',
                        'Terjadi kesalahan saat menyimpan format. Silakan coba lagi.'
                    );
                    $action->halt();
                }
            })
            ->after(fn ($livewire, FormatNomorSurat $record) => SweetAlert::success(
                $livewire,
                'Format tersimpan',
                "Format {$record->opd->nama_opd} berlaku sejak {$record->berlaku_sejak->translatedFormat('d F Y')}."
            )),
    ];
}

}
