<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FincaController;
use App\Http\Controllers\Api\LoteController;
use App\Http\Controllers\Api\GanadoController;
use App\Http\Controllers\Api\RegistroOrdenioController;
use App\Http\Controllers\Api\VeterinarioController;
use App\Http\Controllers\Api\VacunaController;
use App\Http\Controllers\Api\VacunacionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReporteProduccionController;
use App\Http\Controllers\Api\AnalisisIAController;
use App\Http\Controllers\Api\TareaController;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:login');

Route::post('/auth/google', [AuthController::class, 'google'])
    ->middleware('throttle:login');

Route::post('/auth/facebook', [AuthController::class, 'facebook'])
    ->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('fincas', FincaController::class)
        ->names('api.fincas');

    Route::apiResource('lotes', LoteController::class)
        ->names('api.lotes');

    Route::apiResource('ganado', GanadoController::class)
        ->names('api.ganado');

    Route::apiResource('ordenios', RegistroOrdenioController::class)
        ->names('api.ordenios');

    Route::apiResource('veterinarios', VeterinarioController::class)
        ->names('api.veterinarios');

    Route::apiResource('vacunas', VacunaController::class)
        ->names('api.vacunas');

    Route::apiResource('vacunaciones', VacunacionController::class)
        ->parameters([
            'vacunaciones' => 'vacunacion',
        ])
        ->names('api.vacunaciones');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('api.dashboard');

    Route::get('/reportes/produccion', [ReporteProduccionController::class, 'index'])
        ->name('api.reportes.produccion');

    Route::get('/ia/produccion', [AnalisisIAController::class, 'produccion'])
        ->name('api.ia.produccion');

    Route::get('/ia/agenda', [AnalisisIAController::class, 'agenda'])
        ->name('api.ia.agenda');

    Route::apiResource('tareas', TareaController::class)
        ->except(['destroy'])
        ->names('api.tareas');

    Route::patch('/tareas/{tarea}/completar', [TareaController::class, 'completar'])
        ->name('api.tareas.completar');

    Route::patch('/tareas/{tarea}/cancelar', [TareaController::class, 'cancelar'])
        ->name('api.tareas.cancelar');

    Route::get(
        '/ia/prediccion-produccion',
        [AnalisisIAController::class, 'prediccionProduccion']
    )->name('api.ia.prediccion-produccion');


    Route::post(
        '/ia/{ganado}/agregar-agenda',
        [AnalisisIAController::class, 'agregarAgenda']
    )->name('api.ia.agregar-agenda');
});
