<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tarea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Ganado;
use App\Models\Lote;

class TareaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tareas = Tarea::where(
            'user_id',
            $request->user()->id
        )
            ->orderBy('fecha')
            ->orderBy('hora')
            ->orderBy('id')
            ->paginate(50);

        return response()->json([
            'success' => true,

            // Flutter recibe directamente la lista
            'data' => $tareas->items(),

            // Conservamos información de paginación
            'pagination' => [
                'current_page' => $tareas->currentPage(),
                'last_page' => $tareas->lastPage(),
                'per_page' => $tareas->perPage(),
                'total' => $tareas->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ganado_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    $existe = Ganado::forUser($request->user())
                        ->where('id', $value)
                        ->exists();

                    if (!$existe) {
                        $fail(
                            'El ganado seleccionado no existe '
                                . 'o no pertenece al usuario.'
                        );
                    }
                },
            ],

            'lote_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    $existe = Lote::whereHas(
                        'finca',
                        function ($query) use ($request) {
                            $query->where(
                                'user_id',
                                $request->user()->id
                            );
                        }
                    )
                        ->where('id', $value)
                        ->exists();

                    if (!$existe) {
                        $fail(
                            'El lote seleccionado no existe '
                                . 'o no pertenece al usuario.'
                        );
                    }
                },
            ],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'tipo' => [
                'required',
                'in:ordenio,vacunacion,veterinaria,produccion,general',
            ],
            'fecha' => ['required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'prioridad' => ['required', 'in:baja,media,alta'],
            'recordatorio' => ['nullable', 'boolean'],
        ]);

        $data['user_id'] = $request->user()->id;
        $data['estado'] = 'pendiente';
        $data['creada_por_ia'] = false;

        $tarea = Tarea::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tarea creada correctamente.',
            'data' => $tarea,
        ], 201);
    }

    public function show(Request $request, Tarea $tarea): JsonResponse
    {
        $this->validarPropietario($request, $tarea);

        return response()->json([
            'success' => true,
            'data' => $tarea,
        ]);
    }

    public function update(
        Request $request,
        Tarea $tarea
    ): JsonResponse {
        $this->validarPropietario($request, $tarea);

        $data = $request->validate([
            'ganado_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    $existe = Ganado::forUser($request->user())
                        ->where('id', $value)
                        ->exists();

                    if (!$existe) {
                        $fail(
                            'El ganado seleccionado no existe '
                                . 'o no pertenece al usuario.'
                        );
                    }
                },
            ],

            'lote_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    $existe = Lote::whereHas(
                        'finca',
                        function ($query) use ($request) {
                            $query->where(
                                'user_id',
                                $request->user()->id
                            );
                        }
                    )
                        ->where('id', $value)
                        ->exists();

                    if (!$existe) {
                        $fail(
                            'El lote seleccionado no existe '
                                . 'o no pertenece al usuario.'
                        );
                    }
                },
            ],
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'tipo' => [
                'sometimes',
                'required',
                'in:ordenio,vacunacion,veterinaria,produccion,general',
            ],
            'fecha' => ['sometimes', 'required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'prioridad' => ['sometimes', 'required', 'in:baja,media,alta'],
            'recordatorio' => ['nullable', 'boolean'],
        ]);

        $tarea->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Tarea actualizada correctamente.',
            'data' => $tarea->fresh(),
        ]);
    }

    public function completar(
        Request $request,
        Tarea $tarea
    ): JsonResponse {
        $this->validarPropietario($request, $tarea);

        $tarea->update([
            'estado' => 'completada',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tarea marcada como completada.',
            'data' => $tarea->fresh(),
        ]);
    }

    public function cancelar(
        Request $request,
        Tarea $tarea
    ): JsonResponse {
        $this->validarPropietario($request, $tarea);

        $tarea->update([
            'estado' => 'cancelada',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tarea cancelada.',
            'data' => $tarea->fresh(),
        ]);
    }

    public function destroy(
        Request $request,
        Tarea $tarea
    ): JsonResponse {
        $this->validarPropietario(
            $request,
            $tarea
        );

        if (! in_array(
            $tarea->estado,
            ['vencida', 'cancelada'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden eliminar tareas vencidas o canceladas.',
            ], 422);
        }

        $tarea->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tarea eliminada correctamente.',
        ]);
    }

    private function validarPropietario(
        Request $request,
        Tarea $tarea
    ): void {
        abort_if(
            $tarea->user_id !== $request->user()->id,
            403,
            'No tienes permiso para acceder a esta tarea.'
        );
    }
}
