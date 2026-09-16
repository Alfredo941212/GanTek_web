<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VacunacionRequest;
use App\Models\Ganado;
use App\Models\Vacunacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VacunacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vacunaciones = Vacunacion::forUser($request->user())
            ->with([
                'ganado:id,arete_siniiga,nombre,lote_id',
                'vacuna:id,nombre,fabricante,dosis_recomendada,intervalo_dias,estado',
                'veterinario:id,nombre,cedula_profesional,estado',
            ])
            ->latest('fecha_aplicacion')
            ->get();

        return response()->json([
            'data' => $vacunaciones,
        ]);
    }

    public function store(VacunacionRequest $request): JsonResponse
    {
        $vacunacion = DB::transaction(function () use ($request): Vacunacion {
            $animal = Ganado::whereKey($request->integer('ganado_id'))
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('update', $animal);

            if (
                $animal->estado !== 'Activo'
                || $request->input('fecha_aplicacion') < $animal->fecha_ingreso->toDateString()
            ) {
                throw ValidationException::withMessages([
                    'ganado_id' => [
                        'El animal debe estar activo y la aplicación no puede preceder a su ingreso.',
                    ],
                ]);
            }

            return $animal->vacunaciones()->create(
                $request->safe()->except('ganado_id')
            );
        });

        return response()->json([
            'message' => 'Vacunación registrada correctamente.',
            'data' => $vacunacion->load([
                'ganado:id,arete_siniiga,nombre,lote_id',
                'vacuna:id,nombre,fabricante,dosis_recomendada,intervalo_dias,estado',
                'veterinario:id,nombre,cedula_profesional,estado',
            ]),
        ], 201);
    }

    public function show(
        Request $request,
        Vacunacion $vacunacion
    ): JsonResponse {
        Gate::authorize('view', $vacunacion);

        return response()->json([
            'data' => $vacunacion->load([
                'ganado:id,arete_siniiga,nombre,lote_id',
                'vacuna:id,nombre,fabricante,dosis_recomendada,intervalo_dias,estado',
                'veterinario:id,nombre,cedula_profesional,estado',
            ]),
        ]);
    }

    public function update(
        VacunacionRequest $request,
        Vacunacion $vacunacion
    ): JsonResponse {
        DB::transaction(function () use ($request, $vacunacion): void {
            $animal = Ganado::whereKey($vacunacion->ganado_id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('update', $animal);

            if (
                $request->input('fecha_aplicacion')
                < $animal->fecha_ingreso->toDateString()
            ) {
                throw ValidationException::withMessages([
                    'fecha_aplicacion' => [
                        'La aplicación no puede preceder al ingreso del animal.',
                    ],
                ]);
            }

            $vacunacion->update(
                $request->safe()->except('ganado_id')
            );
        });

        return response()->json([
            'message' => 'Vacunación actualizada correctamente.',
            'data' => $vacunacion->fresh()->load([
                'ganado:id,arete_siniiga,nombre,lote_id',
                'vacuna:id,nombre,fabricante,dosis_recomendada,intervalo_dias,estado',
                'veterinario:id,nombre,cedula_profesional,estado',
            ]),
        ]);
    }

    public function destroy(Vacunacion $vacunacion): JsonResponse
    {
        Gate::authorize('delete', $vacunacion);

        $vacunacion->delete();

        return response()->json([
            'message' => 'Vacunación eliminada correctamente.',
        ]);
    }
}