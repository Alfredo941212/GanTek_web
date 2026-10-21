<?php

namespace App\Http\Controllers;

use App\Models\SesionOrdenio;
use App\Services\ProduccionGrupoIAService;
use Illuminate\Http\Request;

class IaProduccionWebController extends Controller
{
    public function index(
        Request $request,
        ProduccionGrupoIAService $iaService
    ) {
        set_time_limit(120);

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
            'grupo_id' => [
                'nullable',
                'integer',
            ],
        ]);

        $user = $request->user();

        $horizonte = (int) (
            $validated['horizonte'] ?? 7
        );

        $query = SesionOrdenio::query()
            ->where('user_id', $user->id)
            ->where('estado', 'finalizado')
            ->with([
                'grupo:id,nombre,orden',
            ])
            ->orderBy('fecha')
            ->orderBy('grupo_ordenio_id');

        if (!empty($validated['fecha_inicio'])) {
            $query->whereDate(
                'fecha',
                '>=',
                $validated['fecha_inicio']
            );
        }

        if (!empty($validated['fecha_fin'])) {
            $query->whereDate(
                'fecha',
                '<=',
                $validated['fecha_fin']
            );
        }

        if (!empty($validated['grupo_id'])) {
            $query->where(
                'grupo_ordenio_id',
                $validated['grupo_id']
            );
        }

        $sesiones = $query->get();

        $resultado = null;
        $error = null;

        if ($sesiones->count() >= 2) {
            $registros = $sesiones
                ->map(function (SesionOrdenio $sesion) {
                    return [
                        'grupo_id' =>
                            $sesion->grupo_ordenio_id,

                        'grupo' =>
                            $sesion->grupo?->nombre
                            ?? 'Grupo',

                        'fecha' =>
                            $sesion->fecha->format(
                                'Y-m-d'
                            ),

                        'litros_totales' =>
                            (float) $sesion
                                ->litros_totales,

                        'cantidad_vacas' =>
                            (int) $sesion
                                ->cantidad_vacas,

                        'duracion_minutos' =>
                            (int) (
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
            } catch (\Throwable $e) {
                report($e);

                $error =
                    'No fue posible ejecutar '
                    . 'el análisis de producción.';
            }
        } else {
            $error =
                'No hay suficientes registros '
                . 'para ejecutar el análisis.';
        }

        $gruposDisponibles = SesionOrdenio::query()
            ->where('user_id', $user->id)
            ->where('estado', 'finalizado')
            ->with([
                'grupo:id,nombre,orden',
            ])
            ->get()
            ->pluck('grupo')
            ->filter()
            ->unique('id')
            ->sortBy('orden')
            ->values();

        return view(
            'ia.index',
            [
                'resultado' => $resultado,
                'error' => $error,
                'horizonte' => $horizonte,
                'fechaInicio' =>
                    $validated['fecha_inicio']
                    ?? null,
                'fechaFin' =>
                    $validated['fecha_fin']
                    ?? null,
                'grupoSeleccionado' =>
                    $validated['grupo_id']
                    ?? null,
                'gruposDisponibles' =>
                    $gruposDisponibles,
            ]
        );
    }
}