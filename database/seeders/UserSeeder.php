<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $opd = Opd::where('kode_opd', 'DISKOMINFOSANTI')->firstOrFail();

        $superAdmin = User::firstOrNew(['email' => 'superadmin@example.com']);
        $superAdmin->fill([
            'name' => 'Super Admin',
            'password' => 'password',
        ]);
        $superAdmin->role = 'super_admin';
        $superAdmin->opd_id = null;
        $superAdmin->aktif = true;
        $superAdmin->save();

        $adminFo = User::firstOrNew(['email' => 'adminfo@example.com']);
        $adminFo->fill([
            'name' => 'Admin FO Diskominfosanti',
            'password' => 'password',
        ]);
        $adminFo->role = 'admin_fo';
        $adminFo->opd_id = $opd->id;
        $adminFo->aktif = true;
        $adminFo->save();
    }
}
