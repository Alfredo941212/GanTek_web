<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Finca;
use App\Models\Lote;
use App\Services\ProduccionService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReporteProduccionController extends Controller
{
    public function index(
        Request $request,
        ProduccionService $produccion
    ): JsonResponse {
        $user = $request->user();

        $filtros = $request->validate([
            'periodo' => [
                'nullable',
                Rule::in([
                    'diario',
                    'semanal',
                    'mensual',
                    'personalizado',
                ]),
            ],

            'desde' => [
                'required_if:periodo,personalizado',
                'nullable',
                'date_format:Y-m-d',
            ],

            'hasta' => [
                'required_if:periodo,personalizado',
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:desde',
            ],

            'finca_id' => [
                'nullable',
                'integer',
                Rule::exists('fincas', 'id')
                    ->where('user_id', $user->id),
            ],

            'lote_id' => [
                'nullable',
                'integer',
                Rule::exists('lotes', 'id')
                    ->where(
                        fn (Builder $query) =>
                        $query->whereIn(
                            'finca_id',
                            Finca::forUser($user)->select('id')
                        )
                    ),
            ],

            'ganado_id' => [
                'nullable',
                'integer',
                Rule::exists('ganado', 'id')
                    ->where(
                        fn (Builder $query) =>
                        $query->whereIn(
                            'lote_id',
                            Lote::forUser($user)->select('id')
                        )
                    ),
            ],
        ]);

        $periodo = $filtros['periodo'] ?? 'semanal';

        if ($periodo !== 'personalizado') {
            $filtros['desde'] = match ($periodo) {
                'diario' => today()->toDateString(),

                'mensual' => today()
                    ->startOfMonth()
                    ->toDateString(),

                default => today()
                    ->startOfWeek()
                    ->toDateString(),
            };

            $filtros['hasta'] = today()->toDateString();
        }

        $resumen = $produccion->resumen($user, $filtros);

        return response()->json([
            'data' => [
                'periodo' => $periodo,

                'filtros' => [
                    'desde' => $filtros['desde'],
                    'hasta' => $filtros['hasta'],
                    'finca_id' => $filtros['finca_id'] ?? null,
                    'lote_id' => $filtros['lote_id'] ?? null,
                    'ganado_id' => $filtros['ganado_id'] ?? null,
                ],

                'indicadores' => [
                    'total_litros' => (float) $resumen['total'],
                    'promedio_diario' => (float) $resumen['promedio'],
                    'dias_con_registros' => (int) $resumen['dias'],
                ],

                'produccion_diaria' => $resumen['diaria'],

                'produccion_por_animal' => $resumen['animales'],

                'produccion_por_lote' => $resumen['lotes'],

                'produccion_por_finca' => $resumen['fincas'],
            ],
        ]);
    }
}