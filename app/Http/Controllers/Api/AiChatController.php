<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GanTekAgentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function __construct(
        private GanTekAgentService $agent
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'pregunta' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $pregunta = trim(
            $request->string('pregunta')->toString()
        );

        $preguntaLower = mb_strtolower($pregunta);

        /*
        |--------------------------------------------------------------------------
        | DETECCIÓN DE ARCHIVOS
        |--------------------------------------------------------------------------
        */

        $esPdf = str_contains(
            $preguntaLower,
            'pdf'
        );

        $esExcel =
            str_contains($preguntaLower, 'excel') ||
            str_contains($preguntaLower, 'xlsx');

        if ($esPdf || $esExcel) {
            $formato = $esPdf
                ? 'pdf'
                : 'excel';

            /*
            |--------------------------------------------------------------------------
            | MESES
            |--------------------------------------------------------------------------
            */

            $meses = [
                'enero' => 1,
                'febrero' => 2,
                'marzo' => 3,
                'abril' => 4,
                'mayo' => 5,
                'junio' => 6,
                'julio' => 7,
                'agosto' => 8,
                'septiembre' => 9,
                'setiembre' => 9,
                'octubre' => 10,
                'noviembre' => 11,
                'diciembre' => 12,
            ];

            /*
            |--------------------------------------------------------------------------
            | 1. RANGO PERSONALIZADO
            |--------------------------------------------------------------------------
            |
            | Ejemplos:
            |
            | del 17 al 23 de octubre
            | 17 al 23 de octubre
            | del 1 al 15 de octubre de 2026
            |
            */

            $patronRango =
                '/(?:del\s+)?'
                . '(\d{1,2})\s+'
                . '(?:al|a|-)\s+'
                . '(\d{1,2})\s+'
                . '(?:de\s+)?'
                . '(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)'
                . '(?:\s+de\s+(20\d{2}))?'
                . '/u';

            if (
                preg_match(
                    $patronRango,
                    $preguntaLower,
                    $matches
                )
            ) {
                $diaInicio = (int) $matches[1];
                $diaFin = (int) $matches[2];

                $nombreMes = $matches[3];
                $numeroMes = $meses[$nombreMes];

                $anio = isset($matches[4])
                    && $matches[4] !== ''
                    ? (int) $matches[4]
                    : now()->year;

                /*
                 * Validamos que las fechas existan.
                 */

                if (
                    !checkdate(
                        $numeroMes,
                        $diaInicio,
                        $anio
                    ) ||
                    !checkdate(
                        $numeroMes,
                        $diaFin,
                        $anio
                    )
                ) {
                    return response()->json([
                        'success' => false,
                        'tipo' => 'texto',
                        'message' =>
                            'El rango de fechas indicado no es válido.',
                    ], 422);
                }

                if ($diaInicio > $diaFin) {
                    return response()->json([
                        'success' => false,
                        'tipo' => 'texto',
                        'message' =>
                            'La fecha inicial no puede ser posterior a la fecha final.',
                    ], 422);
                }

                $desde = Carbon::create(
                    $anio,
                    $numeroMes,
                    $diaInicio
                )->toDateString();

                $hasta = Carbon::create(
                    $anio,
                    $numeroMes,
                    $diaFin
                )->toDateString();

                $url =
                    "/reportes/produccion/{$formato}"
                    . "?periodo=personalizado"
                    . "&desde={$desde}"
                    . "&hasta={$hasta}";

                return response()->json([
                    'success' => true,
                    'tipo' => 'archivo',
                    'formato' => $formato,
                    'periodo' => 'personalizado',
                    'desde' => $desde,
                    'hasta' => $hasta,
                    'respuesta' =>
                        "Tu reporte de producción del "
                        . "{$diaInicio} al {$diaFin} de "
                        . ucfirst($nombreMes)
                        . " de {$anio} está listo.",
                    'url' => $url,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 2. MES HISTÓRICO COMPLETO
            |--------------------------------------------------------------------------
            |
            | Ejemplos:
            |
            | reporte de octubre en PDF
            | reporte de octubre de 2026 en Excel
            |
            */

            $mesDetectado = null;
            $nombreMesDetectado = null;

            foreach (
                $meses as $nombreMes => $numeroMes
            ) {
                if (
                    str_contains(
                        $preguntaLower,
                        $nombreMes
                    )
                ) {
                    $mesDetectado = $numeroMes;
                    $nombreMesDetectado = $nombreMes;
                    break;
                }
            }

            if ($mesDetectado !== null) {
                $anio = now()->year;

                if (
                    preg_match(
                        '/\b(20\d{2})\b/',
                        $preguntaLower,
                        $matchesAnio
                    )
                ) {
                    $anio = (int) $matchesAnio[1];
                }

                $fechaBase = Carbon::create(
                    $anio,
                    $mesDetectado,
                    1
                );

                $desde = $fechaBase
                    ->copy()
                    ->startOfMonth()
                    ->toDateString();

                $hasta = $fechaBase
                    ->copy()
                    ->endOfMonth()
                    ->toDateString();

                $url =
                    "/reportes/produccion/{$formato}"
                    . "?periodo=personalizado"
                    . "&desde={$desde}"
                    . "&hasta={$hasta}";

                return response()->json([
                    'success' => true,
                    'tipo' => 'archivo',
                    'formato' => $formato,
                    'periodo' => 'personalizado',
                    'desde' => $desde,
                    'hasta' => $hasta,
                    'respuesta' =>
                        'Tu reporte de producción de '
                        . ucfirst($nombreMesDetectado)
                        . " de {$anio} está listo.",
                    'url' => $url,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 3. PERIODOS GENERALES
            |--------------------------------------------------------------------------
            |
            | hoy       -> diario
            | semana    -> semanal
            | mes       -> mensual
            |
            */

            $periodo = 'semanal';

            if (
                str_contains(
                    $preguntaLower,
                    'hoy'
                ) ||
                str_contains(
                    $preguntaLower,
                    'diario'
                ) ||
                str_contains(
                    $preguntaLower,
                    'diaria'
                ) ||
                str_contains(
                    $preguntaLower,
                    'día'
                ) ||
                str_contains(
                    $preguntaLower,
                    'dia'
                )
            ) {
                $periodo = 'diario';
            } elseif (
                str_contains(
                    $preguntaLower,
                    'mes'
                ) ||
                str_contains(
                    $preguntaLower,
                    'mensual'
                )
            ) {
                $periodo = 'mensual';
            } elseif (
                str_contains(
                    $preguntaLower,
                    'semana'
                ) ||
                str_contains(
                    $preguntaLower,
                    'semanal'
                )
            ) {
                $periodo = 'semanal';
            }

            $respuesta = match ($periodo) {
                'diario' =>
                    'Tu reporte diario de producción está listo.',

                'mensual' =>
                    'Tu reporte mensual de producción está listo.',

                default =>
                    'Tu reporte semanal de producción está listo.',
            };

            $url =
                "/reportes/produccion/{$formato}"
                . "?periodo={$periodo}";

            return response()->json([
                'success' => true,
                'tipo' => 'archivo',
                'formato' => $formato,
                'periodo' => $periodo,
                'respuesta' => $respuesta,
                'url' => $url,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CONSULTA NORMAL AL AGENTE
        |--------------------------------------------------------------------------
        */

        try {
            $respuesta = $this->agent->responder(
                $user->id,
                $pregunta
            );

            return response()->json([
                'success' => true,
                'tipo' => 'texto',
                'respuesta' => $respuesta,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'tipo' => 'texto',
                'message' =>
                    'No se pudo obtener una respuesta del asistente.',
            ], 500);
        }
    }
}