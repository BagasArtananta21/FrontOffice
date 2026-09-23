<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Support\SweetAlert;
use App\Models\DisplayDevice;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class DisplayStatus extends Widget
{
    protected string $view = 'filament.admin.widgets.display-status';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 1;

    public function setDisplayForm(bool $tampilkan): void{
        $devices = $this->devices();

        if ($devices->isEmpty()) {
            SweetAlert::warning($this, 'Display Belum Terdaftar', 'Belum ada perangkat display aktif untuk OPD ini');
            return;
        }

        DisplayDevice::query()->where('aktif', true)->update(['tampilkan_form' => $tampilkan]);

        if (! $tampilkan) {
            SweetAlert::info($this, 'Display Kembali ke Layar Idle');
            return;
        }

        if (! $devices->contains(fn (DisplayDevice $device) => $device->isConnected())) {
            SweetAlert::warning($this, 'Display Tidak Terhubung', 'Form akan tampil begitu display terhubung kembali');
            return;
        }

        SweetAlert::success($this, 'Form Tamu Ditampilkan', 'Form tamu akan tampil di layar display dalam beberapa detik');
    }

    protected function devices(): Collection
    {
        return DisplayDevice::query()->where('aktif', true)->get();
    }

    protected function getViewData(): array
    {
        $devices = $this->devices();

        return [
            'terdaftar' => $devices->isNotEmpty(),
            'terhubung' => $devices->contains(fn (DisplayDevice $device) => $device->isConnected()),
            'menampilkanForm' => $devices->first()?->tampilkan_form === true,
        ];
    }
}
