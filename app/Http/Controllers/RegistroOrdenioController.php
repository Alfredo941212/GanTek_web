<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistroOrdenioRequest;
use App\Models\Ganado;
use App\Models\Lote;
use App\Models\RegistroOrdenio;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\SesionOrdenio;

class RegistroOrdenioController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Sistema anterior de ordeños.
        // Lo conservamos por ahora para no romper funcionalidades existentes.
        $ordenios = RegistroOrdenio::forUser($user)
            ->with([
                'ganado',
                'loteHistorico.finca',
            ])
            ->latest('fecha')
            ->orderBy('numero_ordenio')
            ->paginate(20);

        // Jornada de ordeña del día.
        $sesionesHoy = SesionOrdenio::query()
            ->where('user_id', $user->id)
            ->whereDate('fecha', now()->toDateString())
            ->with([
                'grupo:id,nombre,orden',
            ])
            ->get()
            ->sortBy(
                fn(SesionOrdenio $sesion) =>
                $sesion->grupo?->orden ?? 999
            )
            ->values();

        // Historial reciente del nuevo sistema por grupos.
        $historial = SesionOrdenio::query()
            ->where('user_id', $user->id)
            ->where('estado', 'finalizado')
            ->with([
                'grupo:id,nombre,orden',
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(
                5,
                ['*'],
                'historial_page'
            );

        return view('ordenios.index', [
            'ordenios' => $ordenios,
            'sesionesHoy' => $sesionesHoy,
            'historial' => $historial,
        ]);
    }

    public function create(Request $request): View
    {
        return view('ordenios.create', [
            'animales' => Ganado::forUser($request->user())->where('estado', 'Activo')->where('sexo', 'Hembra')->with('lote.finca')->orderBy('arete_siniiga')->get(),
            'lotes' => Lote::forUser($request->user())->with('finca')->orderBy('nombre')->get(),
        ]);
    }

    public function store(RegistroOrdenioRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                $animal = Ganado::whereKey($request->integer('ganado_id'))->lockForUpdate()->firstOrFail();
                Gate::authorize('update', $animal);
                $data = $request->validated();
                if ($animal->estado !== 'Activo' || $animal->sexo !== 'Hembra' || $data['fecha'] < $animal->fecha_ingreso->toDateString()) {
                    throw ValidationException::withMessages(['ganado_id' => 'Selecciona una hembra activa y una fecha desde su ingreso.']);
                }
                if ($data['fecha'] === today()->toDateString() && empty($data['lote_historico_id'])) {
                    $data['lote_historico_id'] = $animal->lote_id;
                }
                $lote = Lote::forUser($request->user())->findOrFail($data['lote_historico_id']);
                $data['lote_historico_id'] = $lote->id;
                $animal->registrosOrdenio()->create($data);
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['numero_ordenio' => 'Ya existe un ordeño de este animal para la fecha y turno indicados.']);
        }

        return redirect()->route('ordenios.index')->with('success', 'Ordeño registrado.');
    }

    public function edit(RegistroOrdenio $ordenio): View
    {
        Gate::authorize('update', $ordenio);

        return view('ordenios.edit', ['ordenio' => $ordenio->load(['ganado', 'loteHistorico.finca'])]);
    }

    public function update(RegistroOrdenioRequest $request, RegistroOrdenio $ordenio): RedirectResponse
    {
        $ordenio->update($request->safe()->only(['litros', 'observaciones']));

        return redirect()->route('ordenios.index')->with('success', 'Ordeño corregido. Se conserva su lote histórico.');
    }

    public function destroy(RegistroOrdenio $ordenio): RedirectResponse
    {
        Gate::authorize('delete', $ordenio);
        $ordenio->delete();

        return redirect()->route('ordenios.index')->with('success', 'Ordeño eliminado.');
    }
}
