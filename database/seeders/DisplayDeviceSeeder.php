<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Services\DisplayDeviceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class DisplayDeviceSeeder extends Seeder
{
    public function run(DisplayDeviceService $displays): void
    {
        $opds = Opd::query()
            ->whereDoesntHave('displayDevice', fn (Builder $query) => $query->withoutGlobalScope('opd'))
            ->get();

        foreach ($opds as $opd) {
            app()->instance('current_opd_id', $opd->id);

            try {
                $device = $displays->provisionFor($opd);
                $this->command?->warn("PIN display {$opd->kode_opd}: {$device->pin}");
            } finally {
                app()->forgetInstance('current_opd_id');
            }
        }
    }
}
