<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalisisProduccionIAService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\AgendaIAService;
use App\Services\AnalisisPredictivoIAService;
use App\Models\Ganado;
use App\Models\Tarea;

class AnalisisIAController extends Controller
{
    public function produccion(
        Request $request,
        AnalisisProduccionIAService $analisisIA
    ): JsonResponse {
        $resultado = $analisisIA->analizarProduccion(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data' => $resultado,
        ]);
    }


    public function agenda(
        Request $request,
        AgendaIAService $agendaIA
    ): JsonResponse {
        $user = $request->user();

        $generadas = $agendaIA->generarTareas($user);

        $agenda = $agendaIA->agendaHoy($user);

        $agendaIA->actualizarTareasVencidas($user);

        return response()->json([
            'success' => true,
            'data' => [
                'generacion' => $generadas,
                'agenda_hoy' => $agenda,
            ],
        ]);
    }

    public function prediccionProduccion(
        Request $request,
        AnalisisPredictivoIAService $analisisPredictivo
    ): JsonResponse {
        $resultado = $analisisPredictivo->analizar(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data' => $resultado,
        ]);
    }

    public function agregarAgenda(
        Request $request,
        Ganado $ganado
    ): JsonResponse {
        $ganadoValido = Ganado::forUser($request->user())
            ->where('id', $ganado->id)
            ->firstOrFail();

        $tarea = Tarea::where('user_id', $request->user()->id)
            ->where('ganado_id', $ganadoValido->id)
            ->where(
                'titulo',
                'Revisar producción de vaca '
                    . $ganadoValido->arete_siniiga
            )
            ->latest('id')
            ->first();

        if ($tarea) {
            if (in_array(
                $tarea->estado,
                ['cancelada', 'vencida'],
                true
            )) {
                $tarea->update([
                    'fecha' => today()->toDateString(),
                    'estado' => 'pendiente',
                    'prioridad' => 'alta',
                    'recordatorio' => true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' =>
                    'La tarea fue reactivada en la agenda.',
                    'data' => $tarea->fresh(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' =>
                'La tarea ya está registrada en la agenda.',
                'data' => $tarea,
            ]);
        }

        $tarea = Tarea::create([
            'user_id' => $request->user()->id,
            'ganado_id' => $ganadoValido->id,
            'titulo' =>
            'Revisar producción de vaca '
                . $ganadoValido->arete_siniiga,
            'descripcion' =>
            'Revisar la producción detectada como anómala por GanTek IA.',
            'tipo' => 'produccion',
            'fecha' => today()->toDateString(),
            'prioridad' => 'alta',
            'estado' => 'pendiente',
            'recordatorio' => true,
            'creada_por_ia' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' =>
            'La tarea fue agregada a la agenda.',
            'data' => $tarea,
        ]);

        return response()->json([
            'success' => true,
            'message' => $tarea->wasRecentlyCreated
                ? 'La tarea fue agregada a la agenda.'
                : 'La tarea ya está registrada en la agenda.',
            'data' => $tarea,
        ]);
    }
}
