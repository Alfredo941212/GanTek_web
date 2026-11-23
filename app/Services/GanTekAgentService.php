<?php

namespace App\Services;

use App\Models\SesionOrdenio;
use App\Models\User;
use App\Services\AlertaService;
use Carbon\Carbon;
use App\Models\Tarea;
use App\Models\Ganado;

class GanTekAgentService
{
    public function __construct(
        private ClaudeService $claudeService
    ) {}

    public function analizarProduccionSemanal(
        int $userId
    ): string {
        $ultimaFecha = SesionOrdenio::query()
            ->where('user_id', $userId)
            ->where('estado', 'finalizado')
            ->max('fecha');

        if (!$ultimaFecha) {
            return 'No hay registros de producción disponibles para analizar.';
        }

        $hoy = Carbon::parse($ultimaFecha);

        $inicioActual = $hoy
            ->copy()
            ->subDays(6);

        $finAnterior = $inicioActual
            ->copy()
            ->subDay();

        $inicioAnterior = $finAnterior
            ->copy()
            ->subDays(6);

        $sesionesActuales = SesionOrdenio::query()
            ->where('user_id', $userId)
            ->where('estado', 'finalizado')
            ->whereBetween(
                'fecha',
                [
                    $inicioActual->toDateString(),
                    $hoy->toDateString(),
                ]
            )
            ->with([
                'grupo:id,nombre,orden',
            ])
            ->get();

        $sesionesAnteriores = SesionOrdenio::query()
            ->where('user_id', $userId)
            ->where('estado', 'finalizado')
            ->whereBetween(
                'fecha',
                [
                    $inicioAnterior->toDateString(),
                    $finAnterior->toDateString(),
                ]
            )
            ->get();

        $totalActual = (float) $sesionesActuales
            ->sum('litros_totales');

        $totalAnterior = (float) $sesionesAnteriores
            ->sum('litros_totales');

        $variacion = null;

        if ($totalAnterior > 0) {
            $variacion = (
                ($totalActual - $totalAnterior)
                / $totalAnterior
            ) * 100;
        }

        $grupos = $sesionesActuales
            ->groupBy('grupo_ordenio_id')
            ->map(function ($sesiones) {
                $primera = $sesiones->first();

                return [
                    'grupo' =>
                    $primera->grupo?->nombre
                        ?? 'Grupo',

                    'litros' =>
                    round(
                        (float) $sesiones
                            ->sum('litros_totales'),
                        2
                    ),

                    'sesiones' =>
                    $sesiones->count(),
                ];
            })
            ->values()
            ->all();

        $datos = [
            'periodo_actual' => [
                'inicio' =>
                $inicioActual->toDateString(),

                'fin' =>
                $hoy->toDateString(),

                'litros_totales' =>
                round($totalActual, 2),

                'sesiones' =>
                $sesionesActuales->count(),
            ],

            'periodo_anterior' => [
                'inicio' =>
                $inicioAnterior->toDateString(),

                'fin' =>
                $finAnterior->toDateString(),

                'litros_totales' =>
                round($totalAnterior, 2),

                'sesiones' =>
                $sesionesAnteriores->count(),
            ],

            'variacion_porcentaje' =>
            $variacion !== null
                ? round($variacion, 2)
                : null,

            'grupos' => $grupos,
        ];

        $json = json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );

        $prompt = <<<PROMPT
Eres el asistente inteligente de GanTek,
un sistema para gestión de ganado lechero.

Analiza únicamente los datos proporcionados.
No inventes información.
No diagnostiques enfermedades.
Si faltan datos, indícalo claramente.

Datos reales del usuario:

$json

Responde en español de forma breve y clara.

Explica:
1. Cuántos litros se produjeron en los últimos 7 días.
2. Cómo se compara con los 7 días anteriores.
3. Qué grupo tuvo mayor producción.
4. Si existe alguna disminución relevante.
5. Una recomendación general basada únicamente
   en los datos disponibles.

No afirmes causas de una disminución si los datos
no permiten conocerlas.

No hagas comentarios sobre nombres de grupos,
usuarios o registros salvo que sean necesarios
para responder la pregunta.
PROMPT;

