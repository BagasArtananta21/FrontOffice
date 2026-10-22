<?php

namespace App\Filament\Admin\Resources\Kunjungans\Pages;

use App\Filament\Admin\Resources\Kunjungans\KunjunganResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use App\Filament\Support\SweetAlert;
use App\Services\KunjunganService;
use Illuminate\Support\Facades\Auth;
use App\Filament\Admin\Widgets\GuestStats;
use App\Filament\Admin\Widgets\DisplayStatus;
use Filament\Actions\Action;
use App\Exceptions\DisplayUnavailableException;
use App\Models\Kunjungan;
use Illuminate\Database\QueryException;

class ManageKunjungans extends ManageRecords
{
    protected static string $resource = KunjunganResource::class;

    protected static ?string $title = 'Tamu Hari Ini';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manualInput')
                ->label('Input Tamu Manual')
                ->modalHeading('Input Tamu Manual')
                ->modalSubmitActionLabel('Lanjutkan')
                ->modalWidth(Width::SixExtraLarge)
                ->schema([
                    ...KunjunganResource::formComponents(),
                    KunjunganResource::signatureSection(),
                ])
                ->action(function (array $data, $livewire, Action $action) {
                    $service = app(KunjunganService::class);

                    if ($data['signature_method'] === KunjunganResource::SIGNATURE_DISPLAY) {
                        try {
                            $service->requestSignature($data, Auth::user());
                        } catch (DisplayUnavailableException) {
                            SweetAlert::warning(
                                $livewire,
                                'Display Tidak Tersedia',
                                'Display sedang dipakai atau tidak terhubung. Coba lagi sebentar, atau simpan tanpa tanda tangan.'
                            );
                            $action->halt();
                        }

                        SweetAlert::success(
                            $livewire,
                            'Dikirim ke Display',
                            "Minta {$data['nama_tamu']} memeriksa data dan tanda tangan di display."
                        );

                        return;
                    }

                    try {
                        $kunjungan = $service->recordWithoutSignature($data, Auth::user(), $data['alasan_tanpa_ttd']);
                    } catch (QueryException $e) {
                        report($e);
                        SweetAlert::error(
                            $livewire,
                            'Gagal menambahkan Tamu',
                            'Terjadi kesalahan saat menambahkan tamu. Silahkan coba lagi.'
                        );
                        $action->halt();
                    }

                    SweetAlert::info(
                        $livewire,
                        'Kunjungan Tercatat',
                        "Data {$kunjungan->nama_tamu} tercatat tanpa tanda tangan · ".(Kunjungan::NO_SIGNATURE_REASONS[$kunjungan->alasan_tanpa_ttd])
                    );
                }),

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
