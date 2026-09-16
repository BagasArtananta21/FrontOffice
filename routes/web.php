<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\AdminTest;
use App\Http\Controllers\SuperAdmin\SuperAdminTest;
use Illuminate\Foundation\Http\Attributes\RedirectTo;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (){
    Route::get('/login', [LoginController::class, 'create']) -> name('login');
    Route::post('/login', [LoginController::class, 'store']) -> name('login.store');
    
});

Route::middleware('auth') -> group(function (){
    Route::post('/logout', [LoginController::class, 'destroy']) 
        -> name('logout');

    Route::middleware('role:super_admin')
        ->prefix('super-admin')
        ->name('super-admin.')
        ->group(function() {
            Route::get('/', [SuperAdminTest::class, 'superAdmin'])->name('dashboard');
        });
});
