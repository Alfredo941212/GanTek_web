<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoteRequest;
use App\Models\Finca;
use App\Models\Lote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LoteController extends Controller
{
    public function index(Request $request): View
    {
        return view('lotes.index', ['lotes' => Lote::forUser($request->user())->with('finca')->orderBy('nombre')->paginate(10)]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Lote::class);

        return view('lotes.create', ['fincas' => Finca::forUser($request->user())->orderBy('nombre')->get()]);
    }

    public function store(LoteRequest $request): RedirectResponse
    {
        Lote::create($request->validated());

        return redirect()->route('lotes.index')->with('success', 'Registro creado correctamente.');
    }

    public function edit(Request $request, Lote $lote): View
    {
        Gate::authorize('update', $lote);

        return view('lotes.edit', ['lote' => $lote, 'fincas' => Finca::forUser($request->user())->orderBy('nombre')->get()]);
    }

    public function update(LoteRequest $request, Lote $lote): RedirectResponse
    {
        $lote->update($request->safe()->only(['nombre', 'descripcion', 'estado']));

        return redirect()->route('lotes.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Lote $lote): RedirectResponse
    {
        Gate::authorize('delete', $lote);
        $this->deleteWithoutHistory($lote, ['ganado', 'registrosOrdenioHistoricos']);

        return redirect()->route('lotes.index')->with('success', 'Registro eliminado correctamente.');
    }
}
