<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Seeder;
use InvalidArgumentException;
use RuntimeException;

class UserSeeder extends Seeder
{
    public const MIN_PASSWORD_LENGTH = 12;

    public function run(): void
    {
        $superAdmin = User::firstOrNew(['email' => 'superadmin@example.com']);
        $superAdmin->fill(['name' => 'Super Admin']);

        if (app()->isLocal() || ! $superAdmin->exists) {
            $superAdmin->password = $this->superAdminPassword();
        }

        $superAdmin->role = 'super_admin';
        $superAdmin->opd_id = null;
        $superAdmin->aktif = true;
        $superAdmin->save();

        if (! app()->isLocal()) {
            return;
        }

        $opd = Opd::where('kode_opd', 'DISKOMINFOSANTI')->firstOrFail();

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

    private function superAdminPassword(): string
    {
        if (app()->isLocal()) {
            return 'password';
        }

        if ($this->command === null) {
            throw new RuntimeException('Seeding di luar lokal harus lewat artisan agar kata sandi super admin bisa ditanyakan.');
        }

        $password = (string) $this->command->secret('Kata sandi super admin (minimal '.self::MIN_PASSWORD_LENGTH.' karakter)');

        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException('Kata sandi super admin minimal '.self::MIN_PASSWORD_LENGTH.' karakter.');
        }

        return $password;
    }
}