        return $this->claudeService->ask(
            $prompt
        );
    }

    public function consultarVacunasProximas(
        int $userId
    ): string {
        $user = User::find($userId);

        if (!$user) {
            return 'No se encontró el usuario.';
        }

        $alertaService = app(AlertaService::class);

        $proximas = $alertaService
            ->proximas($user)
            ->with([
                'ganado:id,arete_siniiga',
                'vacuna:id,nombre',
                'veterinario:id,nombre',
            ])
            ->orderBy('proxima_aplicacion')
            ->get();

        $vencidas = $alertaService
            ->vencidas($user)
            ->with([
                'ganado:id,arete_siniiga',
                'vacuna:id,nombre',
            ])
            ->orderBy('proxima_aplicacion')
            ->get();

        if ($proximas->isEmpty() && $vencidas->isEmpty()) {
            return 'No tienes vacunas próximas ni vencidas registradas.';
        }

        $datosProximas = $proximas
            ->map(function ($vacunacion) {
                return [
                    'arete' =>
                    $vacunacion->ganado?->arete_siniiga
                        ?? 'Sin arete',

                    'vacuna' =>
                    $vacunacion->vacuna?->nombre
                        ?? 'Vacuna no identificada',

                    'fecha' =>
                    $vacunacion->proxima_aplicacion
                        ?->toDateString(),

                    'veterinario' =>
                    $vacunacion->veterinario?->nombre
                        ?? 'No asignado',
                ];
            })
            ->values()
            ->all();

        $datosVencidas = $vencidas
            ->map(function ($vacunacion) {
                return [
                    'arete' =>
                    $vacunacion->ganado?->arete_siniiga
                        ?? 'Sin arete',

                    'vacuna' =>
                    $vacunacion->vacuna?->nombre
                        ?? 'Vacuna no identificada',

                    'fecha' =>
                    $vacunacion->proxima_aplicacion
                        ?->toDateString(),
                ];
            })
            ->values()
            ->all();

        $datos = [
            'vacunas_proximas' => $datosProximas,
            'vacunas_vencidas' => $datosVencidas,
            'total_proximas' => count($datosProximas),
            'total_vencidas' => count($datosVencidas),
        ];

        $json = json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );

        $prompt = <<<PROMPT
Eres el asistente inteligente de GanTek,
un sistema para gestión de ganado lechero.

Analiza únicamente los datos proporcionados.
No inventes información.
No diagnostiques enfermedades.
No recomiendes medicamentos ni tratamientos.

Datos reales de vacunación del usuario:

$json

Responde en español de forma breve y clara.

Explica:
1. Cuántas vacunas próximas existen.
2. Qué animales tienen vacunas próximas.
3. La fecha programada.
4. Cuántas vacunas están vencidas.
5. Qué animales tienen vacunas vencidas.

Si no existen registros en alguna categoría,
indícalo claramente.

