<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GrupoOrdenio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GrupoOrdenioController extends Controller
{
    public function index(Request $request)
    {
        $grupos = GrupoOrdenio::query()
            ->where('user_id', $request->user()->id)
            ->withCount('ganado')
            ->orderBy('orden')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $grupos,
        ]);
    }

    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('grupos_ordenio', 'nombre')
                    ->where('user_id', $userId),
            ],
        ]);

        $orden = (int) GrupoOrdenio::query()
            ->where('user_id', $userId)
            ->max('orden') + 1;

        $grupo = GrupoOrdenio::create([
            'user_id' => $userId,
            'nombre' => trim($validated['nombre']),
            'orden' => $orden,
            'estado' => 'Activo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grupo de ordeña creado correctamente.',
            'data' => $grupo,
        ], 201);
    }

    public function show(Request $request, GrupoOrdenio $grupoOrdenio)
    {
        $this->validateOwnership(
            $request,
            $grupoOrdenio
        );

        $grupoOrdenio->load('ganado');

        return response()->json([
            'success' => true,
            'data' => $grupoOrdenio,
        ]);
    }

    public function update(
        Request $request,
        GrupoOrdenio $grupoOrdenio
    ) {
        $this->validateOwnership(
            $request,
            $grupoOrdenio
        );

        $validated = $request->validate([
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('grupos_ordenio', 'nombre')
                    ->where('user_id', $request->user()->id)
                    ->ignore($grupoOrdenio->id),
            ],
            'estado' => [
                'sometimes',
                Rule::in([
                    'Activo',
                    'Inactivo',
                ]),
            ],
        ]);

        $grupoOrdenio->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Grupo actualizado correctamente.',
            'data' => $grupoOrdenio,
        ]);
    }

    public function destroy(
        Request $request,
        GrupoOrdenio $grupoOrdenio
    ) {
        $this->validateOwnership(
            $request,
            $grupoOrdenio
        );

        $grupoOrdenio->update([
            'estado' => 'Inactivo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grupo desactivado correctamente.',
        ]);
    }

    public function agregarGanado(
        Request $request,
        GrupoOrdenio $grupoOrdenio
    ) {
        $this->validateOwnership(
            $request,
            $grupoOrdenio
        );

        if ($grupoOrdenio->estado !== 'Activo') {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden agregar vacas a un grupo inactivo.',
            ], 422);
        }

        $validated = $request->validate([
            'ganado_id' => [
                'required',
                'integer',
                'exists:ganado,id',
            ],
        ]);

        $ganado = \App\Models\Ganado::query()
            ->forUser($request->user())
            ->where('id', $validated['ganado_id'])
            ->first();

        if (!$ganado) {
            return response()->json([
                'success' => false,
                'message' => 'El ganado no pertenece al usuario autenticado.',
            ], 403);
        }

        if ($ganado->sexo !== 'Hembra') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden asignar hembras al grupo de ordeña.',
            ], 422);
        }

        if ($ganado->estado !== 'Activo') {
            return response()->json([
                'success' => false,
                'message' => 'El ganado debe estar activo.',
            ], 422);
        }

        if ($ganado->estado_productivo !== 'En producción') {
            return response()->json([
                'success' => false,
                'message' => 'La vaca debe estar en producción.',
            ], 422);
        }

        $grupoExistente = \App\Models\GrupoOrdenio::query()
            ->where('user_id', $request->user()->id)
            ->where('estado', 'Activo')
            ->whereHas('ganado', function ($query) use ($ganado) {
                $query->where('ganado.id', $ganado->id);
            })
            ->first();

        if ($grupoExistente) {
            return response()->json([
                'success' => false,
                'message' => 'La vaca ya pertenece a un grupo de ordeña activo.',
            ], 422);
        }

        $grupoOrdenio->ganado()->attach(
            $ganado->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Vaca agregada al grupo correctamente.',
        ]);
    }

    public function quitarGanado(
        Request $request,
        GrupoOrdenio $grupoOrdenio,
        int $ganado
    ) {
        $this->validateOwnership(
            $request,
            $grupoOrdenio
        );

        $exists = $grupoOrdenio
            ->ganado()
            ->where('ganado.id', $ganado)
            ->exists();

        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'La vaca no pertenece a este grupo.',
            ], 404);
        }

        $grupoOrdenio
            ->ganado()
            ->detach($ganado);

        return response()->json([
            'success' => true,
            'message' => 'Vaca retirada del grupo correctamente.',
        ]);
    }

    private function validateOwnership(
        Request $request,
        GrupoOrdenio $grupoOrdenio
    ): void {
        if ($grupoOrdenio->user_id !== $request->user()->id) {
            abort(
                403,
                'No tienes permiso para acceder a este grupo.'
            );
        }
    }
}
