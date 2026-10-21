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
use App\Http\Controllers\Api\GrupoOrdenioController;
use App\Http\Controllers\Api\SesionOrdenioController;
use App\Http\Controllers\Api\AnalisisProduccionGrupoController;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');
Route::post('/register', [AuthController::class, 'register'])
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

    Route::apiResource('tareas', TareaController::class)
        ->except(['destroy'])
        ->names('api.tareas');

    Route::patch('/tareas/{tarea}/completar', [TareaController::class, 'completar'])
        ->name('api.tareas.completar');

    Route::patch('/tareas/{tarea}/cancelar', [TareaController::class, 'cancelar'])
        ->name('api.tareas.cancelar');


    Route::apiResource(
        'grupos-ordenio',
        GrupoOrdenioController::class
    )->parameters([
        'grupos-ordenio' => 'grupoOrdenio',
    ]);

    Route::post(
        'grupos-ordenio/{grupoOrdenio}/ganado',
        [GrupoOrdenioController::class, 'agregarGanado']
    );

    Route::delete(
        'grupos-ordenio/{grupoOrdenio}/ganado/{ganado}',
        [GrupoOrdenioController::class, 'quitarGanado']
    );

    Route::get(
        'sesiones-ordenio/hoy',
        [SesionOrdenioController::class, 'hoy']
    );

    Route::post(
        'sesiones-ordenio/iniciar',
        [SesionOrdenioController::class, 'iniciarJornada']
    );

    Route::patch(
        'sesiones-ordenio/{sesionOrdenio}/finalizar',
        [SesionOrdenioController::class, 'finalizar']
    );

    Route::get(
        'sesiones-ordenio/historial',
        [SesionOrdenioController::class, 'historial']
    );

    Route::post(
        'sesiones-ordenio/importar',
        [SesionOrdenioController::class, 'importar']
    );

    Route::get(
        '/ia/produccion-grupos',
        [AnalisisProduccionGrupoController::class, 'analizar']
    );
});