No afirmes que una vacuna fue aplicada si únicamente
existe una fecha de próxima aplicación.
PROMPT;

        return $this->claudeService->ask($prompt);
    }
    public function consultarTareas(
        int $userId
    ): string {
        $hoy = today()->toDateString();

        $pendientes = Tarea::query()
            ->where('user_id', $userId)
            ->where('estado', 'pendiente')
            ->whereDate('fecha', '>=', $hoy)
            ->with([
                'ganado:id,arete_siniiga',
                'lote:id,nombre',
            ])
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get();

        $vencidas = Tarea::query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($hoy) {
                $query
                    ->where('estado', 'vencida')
                    ->orWhere(function ($subquery) use ($hoy) {
                        $subquery
                            ->where('estado', 'pendiente')
                            ->whereDate('fecha', '<', $hoy);
                    });
            })
            ->with([
                'ganado:id,arete_siniiga',
                'lote:id,nombre',
            ])
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get();

        if ($pendientes->isEmpty() && $vencidas->isEmpty()) {
            return 'No tienes tareas pendientes ni vencidas.';
        }

        $datosPendientes = $pendientes
            ->map(function ($tarea) {
                return [
                    'titulo' => $tarea->titulo,
                    'descripcion' => $tarea->descripcion,
                    'tipo' => $tarea->tipo,
                    'fecha' => $tarea->fecha?->format('Y-m-d'),
                    'hora' => $tarea->hora,
                    'prioridad' => $tarea->prioridad,

                    'ganado' =>
                    $tarea->ganado?->arete_siniiga,

                    'lote' =>
                    $tarea->lote?->nombre,
                ];
            })
            ->values()
            ->all();

        $datosVencidas = $vencidas
            ->map(function ($tarea) {
                return [
                    'titulo' => $tarea->titulo,
                    'descripcion' => $tarea->descripcion,
                    'tipo' => $tarea->tipo,
                    'fecha' => $tarea->fecha?->format('Y-m-d'),
                    'hora' => $tarea->hora,
                    'prioridad' => $tarea->prioridad,

                    'ganado' =>
                    $tarea->ganado?->arete_siniiga,

                    'lote' =>
                    $tarea->lote?->nombre,
                ];
            })
            ->values()
            ->all();

        $datos = [
            'fecha_actual' => $hoy,
            'total_pendientes' => count($datosPendientes),
            'total_vencidas' => count($datosVencidas),

            'tareas_pendientes' => $datosPendientes,
            'tareas_vencidas' => $datosVencidas,
        ];

        $json = json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );

        $prompt = <<<PROMPT
Eres el asistente inteligente de GanTek,
un sistema para gestión de ganado lechero.

Analiza únicamente los datos proporcionados.
No inventes tareas, fechas ni información.

Datos reales de tareas del usuario:

$json

Responde en español de forma breve y clara.

Explica:
1. Cuántas tareas pendientes existen.
2. Cuáles son las tareas pendientes más próximas.
3. Cuántas tareas están vencidas.
4. Cuáles tareas tienen prioridad alta.
5. Si una tarea está relacionada con ganado o lote,
   menciona esa relación únicamente cuando exista.

No afirmes que una tarea fue realizada si su estado
no indica que está completada.
PROMPT;

        return $this->claudeService->ask($prompt);
    }

    public function consultarGanado(
        int $userId,
        string $pregunta
    ): string {
        $user = User::find($userId);

        if (!$user) {
            return 'No se encontró el usuario.';
        }

        $ganado = Ganado::forUser($user)
            ->with([
                'lote:id,nombre',
            ])
            ->orderBy('arete_siniiga')
            ->get();

        if ($ganado->isEmpty()) {
            return 'No tienes ganado registrado.';
        }

        $total = $ganado->count();

        $activos = $ganado
            ->where('estado', 'Activo')
            ->count();

        $bajas = $ganado
            ->where('estado', 'Baja')
            ->count();

        $hembras = $ganado
            ->where('sexo', 'Hembra')
            ->count();

        $machos = $ganado
            ->where('sexo', 'Macho')
            ->count();

        $porLote = $ganado
            ->groupBy(function ($animal) {
                return $animal->lote?->nombre
                    ?? 'Sin lote';
            })
            ->map(function ($animales, $lote) {
                return [
                    'lote' => $lote,
                    'total' => $animales->count(),
                ];
            })
            ->values()
            ->all();

        $animales = $ganado
            ->map(function ($animal) {
                return [
                    'arete' =>
                    $animal->arete_siniiga,

                    'nombre' =>
                    $animal->nombre,

                    'sexo' =>
                    $animal->sexo,

                    'raza' =>
                    $animal->raza,

                    'estado_productivo' =>
                    $animal->estado_productivo,

                    'estado' =>
                    $animal->estado,

                    'lote' =>
                    $animal->lote?->nombre
                        ?? 'Sin lote',
                ];
            })
            ->values()
            ->all();

        $datos = [
            'total_ganado' => $total,
            'activos' => $activos,
            'bajas' => $bajas,
            'hembras' => $hembras,
            'machos' => $machos,
            'ganado_por_lote' => $porLote,
            'animales' => $animales,
        ];

        $json = json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );

        $prompt = <<<PROMPT
