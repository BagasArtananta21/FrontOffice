<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Filament\SuperAdmin\Resources\DisplayDevices\DisplayDeviceResource;
use App\Filament\SuperAdmin\Resources\FormatNomorSurats\FormatNomorSuratResource;
use App\Models\DisplayDevice;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Opd;
use App\Filament\SuperAdmin\Resources\Users\UserResource;

class AttentionList extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.super-admin.widgets.attention-list';

    protected function getViewData(): array
    {
        $disconnected = DisplayDevice::query()
            ->with('opd')
            ->where('aktif', true)
            ->whereHas('opd', fn (Builder $query) => $query->active())
            ->where(fn (Builder $query) => $query
                ->whereNull('terakhir_aktif')
                ->orWhere('terakhir_aktif', '<', now()->subDay()))
            ->get()
            ->map(fn (DisplayDevice $device) => [
                'message' => $device->terakhir_aktif
                    ? "Display {$device->nama} ({$device->opd->kode_opd}) terakhir terhubung {$device->terakhir_aktif->diffForHumans()}"
                    : "Display {$device->nama} ({$device->opd->kode_opd}) belum pernah terhubung",
                'url' => DisplayDeviceResource::getUrl(),
            ]);
        
        $withoutFormat = Opd::active()
            ->whereDoesntHave('formatNomorSurat', fn (Builder $query) => $query->where('berlaku_sejak', '<=', today()))
            ->orderBy('nama_opd')
            ->get()
            ->map(fn (Opd $opd) => [
                'message' => "{$opd->nama_opd} belum punya format nomor surat yang berlaku",
                'url' => FormatNomorSuratResource::getUrl(),
            ]);
        
        $withoutAdmin = Opd::active()
            ->whereDoesntHave('users', fn (Builder $query) => $query->where('role', 'admin_fo')->where('aktif', true))
            ->orderBy('nama_opd')
            ->get()
            ->map(fn (Opd $opd) => [
                'message' => "{$opd->nama_opd} belum punya admin FO yang aktif",
                'url' => UserResource::getUrl(),
            ]);
        
        return [
            'items' => $disconnected->concat($withoutFormat)->concat($withoutAdmin),
        ];
    }
}