<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FincaRequest;
use App\Models\Finca;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FincaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $fincas = Finca::forUser($request->user())
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $fincas,
        ]);
    }

    public function store(FincaRequest $request): JsonResponse
    {
        $finca = $request->user()
            ->fincas()
            ->create($request->validated());

        return response()->json([
            'message' => 'Finca registrada correctamente.',
            'data' => $finca,
        ], 201);
    }

    public function show(Finca $finca): JsonResponse
    {
        Gate::authorize('view', $finca);

        return response()->json([
            'data' => $finca,
        ]);
    }

    public function update(FincaRequest $request, Finca $finca): JsonResponse
    {
        $finca->update($request->validated());

        return response()->json([
            'message' => 'Finca actualizada correctamente.',
            'data' => $finca->fresh(),
        ]);
    }

    public function destroy(Finca $finca): JsonResponse
    {
        Gate::authorize('delete', $finca);

        $this->deleteWithoutHistory($finca, ['lotes']);

        return response()->json([
            'message' => 'Finca eliminada correctamente.',
        ]);
    }
}