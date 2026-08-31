<?php

namespace App\Http\Controllers;

use App\Models\Cattle;
use Illuminate\Http\Request;

class CattleController extends Controller
{
    public function index(Request $request)
    {
        $query = Cattle::query();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('breed', 'like', "%{$search}%");
            });
        }

        $cattle = $query->latest()->paginate(10)->withQueryString();

        return view('cattle.index', compact('cattle'));
    }

    public function create()
    {
        return view('cattle.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateCattle($request);
        Cattle::create($data);

        return redirect()->route('ganado.index')
            ->with('success', 'Animal registrado correctamente.');
    }

    public function show(Cattle $ganado)
    {
        $ganado->load(['vaccines', 'sales']);
        return view('cattle.show', ['cattle' => $ganado]);
    }

    public function edit(Cattle $ganado)
    {
        return view('cattle.edit', ['cattle' => $ganado]);
    }

    public function update(Request $request, Cattle $ganado)
    {
        $data = $this->validateCattle($request, $ganado->id);
        $ganado->update($data);

        return redirect()->route('ganado.index')
            ->with('success', 'Animal actualizado correctamente.');
    }

    public function destroy(Cattle $ganado)
    {
        $ganado->delete();

        return redirect()->route('ganado.index')
            ->with('success', 'Animal eliminado correctamente.');
    }

    private function validateCattle(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:cattle,code,' . $id],
            'name' => ['nullable', 'string', 'max:100'],
            'sex' => ['required', 'in:Macho,Hembra'],
            'breed' => ['nullable', 'string', 'max:100'],
            'entry_date' => ['required', 'date'],
            'initial_weight' => ['nullable', 'numeric', 'min:0'],
            'lot' => ['nullable', 'string', 'max:100'],
            'corral' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:Disponible,Vendido'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
