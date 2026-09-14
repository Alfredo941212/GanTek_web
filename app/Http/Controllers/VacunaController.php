<?php

namespace App\Http\Controllers;

use App\Http\Requests\VacunaRequest;
use App\Models\Vacuna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VacunaController extends Controller
{
    public function index(Request $request): View
    {
        return view('vacunas.index', ['vacunas' => Vacuna::orderBy('nombre')->paginate(10)]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Vacuna::class);

        return view('vacunas.create', []);
    }

    public function store(VacunaRequest $request): RedirectResponse
    {
        Vacuna::create($request->validated());

        return redirect()->route('vacunas.index')->with('success', 'Registro creado correctamente.');
    }

    public function edit(Request $request, Vacuna $vacuna): View
    {
        Gate::authorize('update', $vacuna);

        return view('vacunas.edit', ['vacuna' => $vacuna]);
    }

    public function update(VacunaRequest $request, Vacuna $vacuna): RedirectResponse
    {
        $vacuna->update($request->validated());

        return redirect()->route('vacunas.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Vacuna $vacuna): RedirectResponse
    {
        Gate::authorize('delete', $vacuna);
        $vacuna->update(['estado' => 'Inactivo']);

        return redirect()->route('vacunas.index')->with('success', 'Registro desactivado. El historial se conserva.');
    }
}
