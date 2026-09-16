<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Foundation\Http\Attributes\RedirectTo;

Route::middleware('guest')->group(function (){
    Route::get('/login', [LoginController::class, 'create']) -> name('login');
    Route::post('/login', [LoginController::class, 'store']) -> name('login.store'); 
});
