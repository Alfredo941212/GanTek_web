<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SesionOrdenio;
use App\Services\ProduccionGrupoIAService;
use Illuminate\Http\Request;

class AnalisisProduccionGrupoController extends Controller
{
    public function analizar(
        Request $request,
        ProduccionGrupoIAService $iaService
    ) {
        $validated = $request->validate([
            'horizonte' => [
                'nullable',
                'integer',
                'in:7,30',
            ],
            'fecha_inicio' => [
                'nullable',
                'date',
            ],
            'fecha_fin' => [
                'nullable',
                'date',
                'after_or_equal:fecha_inicio',
            ],
        ]);

        $user = $request->user();

        $horizonte = (int) (
            $validated['horizonte'] ?? 7
        );

        $query = SesionOrdenio::query()
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'estado',
                'finalizado'
            )
            ->with([
                'grupo:id,nombre,orden',
            ])
            ->orderBy(
                'fecha'
            )
            ->orderBy(
                'grupo_ordenio_id'
            );

        if (!empty(
            $validated['fecha_inicio']
        )) {
            $query->whereDate(
                'fecha',
                '>=',
                $validated['fecha_inicio']
            );
        }

        if (!empty(
            $validated['fecha_fin']
        )) {
            $query->whereDate(
                'fecha',
                '<=',
                $validated['fecha_fin']
            );
        }

        $sesiones = $query->get();

        if ($sesiones->count() < 2) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No hay suficientes registros '
                    . 'para ejecutar el análisis.',
                'total_registros' =>
                    $sesiones->count(),
            ], 422);
        }

        $registros = $sesiones
            ->map(function (
                SesionOrdenio $sesion
            ) {
                return [
                    'grupo_id' =>
                        $sesion
                            ->grupo_ordenio_id,

                    'grupo' =>
                        $sesion->grupo?->nombre
                        ?? 'Grupo',

                    'fecha' =>
                        $sesion->fecha
                            ->format(
                                'Y-m-d'
                            ),

                    'litros_totales' =>
                        (float)
                        $sesion
                            ->litros_totales,

                    'cantidad_vacas' =>
                        (int)
                        $sesion
                            ->cantidad_vacas,

                    'duracion_minutos' =>
                        (int)
                        (
                            $sesion
                                ->duracion_minutos
                            ?? 0
                        ),
                ];
            })
            ->values()
            ->all();

        try {
            $resultado = $iaService->analizar(
                registros: $registros,
                horizonte: $horizonte,
            );

            return response()->json([
                'success' => true,
                'data' => $resultado,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'No fue posible ejecutar '
                    . 'el análisis de producción.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}