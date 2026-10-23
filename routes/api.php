
<?php

use App\Http\Controllers\Api\AnalisisIAController;
use App\Http\Controllers\Api\AnalisisProduccionGrupoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FincaController;
use App\Http\Controllers\Api\GanadoController;
use App\Http\Controllers\Api\GrupoOrdenioController;
use App\Http\Controllers\Api\LoteController;
use App\Http\Controllers\Api\RegistroOrdenioController;
use App\Http\Controllers\Api\ReporteProduccionController;
use App\Http\Controllers\Api\SesionOrdenioController;
use App\Http\Controllers\Api\TareaController;
use App\Http\Controllers\Api\UbicacionController;
use App\Http\Controllers\Api\VacunacionController;
use App\Http\Controllers\Api\VacunaController;
use App\Http\Controllers\Api\VeterinarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catálogos geográficos
|--------------------------------------------------------------------------
*/

Route::prefix('ubicaciones')
    ->name('api.ubicaciones.')
    ->middleware('throttle:60,1')
    ->group(function (): void {

        Route::get('estados', [UbicacionController::class, 'estados'])
            ->name('estados');

        Route::get(
            'estados/{clave}/municipios',
            [UbicacionController::class, 'municipios']
        )->where('clave', '[0-9]{2}')
            ->name('municipios');

        Route::get(
            'municipios/{clave}/localidades',
            [UbicacionController::class, 'localidades']
        )->where('clave', '[0-9]{5}')
            ->name('localidades');
    });

/*
|--------------------------------------------------------------------------
| Autenticación
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:login');

Route::post('/auth/google', [AuthController::class, 'google'])
    ->middleware('throttle:login');

Route::post('/auth/facebook', [AuthController::class, 'facebook'])
    ->middleware('throttle:login');

/*
|--------------------------------------------------------------------------
| Rutas protegidas
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // Fincas
    Route::apiResource('fincas', FincaController::class)
        ->names('api.fincas');

    // Lotes
    Route::apiResource('lotes', LoteController::class)
        ->names('api.lotes');

    // Ganado
    Route::apiResource('ganado', GanadoController::class)
        ->names('api.ganado');

    // Ordeños
    Route::apiResource('ordenios', RegistroOrdenioController::class)
        ->names('api.ordenios');

    // Veterinarios
    Route::apiResource('veterinarios', VeterinarioController::class)
        ->names('api.veterinarios');

    // Vacunas
    Route::apiResource('vacunas', VacunaController::class)
        ->names('api.vacunas');

    // Vacunaciones
    Route::apiResource('vacunaciones', VacunacionController::class)
        ->parameters([
            'vacunaciones' => 'vacunacion',
        ])
        ->names('api.vacunaciones');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('api.dashboard');

    // Reportes
    Route::get('/reportes/produccion', [ReporteProduccionController::class, 'index'])
        ->name('api.reportes.produccion');

    /*
    |--------------------------------------------------------------------------
    | Agenda y tareas
    |--------------------------------------------------------------------------
    */

    Route::apiResource('tareas', TareaController::class)
        ->except(['destroy'])
        ->names('api.tareas');

    Route::patch('/tareas/{tarea}/completar', [TareaController::class, 'completar'])
        ->name('api.tareas.completar');

    Route::patch('/tareas/{tarea}/cancelar', [TareaController::class, 'cancelar'])
        ->name('api.tareas.cancelar');

    /*
    |--------------------------------------------------------------------------
    | Inteligencia artificial
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/ia/prediccion-produccion',
        [AnalisisIAController::class, 'prediccionProduccion']
    )->name('api.ia.prediccion-produccion');

    Route::post(
        '/ia/{ganado}/agregar-agenda',
        [AnalisisIAController::class, 'agregarAgenda']
    )->name('api.ia.agregar-agenda');

    Route::get(
        '/ia/produccion-grupos',
        [AnalisisProduccionGrupoController::class, 'analizar']
    );

    /*
    |--------------------------------------------------------------------------
    | Grupos de ordeño
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Sesiones de ordeño
    |--------------------------------------------------------------------------
    */

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
});

