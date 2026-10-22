<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Support\SweetAlert;
use App\Models\DisplayDevice;
use App\Models\Kunjungan;
use App\Services\KunjunganService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DisplayStatus extends Widget
{
    protected string $view = 'filament.admin.widgets.display-status';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 1;

    public function setDisplayForm(bool $tampilkan): void{
        $devices = $this->devices();

        if ($devices->isEmpty()) {
            SweetAlert::warning(
                $this, 
                'Display Belum Terdaftar', 
                'Belum ada perangkat display aktif untuk OPD ini'
            );
            return;
        }

        if ($devices->contains(fn (DisplayDevice $device) => $device->kunjungan_menunggu_id !== null)) {
            SweetAlert::warning(
                $this,
                'Menunggu Tanda Tangan',
                'Batalkan permintaan tanda tangan dulu sebelum mengubah tampilan display.'
            );
            return;
        }

        DisplayDevice::query()->where('aktif', true)->update(
            $tampilkan
                ? ['tampilkan_form' => true, 'ditampilkan_oleh' => Auth::id()]
                : ['tampilkan_form' => false],
        );


        if (! $tampilkan) {
            SweetAlert::info($this, 'Display Kembali ke Layar Idle');
            return;
        }

        if (! $devices->contains(fn (DisplayDevice $device) => $device->isConnected())) {
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
    }

    public function cancelSignatureRequest(): void
    {
        app(KunjunganService::class)->cancelSignatureRequest();

        SweetAlert::info(
            $this,
            'Permintaan Tanda Tangan Dibatalkan',
            'Kunjungan tetap tercatat tanpa tanda tangan. Display kembali ke layar idle.'
        );
    }

    protected function devices(): Collection
    {
        return DisplayDevice::query()->where('aktif', true)->get();
    }

    protected function getViewData(): array
    {
        $devices = $this->devices();
        $waitingDevice = $devices->first(fn (DisplayDevice $device) => $device->kunjungan_menunggu_id !== null);

        return [
            'terdaftar' => $devices->isNotEmpty(),
            'terhubung' => $devices->contains(fn (DisplayDevice $device) => $device->isConnected()),
            'menampilkanForm' => $devices->first()?->tampilkan_form === true,
            'menungguNamaTamu' => $waitingDevice?->kunjunganMenunggu?->nama_tamu,
        ];
    }
}
