<?php

namespace App\Http\Controllers;

use App\Services\AnalisisPredictivoIAService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Ganado;
use App\Models\Tarea;
use Illuminate\Http\RedirectResponse;

class AnalisisIAWebController extends Controller
{
    public function index(
        Request $request,
        AnalisisPredictivoIAService $analisisIA
    ): View {
        $resultado = $analisisIA->analizar(
            $request->user()
        );

        $animales = collect($resultado['animales']);

        return view('ia.index', [
            'resultado' => $resultado,
            'animales' => $animales,
            'normales' => $animales
                ->where('estado_ia', 'normal')
                ->count(),
            'atencion' => $animales
                ->where('estado_ia', 'atencion')
                ->count(),
            'anomalias' => $animales
                ->where('estado_ia', 'anomalia')
                ->count(),
            'sinDatos' => $animales
                ->where('estado_ia', 'sin_datos_suficientes')
                ->count(),
        ]);
    }

    public function agregarAgenda(
        Request $request,
        Ganado $ganado
    ): RedirectResponse {
        $ganadoValido = Ganado::forUser($request->user())
            ->where('id', $ganado->id)
            ->firstOrFail();

        $titulo = "Revisar producción de vaca {$ganadoValido->arete_siniiga}";

        $tarea = Tarea::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'ganado_id' => $ganadoValido->id,
                'titulo' => $titulo,
                'fecha' => today()->toDateString(),
            ],
            [
                'lote_id' => $ganadoValido->lote_id,
                'descripcion' =>
                'Revisar la producción del animal debido a una anomalía detectada en el análisis.',
                'tipo' => 'produccion',
                'hora' => null,
                'prioridad' => 'alta',
                'estado' => 'pendiente',
                'recordatorio' => true,
                'creada_por_ia' => false,
            ]
        );

        if ($tarea->wasRecentlyCreated) {
            return redirect()
                ->route('ia.index')
                ->with(
                    'success',
                    'La tarea fue agregada correctamente a la agenda.'
                );
        }

        return redirect()
            ->route('ia.index')
            ->with(
                'warning',
                'Esta tarea ya está registrada en la agenda y no puede duplicarse.'
            );
    }
}
