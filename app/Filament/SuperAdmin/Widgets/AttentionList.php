<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Filament\SuperAdmin\Resources\Opds\OpdResource;
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
                'message' => match (true) {
                    ! $device->isPaired() => "Display {$device->opd->kode_opd} belum dipasang",
                    $device->terakhir_aktif === null => "Display {$device->opd->kode_opd} belum pernah terhubung",
                    default => "Display {$device->opd->kode_opd} terakhir terhubung {$device->terakhir_aktif->diffForHumans()}",
                },
                'url' => OpdResource::getUrl(),
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
            'items' => $disconnected->concat($withoutAdmin),
        ];
    }
}