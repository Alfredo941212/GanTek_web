<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoteRequest;
use App\Models\Lote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $lotes = Lote::forUser($request->user())
            ->with('finca:id,nombre')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $lotes,
        ]);
    }

    public function store(LoteRequest $request): JsonResponse
    {
        $lote = Lote::create($request->validated());

        $lote->load('finca:id,nombre');

        return response()->json([
            'message' => 'Lote registrado correctamente.',
            'data' => $lote,
        ], 201);
    }

    public function show(Lote $lote): JsonResponse
    {
        Gate::authorize('view', $lote);

        $lote->load('finca:id,nombre');

        return response()->json([
            'data' => $lote,
        ]);
    }

    public function update(LoteRequest $request, Lote $lote): JsonResponse
    {
        $lote->update(
            $request->safe()->only([
                'nombre',
                'descripcion',
                'produccion_minima_por_vaca',
                'estado',
            ])
        );

        $lote->load('finca:id,nombre');

        return response()->json([
            'message' => 'Lote actualizado correctamente.',
            'data' => $lote->fresh()->load('finca:id,nombre'),
        ]);
    }

    public function destroy(Lote $lote): JsonResponse
    {
        Gate::authorize('delete', $lote);

        $this->deleteWithoutHistory(
            $lote,
            ['ganado', 'registrosOrdenioHistoricos']
        );

        return response()->json([
            'message' => 'Lote eliminado correctamente.',
        ]);
    }
}