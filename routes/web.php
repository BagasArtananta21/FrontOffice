<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Foundation\Http\Attributes\RedirectTo;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $user = $request->user();

    if (! $user) {
        return redirect()->route('login');
    }

    return redirect()->to(app(AuthService::class)->panelUrlFor($user));
});


Route::middleware('guest')->group(function (){
    Route::get('/login', [LoginController::class, 'create']) -> name('login');
    Route::post('/login', [LoginController::class, 'store']) -> name('login.store'); 
});
