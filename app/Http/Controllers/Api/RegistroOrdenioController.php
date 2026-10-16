<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroOrdenioRequest;
use App\Models\Ganado;
use App\Models\Lote;
use App\Models\RegistroOrdenio;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RegistroOrdenioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $ordenios = RegistroOrdenio::forUser($request->user())
            ->with([
                'ganado',
                'loteHistorico.finca',
            ])
            ->latest('fecha')
            ->orderBy('numero_ordenio')
            ->get();

        return response()->json([
            'data' => $ordenios,
        ]);
    }

    public function store(RegistroOrdenioRequest $request): JsonResponse
    {
        $data = $request->validated();

        // ---------------------------------------------------------
        // EVITAR DUPLICADOS EN LA SINCRONIZACIÓN
        // ---------------------------------------------------------
        // Si Flutter ya había enviado este UUID anteriormente,
        // devolvemos el mismo registro en lugar de crear otro.
        $existente = RegistroOrdenio::forUser($request->user())
            ->where('uuid', $data['uuid'])
            ->with(['ganado', 'loteHistorico.finca'])
            ->first();

        if ($existente) {
            return response()->json([
                'message' => 'El ordeño ya estaba sincronizado.',
                'data' => $existente,
            ], 200);
        }

        try {
            $ordenio = DB::transaction(
                function () use ($request, $data): RegistroOrdenio {
                    $animal = Ganado::whereKey($request->integer('ganado_id'))
                        ->lockForUpdate()
                        ->firstOrFail();

                    Gate::authorize('update', $animal);

                    if (
                        $animal->estado !== 'Activo'
                        || $animal->sexo !== 'Hembra'
                        || $data['fecha'] < $animal->fecha_ingreso->toDateString()
                    ) {
                        throw ValidationException::withMessages([
                            'ganado_id' =>
                            'Selecciona una hembra activa y una fecha desde su ingreso.',
                        ]);
                    }

                    // Si el ordeño es de hoy y Flutter no envió
                    // lote_historico_id, usamos el lote actual.
                    if (
                        $data['fecha'] === today()->toDateString()
                        && empty($data['lote_historico_id'])
                    ) {
                        $data['lote_historico_id'] = $animal->lote_id;
                    }

                    // Para registros offline de días anteriores,
                    // Flutter deberá enviar lote_historico_id.
                    $lote = Lote::forUser($request->user())
                        ->findOrFail($data['lote_historico_id']);

                    $data['lote_historico_id'] = $lote->id;

                    // Laravel mantiene el número de ordeño como
                    // dato canónico del servidor.
                    $ultimoNumero = $animal->registrosOrdenio()
                        ->whereDate('fecha', $data['fecha'])
                        ->max('numero_ordenio');

                    $numeroOrdenio = ((int) $ultimoNumero) + 1;

                    if ($numeroOrdenio > 20) {
                        throw ValidationException::withMessages([
                            'numero_ordenio' =>
                            'No se pueden registrar más de 20 ordeños para este animal en la misma fecha.',
                        ]);
                    }

                    $data['numero_ordenio'] = $numeroOrdenio;

                    return $animal->registrosOrdenio()->create($data);
                }
            );
        } catch (UniqueConstraintViolationException $exception) {

            // Puede ocurrir que Laravel haya guardado el UUID
            // mientras otro intento de sincronización estaba ejecutándose.
            $existente = RegistroOrdenio::forUser($request->user())
                ->where('uuid', $data['uuid'])
                ->with(['ganado', 'loteHistorico.finca'])
                ->first();

            if ($existente) {
                return response()->json([
                    'message' => 'El ordeño ya estaba sincronizado.',
                    'data' => $existente,
                ], 200);
            }

            // Si el conflicto no fue por UUID, conservamos
            // el comportamiento para numero_ordenio.
            throw ValidationException::withMessages([
                'numero_ordenio' =>
                'Ya existe este número de ordeño para el animal y la fecha seleccionados.',
            ]);
        }

        return response()->json([
            'message' => 'Ordeño registrado correctamente.',
            'data' => $ordenio->load([
                'ganado',
                'loteHistorico.finca',
            ]),
        ], 201);
    }

    public function show(RegistroOrdenio $ordenio): JsonResponse
    {
        Gate::authorize('view', $ordenio);

        return response()->json([
            'data' => $ordenio->load([
                'ganado',
                'loteHistorico.finca',
            ]),
        ]);
    }

    public function update(
        RegistroOrdenioRequest $request,
        RegistroOrdenio $ordenio
    ): JsonResponse {
        $ordenio->update(
            $request->safe()->only([
                'litros',
                'observaciones',
            ])
        );

        return response()->json([
            'message' => 'Ordeño corregido correctamente. Se conserva su lote histórico.',
            'data' => $ordenio->fresh()->load([
                'ganado',
                'loteHistorico.finca',
            ]),
        ]);
    }

    public function destroy(RegistroOrdenio $ordenio): JsonResponse
    {
        Gate::authorize('delete', $ordenio);

        $ordenio->delete();

        return response()->json([
            'message' => 'Ordeño eliminado correctamente.',
        ]);
    }
}