Eres el asistente inteligente de GanTek,
un sistema para gestión de ganado lechero.

La pregunta exacta del usuario es:

{$pregunta}

Datos reales del ganado del usuario:

{$json}

Responde directamente a la pregunta anterior
utilizando únicamente los datos proporcionados.

Reglas:
- No inventes animales.
- No inventes nombres, aretes, razas, estados ni lotes.
- Si pregunta cuántos animales tiene, responde el total.
- Si pregunta por distribución por lote, responde esa distribución.
- Si solicita una lista de animales, menciona los registros disponibles.
- No preguntes qué desea consultar.
- No digas que no recibiste una pregunta.
- No diagnostiques enfermedades.

Responde en español de forma clara y breve.
PROMPT;

        return $this->claudeService->ask(
            $prompt
        );
    }

    public function generarResumenGeneral(
        int $userId
    ): string {
        $user = User::find($userId);

        if (!$user) {
            return 'No se encontró el usuario.';
        }

        // =========================
        // GANADO
        // =========================
        $ganado = Ganado::forUser($user)
            ->get();

        $totalGanado = $ganado->count();

        $ganadoActivo = $ganado
            ->where('estado', 'Activo')
            ->count();

        $ganadoBaja = $ganado
            ->where('estado', 'Baja')
            ->count();

        // =========================
        // PRODUCCIÓN
        // =========================
        $ultimaFechaProduccion = SesionOrdenio::query()
            ->where('user_id', $userId)
            ->where('estado', 'finalizado')
            ->max('fecha');

        $produccion = [
            'periodo' => null,
            'litros' => 0,
            'sesiones' => 0,
        ];

        if ($ultimaFechaProduccion) {
            $fin = Carbon::parse(
                $ultimaFechaProduccion
            );

            $inicio = $fin
                ->copy()
                ->subDays(6);

            $sesiones = SesionOrdenio::query()
                ->where('user_id', $userId)
                ->where('estado', 'finalizado')
                ->whereBetween(
                    'fecha',
                    [
                        $inicio->toDateString(),
                        $fin->toDateString(),
                    ]
                )
                ->get();

            $produccion = [
                'periodo' => [
                    'inicio' =>
                    $inicio->toDateString(),

                    'fin' =>
                    $fin->toDateString(),
                ],

                'litros' =>
                round(
                    (float) $sesiones
                        ->sum('litros_totales'),
                    2
                ),

                'sesiones' =>
                $sesiones->count(),
            ];
        }

        // =========================
        // VACUNAS
        // =========================
        $alertaService = app(
            AlertaService::class
        );

        $vacunasProximas = $alertaService
            ->proximas($user)
            ->count();

        $vacunasVencidas = $alertaService
            ->vencidas($user)
            ->count();

        // =========================
        // TAREAS
        // =========================
        $hoy = today()->toDateString();

        $tareasPendientes = Tarea::query()
            ->where('user_id', $userId)
            ->where('estado', 'pendiente')
            ->whereDate('fecha', '>=', $hoy)
            ->count();

        $tareasVencidas = Tarea::query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($hoy) {
                $query
                    ->where('estado', 'vencida')
                    ->orWhere(function ($subquery) use ($hoy) {
                        $subquery
                            ->where('estado', 'pendiente')
                            ->whereDate(
                                'fecha',
                                '<',
                                $hoy
                            );
                    });
            })
            ->count();

        // =========================
        // DATOS PARA CLAUDE
        // =========================
        $datos = [
            'ganado' => [
                'total' =>
                $totalGanado,

                'activos' =>
                $ganadoActivo,

                'bajas' =>
                $ganadoBaja,
            ],

            'produccion' =>
            $produccion,

            'vacunas' => [
                'proximas' =>
                $vacunasProximas,

                'vencidas' =>
                $vacunasVencidas,
            ],

            'tareas' => [
                'pendientes' =>
                $tareasPendientes,

                'vencidas' =>
                $tareasVencidas,
            ],
        ];

        $json = json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );

        $prompt = <<<PROMPT
