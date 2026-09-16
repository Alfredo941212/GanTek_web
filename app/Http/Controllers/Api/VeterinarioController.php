<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VeterinarioRequest;
use App\Models\Veterinario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VeterinarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $veterinarios = Veterinario::orderBy('nombre')->get();

        return response()->json([
            'data' => $veterinarios,
        ]);
    }

    public function store(VeterinarioRequest $request): JsonResponse
    {
        $veterinario = Veterinario::create($request->validated());

        return response()->json([
            'message' => 'Veterinario registrado correctamente.',
            'data' => $veterinario,
        ], 201);
    }

    public function show(Veterinario $veterinario): JsonResponse
    {
        return response()->json([
            'data' => $veterinario,
        ]);
    }

    public function update(
        VeterinarioRequest $request,
        Veterinario $veterinario
    ): JsonResponse {
        $veterinario->update($request->validated());

        return response()->json([
            'message' => 'Veterinario actualizado correctamente.',
            'data' => $veterinario->fresh(),
        ]);
    }

    public function destroy(Veterinario $veterinario): JsonResponse
    {
        Gate::authorize('delete', $veterinario);

        $veterinario->update([
            'estado' => 'Inactivo',
        ]);

        return response()->json([
            'message' => 'Veterinario desactivado correctamente. El historial se conserva.',
            'data' => $veterinario->fresh(),
        ]);
    }
}
