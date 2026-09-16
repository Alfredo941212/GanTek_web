<?php

namespace App\Http\Controllers;

use App\Http\Requests\VacunacionRequest;
use App\Models\Ganado;
use App\Models\Vacuna;
use App\Models\Vacunacion;
use App\Models\Veterinario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VacunacionController extends Controller
{
    public function index(Request $request): View
    {
        return view('vaccines.index', ['vacunaciones' => Vacunacion::forUser($request->user())->with(['ganado', 'vacuna', 'veterinario'])->latest('fecha_aplicacion')->paginate(10)]);
    }

    public function create(Request $request): View
    {
        return view('vaccines.create', $this->opciones($request));
    }

    public function store(VacunacionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $animal = Ganado::whereKey($request->integer('ganado_id'))->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $animal);
            if ($animal->estado !== 'Activo' || $request->input('fecha_aplicacion') < $animal->fecha_ingreso->toDateString()) {
                throw ValidationException::withMessages(['ganado_id' => 'El animal debe estar activo y la aplicación no puede preceder a su ingreso.']);
            }
            $animal->vacunaciones()->create($request->safe()->except('ganado_id'));
        });

        return redirect()->route('vacunaciones.index')->with('success', 'Vacunación registrada con su veterinario responsable.');
    }

    public function edit(Request $request, Vacunacion $vacunacion): View
    {
        Gate::authorize('update', $vacunacion);

        return view('vaccines.edit', ['vacunacion' => $vacunacion->load('ganado')] + $this->opciones($request, $vacunacion));
    }

    public function update(VacunacionRequest $request, Vacunacion $vacunacion): RedirectResponse
    {
        DB::transaction(function () use ($request, $vacunacion): void {
            $animal = Ganado::whereKey($vacunacion->ganado_id)->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $animal);
            if ($request->input('fecha_aplicacion') < $animal->fecha_ingreso->toDateString()) {
                throw ValidationException::withMessages(['fecha_aplicacion' => 'La aplicación no puede preceder al ingreso del animal.']);
            }
            $vacunacion->update($request->safe()->except('ganado_id'));
        });

        return redirect()->route('vacunaciones.index')->with('success', 'Vacunación actualizada.');
    }

    public function destroy(Vacunacion $vacunacion): RedirectResponse
    {
        Gate::authorize('delete', $vacunacion);
        $vacunacion->delete();

        return redirect()->route('vacunaciones.index')->with('success', 'Aplicación eliminada.');
    }

    /** @return array{animales: mixed, vacunas: mixed, veterinarios: mixed} */
    private function opciones(Request $request, ?Vacunacion $vacunacion = null): array
    {
        return [
            'animales' => Ganado::forUser($request->user())->where('estado', 'Activo')->orderBy('arete_siniiga')->get(),
            'vacunas' => Vacuna::where(fn (Builder $query) => $query->where('estado', 'Activo')->orWhereKey($vacunacion?->vacuna_id))->orderBy('nombre')->get(),
            'veterinarios' => Veterinario::where(fn (Builder $query) => $query->where('estado', 'Activo')->orWhereKey($vacunacion?->veterinario_id))->orderBy('nombre')->get(),
        ];
    }
}