Eres el asistente inteligente de GanTek,
un sistema de gestión de ganado lechero.

Genera un resumen general utilizando únicamente
los datos proporcionados.

Datos reales del usuario:

{$json}

Incluye:
1. Estado general del ganado.
2. Producción reciente disponible.
3. Vacunas próximas y vencidas.
4. Tareas pendientes y vencidas.
5. Qué aspecto requiere mayor atención,
   únicamente según los datos disponibles.

Reglas:
- No inventes información.
- No inventes causas.
- No diagnostiques enfermedades.
- No recomiendes medicamentos.
- Si no existen datos de alguna sección,
  indícalo claramente.
- Sé breve y claro.

Responde en español.
PROMPT;

        return $this->claudeService->ask(
            $prompt
        );
    }

    public function responder(
        int $userId,
        string $pregunta
    ): string {
        $preguntaNormalizada = mb_strtolower(
            trim($pregunta)
        );

        if (
            str_contains(
                $preguntaNormalizada,
                'producción'
            )
            || str_contains(
                $preguntaNormalizada,
                'produccion'
            )
            || str_contains(
                $preguntaNormalizada,
                'leche'
            )
            || str_contains(
                $preguntaNormalizada,
                'ordeña'
            )
            || str_contains(
                $preguntaNormalizada,
                'ordena'
            )
        ) {
            return $this->analizarProduccionSemanal(
                $userId
            );
        }

        if (
            str_contains($preguntaNormalizada, 'vacuna')
            || str_contains($preguntaNormalizada, 'vacunación')
            || str_contains($preguntaNormalizada, 'vacunacion')
            || str_contains($preguntaNormalizada, 'vacunas')
        ) {
            return $this->consultarVacunasProximas(
                $userId
            );
        }
        if (
            str_contains($preguntaNormalizada, 'tarea')
            || str_contains($preguntaNormalizada, 'tareas')
            || str_contains($preguntaNormalizada, 'actividad')
            || str_contains($preguntaNormalizada, 'actividades')
            || str_contains($preguntaNormalizada, 'pendiente')
            || str_contains($preguntaNormalizada, 'vencida')
            || str_contains($preguntaNormalizada, 'vencidas')
            || str_contains($preguntaNormalizada, 'agenda')
        ) {
            return $this->consultarTareas(
                $userId
            );
        }

        if (
            str_contains(
                $preguntaNormalizada,
                'resumen'
            )
            || str_contains(
                $preguntaNormalizada,
                'resumen general'
            )
            || str_contains(
                $preguntaNormalizada,
                'estado general'
            )
            || str_contains(
                $preguntaNormalizada,
                'cómo va todo'
            )
            || str_contains(
                $preguntaNormalizada,
                'como va todo'
            )
        ) {
            return $this->generarResumenGeneral(
                $userId
            );
        }

        if (
            str_contains($preguntaNormalizada, 'ganado')
            || str_contains($preguntaNormalizada, 'animal')
            || str_contains($preguntaNormalizada, 'animales')
            || str_contains($preguntaNormalizada, 'vaca')
            || str_contains($preguntaNormalizada, 'vacas')
            || str_contains($preguntaNormalizada, 'arete')
            || str_contains($preguntaNormalizada, 'raza')
            || str_contains($preguntaNormalizada, 'lote')
        ) {
            return $this->consultarGanado(
                $userId,
                $pregunta
            );
        }

        return $this->claudeService->ask(
            <<<PROMPT
Eres el asistente inteligente de GanTek,
un sistema de gestión de ganado lechero.

El usuario hizo esta pregunta:

{$pregunta}

Actualmente tienes acceso a herramientas para consultar:
- producción de leche;
- vacunas próximas y vencidas;
- tareas pendientes y vencidas.

Si la pregunta requiere otros datos privados de GanTek
que todavía no están disponibles, indícalo claramente.

Puedes responder preguntas generales relacionadas
con ganadería lechera, pero no inventes información
del usuario.

No diagnostiques enfermedades ni reemplaces
la valoración de un médico veterinario.

Responde en español de forma clara y breve.
PROMPT
        );
    }
}
