<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VacunaRequest;
use App\Models\Vacuna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VacunaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vacunas = Vacuna::orderBy('nombre')->get();

        return response()->json([
            'data' => $vacunas,
        ]);
    }

    public function store(VacunaRequest $request): JsonResponse
    {
        $vacuna = Vacuna::create($request->validated());

        return response()->json([
            'message' => 'Vacuna registrada correctamente.',
            'data' => $vacuna,
        ], 201);
    }

    public function show(Vacuna $vacuna): JsonResponse
    {
        return response()->json([
            'data' => $vacuna,
        ]);
    }

    public function update(
        VacunaRequest $request,
        Vacuna $vacuna
    ): JsonResponse {
        $vacuna->update($request->validated());

        return response()->json([
            'message' => 'Vacuna actualizada correctamente.',
            'data' => $vacuna->fresh(),
        ]);
    }

    public function destroy(Vacuna $vacuna): JsonResponse
    {
        Gate::authorize('delete', $vacuna);

        $vacuna->update([
            'estado' => 'Inactivo',
        ]);

        return response()->json([
            'message' => 'Vacuna desactivada correctamente. El historial se conserva.',
            'data' => $vacuna->fresh(),
        ]);
    }
}