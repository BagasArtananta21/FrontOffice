<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthService
{
    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        return Auth::attempt([
            'email' => $email,
            'password' => $password,
            'aktif' => true,
            fn (Builder $query) => $query->where(function (Builder $query) {
                $query->where('role', 'super_admin')
                      ->orWhereHas('opd', fn (Builder $opd) => $opd->active());
            }),
        ], $remember);
    }


    public function panelUrlFor(User $user): string
    {
        return filament()
            ->getPanel($user->isSuperAdmin() ? 'super-admin' : 'admin')
            ->getUrl();
    }

    public function logout(): void {
        Auth::logout();
    }
}