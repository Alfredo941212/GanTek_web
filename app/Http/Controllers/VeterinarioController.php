<?php

namespace App\Http\Controllers;

use App\Http\Requests\VeterinarioRequest;
use App\Models\Veterinario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VeterinarioController extends Controller
{
    public function index(Request $request): View
    {
        return view('veterinarios.index', ['veterinarios' => Veterinario::orderBy('nombre')->paginate(10)]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Veterinario::class);

        return view('veterinarios.create', []);
    }

    public function store(VeterinarioRequest $request): RedirectResponse
    {
        Veterinario::create($request->validated());

        return redirect()->route('veterinarios.index')->with('success', 'Registro creado correctamente.');
    }

    public function edit(Request $request, Veterinario $veterinario): View
    {
        Gate::authorize('update', $veterinario);

        return view('veterinarios.edit', ['veterinario' => $veterinario]);
    }

    public function update(VeterinarioRequest $request, Veterinario $veterinario): RedirectResponse
    {
        $veterinario->update($request->validated());

        return redirect()->route('veterinarios.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Veterinario $veterinario): RedirectResponse
    {
        Gate::authorize('delete', $veterinario);
        $veterinario->update(['estado' => 'Inactivo']);

        return redirect()->route('veterinarios.index')->with('success', 'Registro desactivado. El historial se conserva.');
    }
}
