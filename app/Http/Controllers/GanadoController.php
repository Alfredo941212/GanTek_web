<?php

namespace App\Http\Controllers;

use App\Http\Requests\GanadoRequest;
use App\Models\Ganado;
use App\Models\Lote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GanadoController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string', 'max:150']]);
        $query = Ganado::forUser($request->user())
            ->where('estado', 'Activo')
            ->with('lote.finca');
        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(fn (Builder $query) => $query->where('arete_siniiga', 'like', $search)->orWhere('nombre', 'like', $search)->orWhere('raza', 'like', $search));
        }

        return view('cattle.index', ['ganado' => $query->latest()->paginate(10)->withQueryString()]);
    }

    public function create(Request $request): View
    {
        return view('cattle.create', ['lotes' => Lote::forUser($request->user())->where('estado', 'Activo')->with('finca')->get()]);
    }

    public function store(GanadoRequest $request): RedirectResponse
    {
        Ganado::create($request->validated());

        return redirect()->route('ganado.index')->with('success', 'Animal registrado correctamente.');
    }

    public function show(Ganado $ganado): View
    {
        Gate::authorize('view', $ganado);

        return view('cattle.show', [
            'animal' => $ganado->load('lote.finca'),
            'vacunaciones' => $ganado->vacunaciones()->with(['vacuna', 'veterinario'])->latest('fecha_aplicacion')->paginate(10, ['*'], 'vacunaciones_page'),
            'ordenios' => $ganado->registrosOrdenio()->with('loteHistorico.finca')->latest('fecha')->paginate(14, ['*'], 'ordenios_page'),
        ]);
    }

    public function edit(Request $request, Ganado $ganado): View
    {
        Gate::authorize('update', $ganado);

        return view('cattle.edit', ['animal' => $ganado, 'lotes' => Lote::forUser($request->user())->with('finca')
            ->where(fn (Builder $query) => $query->where('estado', 'Activo')->orWhereKey($ganado->lote_id))->get()]);
    }

    public function update(GanadoRequest $request, Ganado $ganado): RedirectResponse
    {
        DB::transaction(function () use ($request, $ganado): void {
            $animal = Ganado::whereKey($ganado->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $animal);
            if ($request->input('sexo') !== 'Hembra' && $animal->registrosOrdenio()->exists()) {
                throw ValidationException::withMessages(['sexo' => 'Un animal con ordeños registrados debe conservar el sexo Hembra.']);
            }
            if (
                $animal->registrosOrdenio()->where('fecha', '<', $request->input('fecha_ingreso'))->exists()
                || $animal->vacunaciones()->where('fecha_aplicacion', '<', $request->input('fecha_ingreso'))->exists()
            ) {
                throw ValidationException::withMessages(['fecha_ingreso' => 'La fecha de ingreso no puede ser posterior a su historial.']);
            }
            $animal->update($request->validated());
        });

        return redirect()->route('ganado.index')->with('success', 'Animal actualizado. Su historial se conserva.');
    }

    public function destroy(Ganado $ganado): RedirectResponse
    {
        Gate::authorize('delete', $ganado);
        $ganado->update(['estado' => 'Baja']);

        return redirect()->route('ganado.index')->with('success', 'Animal dado de baja. Su historial se conserva.');
    }
}
