<?php

use App\Http\Controllers\DisplayController;
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

    return redirect()->to(app(AuthService::class)->panelUrlFor($user)) ;
}) -> middleware('not_display');


Route::middleware(['not_display', 'guest'])->group(function (){
    Route::get('/login', [LoginController::class, 'create']) -> name('login');
    Route::post('/login', [LoginController::class, 'store']) -> name('login.store');
});

Route::get('/display/pair', [DisplayController::class, 'pairForm'])
    ->name('display.pair');

Route::post('/display/pair', [DisplayController::class, 'pair'])
    ->middleware('throttle:10,1')
    ->name('display.pair.store');

Route::middleware('display')
    ->prefix('display')
    ->name('display.')
    ->group(function(){
        Route::get('/', [DisplayController::class, 'show'])->name('show');
        Route::get('/status', [DisplayController::class, 'status'])->name('status');
        Route::get('/unpair', [DisplayController::class, 'unpair'])->name('unpair');
        
        Route::post('/kunjungan', [DisplayController::class, 'storeVisit'])
            ->middleware('throttle:display-submit')
            ->name('kunjungan.store');
        
        Route::get('/terima-kasih', [DisplayController::class, 'confirmation'])
            ->name('confirmation');
    });