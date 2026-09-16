<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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


    public function logout(): void {
        Auth::logout();
    }
}