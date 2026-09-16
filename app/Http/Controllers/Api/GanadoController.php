<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GanadoRequest;
use App\Models\Ganado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GanadoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $query = Ganado::forUser($request->user())
            ->with('lote.finca');

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';

            $query->where(function (Builder $query) use ($search): void {
                $query->where('arete_siniiga', 'like', $search)
                    ->orWhere('nombre', 'like', $search)
                    ->orWhere('raza', 'like', $search);
            });
        }

        return response()->json([
            'data' => $query->latest()->get(),
        ]);
    }

    public function store(GanadoRequest $request): JsonResponse
    {
        $ganado = Ganado::create($request->validated());

        return response()->json([
            'message' => 'Animal registrado correctamente.',
            'data' => $ganado->load('lote.finca'),
        ], 201);
    }

    public function show(Ganado $ganado): JsonResponse
    {
        Gate::authorize('view', $ganado);

        return response()->json([
            'data' => $ganado->load('lote.finca'),
        ]);
    }

    public function update(GanadoRequest $request, Ganado $ganado): JsonResponse
    {
        $animal = DB::transaction(function () use ($request, $ganado): Ganado {
            $animal = Ganado::whereKey($ganado->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('update', $animal);

            if (
                $request->input('sexo') !== 'Hembra'
                && $animal->registrosOrdenio()->exists()
            ) {
                throw ValidationException::withMessages([
                    'sexo' => 'Un animal con ordeños registrados debe conservar el sexo Hembra.',
                ]);
            }

            if (
                $animal->registrosOrdenio()
                    ->where('fecha', '<', $request->input('fecha_ingreso'))
                    ->exists()
                || $animal->vacunaciones()
                    ->where('fecha_aplicacion', '<', $request->input('fecha_ingreso'))
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'fecha_ingreso' => 'La fecha de ingreso no puede ser posterior a su historial.',
                ]);
            }

            $animal->update($request->validated());

            return $animal;
        });

        return response()->json([
            'message' => 'Animal actualizado correctamente. Su historial se conserva.',
            'data' => $animal->fresh()->load('lote.finca'),
        ]);
    }

    public function destroy(Ganado $ganado): JsonResponse
    {
        Gate::authorize('delete', $ganado);

        $ganado->update([
            'estado' => 'Baja',
        ]);

        return response()->json([
            'message' => 'Animal dado de baja correctamente. Su historial se conserva.',
        ]);
    }
}