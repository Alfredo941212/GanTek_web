<?php

namespace App\Services;

use App\Models\Ganado;
use App\Models\RegistroOrdenio;
use App\Models\Tarea;
use App\Models\User;

class AgendaIAService
{
    public function generarTareas(User $user): array
    {
        $creadas = [];

        $creadas = array_merge(
            $creadas,
            $this->generarTareasOrdenio($user)
        );

        $creadas = array_merge(
            $creadas,
            $this->generarTareasVacunacion($user)
        );

        $creadas = array_merge(
            $creadas,
            $this->generarTareasProduccion($user)
        );

        return [
            'total_creadas' => count($creadas),
            'tareas_creadas' => $creadas,
        ];
    }

    /**
     * Genera recordatorios para ordeña de mañana y tarde.
     */
    private function generarTareasOrdenio(User $user): array
    {
        $tareas = [];

        $vacasProduccion = Ganado::forUser($user)
            ->where('estado', 'Activo')
            ->where('sexo', 'Hembra')
            ->where('estado_productivo', 'En producción')
            ->count();

        if ($vacasProduccion === 0) {
            return [];
        }

        $fecha = today()->toDateString();

        $turnos = [
            'Mañana' => '07:00',
            'Tarde' => '17:00',
        ];

        foreach ($turnos as $turno => $hora) {

            $existeOrdenio = RegistroOrdenio::forUser($user)
                ->whereDate('fecha', $fecha)
                ->where('turno', $turno)
                ->exists();

            if ($existeOrdenio) {
                continue;
            }

            $titulo = "Registrar ordeña de la {$turno}";

            $horaActual = now()->format('H:i');

            $prioridad = 'media';

            $descripcion = "No se ha registrado la ordeña del turno {$turno}.";

            if ($horaActual > $hora) {
                $prioridad = 'alta';

                $descripcion =
                    "La ordeña del turno {$turno} está atrasada "
                    . "y todavía no ha sido registrada.";
            }

            $tarea = Tarea::firstOrNew([
                'user_id' => $user->id,
                'titulo' => $titulo,
                'fecha' => $fecha,
            ]);

            $esNueva = !$tarea->exists;

            $tarea->descripcion = $descripcion;
            $tarea->tipo = 'ordenio';
            $tarea->hora = $hora;
            $tarea->prioridad = $prioridad;
            $tarea->recordatorio = true;
            $tarea->creada_por_ia = true;

            if ($esNueva) {
                $tarea->estado = 'pendiente';
            }

            $tarea->save();

            if ($esNueva) {
                $tareas[] = $tarea->toArray();
            }

            if ($tarea->wasRecentlyCreated) {
                $tareas[] = $tarea->toArray();
            }
        }

        return $tareas;
    }

    /**
     * Genera tareas de vacunación próximas.
     */
    private function generarTareasVacunacion(User $user): array
    {
        $tareas = [];

        $alertaService = app(AlertaService::class);

        $vacunaciones = $alertaService
            ->proximas($user)
            ->with(['ganado', 'vacuna'])
            ->get();

        foreach ($vacunaciones as $vacunacion) {

            if (!$vacunacion->proxima_aplicacion) {
                continue;
            }

            $arete = $vacunacion->ganado?->arete_siniiga ?? 'Sin arete';

            $vacuna = $vacunacion->vacuna?->nombre ?? 'Vacuna';

            $titulo = "Aplicar {$vacuna} a {$arete}";

            $tarea = Tarea::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'ganado_id' => $vacunacion->ganado_id,
                    'titulo' => $titulo,
                    'fecha' => $vacunacion
                        ->proxima_aplicacion
                        ->toDateString(),
                ],
                [
                    'descripcion' =>
                    "Vacunación programada para el animal {$arete}.",

                    'tipo' => 'vacunacion',
                    'prioridad' => 'alta',
                    'estado' => 'pendiente',
                    'recordatorio' => true,
                    'creada_por_ia' => true,
                ]
            );

            if ($tarea->wasRecentlyCreated) {
                $tareas[] = $tarea->toArray();
            }
        }

        return $tareas;
    }

    /**
     * Genera tareas cuando el análisis inteligente detecta
     * producción baja o crítica.
     */
    private function generarTareasProduccion(User $user): array
    {
        $tareas = [];

        $analisisService = app(AnalisisProduccionIAService::class);

        $analisis = $analisisService
            ->analizarProduccion($user);

        foreach ($analisis['animales'] as $animal) {

            if (!in_array(
                $animal['estado'],
                ['baja', 'critica'],
                true
            )) {
                continue;
            }

            $prioridad = $animal['estado'] === 'critica'
                ? 'alta'
                : 'media';

            $titulo = "Revisar producción de {$animal['arete']}";

            $tarea = Tarea::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'ganado_id' => $animal['ganado_id'],
                    'titulo' => $titulo,
                    'fecha' => today()->toDateString(),
                ],
                [
                    'descripcion' => $animal['mensaje'],
                    'tipo' => 'produccion',
                    'prioridad' => $prioridad,
                    'estado' => 'pendiente',
                    'recordatorio' => true,
                    'creada_por_ia' => true,
                ]
            );

            if ($tarea->wasRecentlyCreated) {
                $tareas[] = $tarea->toArray();
            }
        }

        return $tareas;
    }

    /**
     * Devuelve la agenda del día.
     */
    public function agendaHoy(User $user)
    {
        return Tarea::where('user_id', $user->id)
            ->whereDate('fecha', today())
            ->where('estado', 'pendiente')
            ->orderByRaw("
                CASE prioridad
                    WHEN 'alta' THEN 1
                    WHEN 'media' THEN 2
                    WHEN 'baja' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('hora')
            ->get();
    }

    public function actualizarTareasVencidas(User $user): int
    {
        return Tarea::where('user_id', $user->id)
            ->where('estado', 'pendiente')
            ->whereDate('fecha', '<', today())
            ->update([
                'estado' => 'vencida',
            ]);
    }
}
