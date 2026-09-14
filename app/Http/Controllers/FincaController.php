<?php

namespace App\Http\Controllers;

use App\Http\Requests\FincaRequest;
use App\Models\Finca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FincaController extends Controller
{
    public function index(Request $request): View
    {
        return view('fincas.index', ['fincas' => Finca::forUser($request->user())->orderBy('nombre')->paginate(10)]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Finca::class);

        return view('fincas.create', []);
    }

    public function store(FincaRequest $request): RedirectResponse
    {
        $request->user()->fincas()->create($request->validated());

        return redirect()->route('fincas.index')->with('success', 'Registro creado correctamente.');
    }

    public function edit(Request $request, Finca $finca): View
    {
        Gate::authorize('update', $finca);

        return view('fincas.edit', ['finca' => $finca]);
    }

    public function update(FincaRequest $request, Finca $finca): RedirectResponse
    {
        $finca->update($request->validated());

        return redirect()->route('fincas.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Finca $finca): RedirectResponse
    {
        Gate::authorize('delete', $finca);
        $this->deleteWithoutHistory($finca, ['lotes']);

        return redirect()->route('fincas.index')->with('success', 'Registro eliminado correctamente.');
    }
}
