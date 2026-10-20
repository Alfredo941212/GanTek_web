
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
use App\Http\Controllers\TareaController;
use App\Http\Controllers\AnalisisIAWebController;
use App\Http\Controllers\OrdenioWebController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));

Route::middleware('guest')->group(function (): void {

    Route::get('/registro', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/registro', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register.process');

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.process');

    // Autenticación con Google
    Route::get('/auth/google', [SocialController::class, 'redirectToGoogle'])
        ->name('auth.google');

    Route::get('/auth/google/callback', [SocialController::class, 'handleGoogleCallback'])
        ->name('auth.google.callback');

    // Autenticación con Facebook
    Route::get('/auth/facebook', [SocialController::class, 'redirectToFacebook'])
        ->name('auth.facebook');

    Route::get('/auth/facebook/callback', [SocialController::class, 'handleFacebookCallback'])
        ->name('auth.facebook.callback');

    // Recuperación de contraseña
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

    Route::get('/ordenios', [OrdenioWebController::class, 'index'])
        ->name('ordenios.index');
    
    Route::get('/jornadas-ordenio', [OrdenioWebController::class, 'index'])
    ->name('jornadas-ordenio.index');
});

// Rutas para usuarios autenticados
Route::middleware('auth')->group(function (): void {

    // Cerrar sesión
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Perfil del usuario
    Route::get('/perfil', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/perfil', [ProfileController::class, 'update'])
        ->name('profile.update');

    // Agenda
    Route::get('/agenda', [TareaController::class, 'index'])
        ->name('agenda.index');

    Route::post('/agenda', [TareaController::class, 'store'])
        ->name('agenda.store');

    Route::patch('/agenda/{tarea}/completar', [TareaController::class, 'completar'])
        ->name('agenda.completar');

    Route::patch('/agenda/{tarea}/cancelar', [TareaController::class, 'cancelar'])
        ->name('agenda.cancelar');

    // Fincas
    Route::resource('fincas', FincaController::class)
        ->parameters(['fincas' => 'finca'])
        ->except('show');

    // Lotes
    Route::resource('lotes', LoteController::class)
        ->parameters(['lotes' => 'lote'])
        ->except('show');

    // Ganado
    Route::resource('ganado', GanadoController::class)
        ->parameters(['ganado' => 'ganado']);

    // Veterinarios
    Route::resource('veterinarios', VeterinarioController::class)
        ->parameters(['veterinarios' => 'veterinario'])
        ->except('show');

    // Vacunas
    Route::resource('vacunas', VacunaController::class)
        ->parameters(['vacunas' => 'vacuna'])
        ->except('show');

    // Vacunaciones
    Route::resource('vacunaciones', VacunacionController::class)
        ->parameters(['vacunaciones' => 'vacunacion'])
        ->except('show');

    // Ordeños
    Route::resource('ordenios', RegistroOrdenioController::class)
        ->parameters(['ordenios' => 'ordenio'])
        ->except('show');

    // Producción y reportes
    Route::get('/produccion', [ReportController::class, 'index'])
        ->name('produccion.index');

    Route::get('/reportes', [ReportController::class, 'index'])
        ->name('reportes.index');

    // Análisis inteligente
    Route::get('/analisis-ia', [AnalisisIAWebController::class, 'index'])
        ->name('ia.index');

    Route::post(
        '/analisis-ia/{ganado}/agregar-agenda',
        [AnalisisIAWebController::class, 'agregarAgenda']
    )->name('ia.agregar-agenda');
});
