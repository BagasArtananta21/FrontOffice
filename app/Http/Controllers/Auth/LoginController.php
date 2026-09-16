<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller{
    public function __construct (private AuthService $authService) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $this->authService->attempt($credentials['email'], $credentials['password'], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau Password Salah.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(
            filament()
                ->getPanel($request->user()->isSuperAdmin() ? 'super-admin' : 'admin')
                ->getUrl()
        );
    }
}