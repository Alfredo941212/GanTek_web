<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GrupoOrdenio;
use App\Models\SesionOrdenio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SesionOrdenioController extends Controller
{
    public function hoy(Request $request)
    {
        $sesiones = SesionOrdenio::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('fecha', now()->toDateString())
            ->with([
                'grupo:id,nombre,orden',
            ])
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sesiones,
        ]);
    }

    public function iniciarJornada(Request $request)
    {
        $user = $request->user();
        $fecha = now()->toDateString();
        $ahora = now();

        $yaExiste = SesionOrdenio::query()
            ->where('user_id', $user->id)
            ->whereDate('fecha', $fecha)
            ->exists();

        if ($yaExiste) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una jornada de ordeña para hoy.',
            ], 422);
        }

        $grupos = GrupoOrdenio::query()
            ->where('user_id', $user->id)
            ->where('estado', 'Activo')
            ->with('ganado')
            ->orderBy('orden')
            ->get();

        if ($grupos->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay grupos de ordeña activos.',
            ], 422);
        }

        foreach ($grupos as $grupo) {
            if ($grupo->ganado->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El grupo "' . $grupo->nombre . '" no tiene vacas asignadas.',
                ], 422);
            }
        }

        $sesiones = DB::transaction(function () use (
            $grupos,
            $user,
            $fecha,
            $ahora
        ) {
            $creadas = collect();

            foreach ($grupos as $index => $grupo) {
                $esPrimero = $index === 0;

                $sesion = SesionOrdenio::create([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'grupo_ordenio_id' => $grupo->id,
                    'fecha' => $fecha,
                    'hora_inicio' => $esPrimero ? $ahora : null,
                    'hora_fin' => null,
                    'duracion_minutos' => null,
                    'cantidad_vacas' => $grupo->ganado->count(),
                    'litros_totales' => null,
                    'estado' => $esPrimero
                        ? 'en_produccion'
                        : 'en_espera',
                    'observaciones' => null,
                ]);

                $sesion->ganado()->attach(
                    $grupo->ganado->pluck('id')->all()
                );

                $creadas->push(
                    $sesion->load(
                        'grupo:id,nombre,orden',
                        'ganado:id,arete_siniiga,nombre'
                    )
                );
            }

            return $creadas;
        });

        return response()->json([
            'success' => true,
            'message' => 'Jornada de ordeña iniciada correctamente.',
            'data' => $sesiones,
        ], 201);
    }

    public function finalizar(
        Request $request,
        SesionOrdenio $sesionOrdenio
    ) {
        if ($sesionOrdenio->user_id !== $request->user()->id) {
            abort(
                403,
                'No tienes permiso para acceder a esta sesión.'
            );
        }

        if ($sesionOrdenio->estado !== 'en_produccion') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede finalizar el grupo que está en producción.',
            ], 422);
        }

        $validated = $request->validate([
            'litros_totales' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $ahora = now();

        if ($sesionOrdenio->hora_inicio === null) {
            return response()->json([
                'success' => false,
                'message' => 'La sesión no tiene hora de inicio.',
            ], 422);
        }

        $duracionMinutos = max(
            1,
            $sesionOrdenio
                ->hora_inicio
                ->diffInMinutes($ahora)
        );

        DB::transaction(function () use (
            $sesionOrdenio,
            $validated,
            $ahora,
            $duracionMinutos,
            $request
        ) {
            // 1. Finalizar sesión actual.
            $sesionOrdenio->update([
                'hora_fin' => $ahora,
                'duracion_minutos' => $duracionMinutos,
                'litros_totales' => $validated['litros_totales'],
                'estado' => 'finalizado',
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // 2. Buscar siguiente sesión en espera.
            $siguiente = SesionOrdenio::query()
                ->where('user_id', $request->user()->id)
                ->whereDate(
                    'fecha',
                    $sesionOrdenio->fecha->toDateString()
                )
                ->where('estado', 'en_espera')
                ->whereHas('grupo')
                ->with('grupo:id,orden')
                ->get()
                ->sortBy(
                    fn(SesionOrdenio $sesion) =>
                    $sesion->grupo->orden
                )
                ->first();

            // 3. Activar automáticamente el siguiente grupo.
            if ($siguiente) {
                $siguiente->update([
                    'estado' => 'en_produccion',
                    'hora_inicio' => $ahora,
                ]);
            }
        });

        $sesionOrdenio->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Sesión de ordeña finalizada correctamente.',
            'data' => $sesionOrdenio->load(
                'grupo:id,nombre,orden'
            ),
        ]);
    }

    public function historial(Request $request)
    {
        $sesiones = SesionOrdenio::query()
            ->where('user_id', $request->user()->id)
            ->where('estado', 'finalizado')
            ->with([
                'grupo:id,nombre,orden',
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sesiones,
        ]);
    }

    public function importar(Request $request)
    {
        $data = $request->validate([
            'grupo_ordenio_id' => [
                'required',
                'integer',
                'exists:grupos_ordenio,id',
            ],
            'fecha' => [
                'required',
                'date',
            ],
            'hora_inicio' => [
                'nullable',
                'date',
            ],
            'hora_fin' => [
                'nullable',
                'date',
            ],
            'duracion_minutos' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'cantidad_vacas' => [
                'required',
                'integer',
                'min:0',
            ],
            'litros_totales' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'estado' => [
                'required',
                'in:finalizado',
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $grupo = GrupoOrdenio::query()
            ->where('id', $data['grupo_ordenio_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$grupo) {
            return response()->json([
                'success' => false,
                'message' => 'El grupo de ordeña no pertenece al usuario.',
            ], 403);
        }

        /*
     * Evitar duplicar una sesión si ya fue importada
     * anteriormente.
     */
        $existente = SesionOrdenio::query()
            ->where('user_id', $request->user()->id)
            ->where(
                'grupo_ordenio_id',
                $grupo->id
            )
            ->whereDate(
                'fecha',
                $data['fecha']
            )
            ->first();

        if ($existente) {
            return response()->json([
                'success' => true,
                'message' => 'La sesión ya existe en el servidor.',
                'data' => $existente->load('grupo'),
            ], 200);
        }

        $sesion = SesionOrdenio::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'grupo_ordenio_id' => $grupo->id,
            'fecha' => $data['fecha'],
            'hora_inicio' => $data['hora_inicio'] ?? null,
            'hora_fin' => $data['hora_fin'] ?? null,
            'duracion_minutos' =>
            $data['duracion_minutos'] ?? null,
            'cantidad_vacas' =>
            $data['cantidad_vacas'],
            'litros_totales' =>
            $data['litros_totales'],
            'estado' => 'finalizado',
            'observaciones' =>
            $data['observaciones'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sesión histórica importada correctamente.',
            'data' => $sesion->load('grupo'),
        ], 201);
    }
}
