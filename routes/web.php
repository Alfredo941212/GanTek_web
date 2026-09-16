<?php

use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FincaController;
use App\Http\Controllers\GanadoController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\RegistroOrdenioController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VacunacionController;
use App\Http\Controllers\VacunaController;
use App\Http\Controllers\VeterinarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));

Route::middleware('guest')->group(function (): void {
    Route::get('/registro', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/registro', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register.process');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.process');
    Route::get('/auth/google', [SocialController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [SocialController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    Route::get('/auth/facebook', [SocialController::class, 'redirectToFacebook'])->name('auth.facebook');
    Route::get('/auth/facebook/callback', [SocialController::class, 'handleFacebookCallback'])->name('auth.facebook.callback');
    Route::get('/olvide-contrasena', [AuthController::class, 'showForgotPassword'])
        ->name('password.request');

    Route::post('/olvide-contrasena', [AuthController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('/restablecer-contrasena/{token}', [AuthController::class, 'showResetPassword'])
        ->name('password.reset');

    Route::post('/restablecer-contrasena', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('fincas', FincaController::class)->parameters(['fincas' => 'finca'])->except('show');
    Route::resource('lotes', LoteController::class)->parameters(['lotes' => 'lote'])->except('show');
    Route::resource('ganado', GanadoController::class)->parameters(['ganado' => 'ganado']);
    Route::resource('veterinarios', VeterinarioController::class)->parameters(['veterinarios' => 'veterinario'])->except('show');
    Route::resource('vacunas', VacunaController::class)->parameters(['vacunas' => 'vacuna'])->except('show');
    Route::resource('vacunaciones', VacunacionController::class)->parameters(['vacunaciones' => 'vacunacion'])->except('show');
    Route::resource('ordenios', RegistroOrdenioController::class)->parameters(['ordenios' => 'ordenio'])->except('show');
    Route::get('/produccion', [ReportController::class, 'index'])->name('produccion.index');
    Route::get('/reportes', [ReportController::class, 'index'])->name('reportes.index');
});
