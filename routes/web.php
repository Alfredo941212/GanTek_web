<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CattleController;
use App\Http\Controllers\VaccineController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ReportController;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.process');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('ganado', CattleController::class);
    Route::resource('vacunas', VaccineController::class)->except(['show']);
    Route::resource('ventas', SaleController::class)->except(['show']);

    Route::get('/reportes', [ReportController::class, 'index'])->name('reportes.index');
});
