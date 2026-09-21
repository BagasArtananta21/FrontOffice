<?php

namespace Database\Seeders;

use App\Models\DisplayDevice;
use App\Models\Opd;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DisplayDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $opd = Opd::where('kode_opd', 'DISKOMINFOSANTI')->firstOrFail();

        app()->instance('current_opd_id', $opd->id);

        $token = Str::random(40);

        DisplayDevice::firstOrNew(['nama' => 'Pc Lobby'])
            ->forceFill([
                'token_hash' => hash('sha256', $token),
                'aktif' => true,
            ]) ->save();

        $this->command->warn('Token display: '.$token);
        app()->forgetInstance('current_opd_id');
    }
}
