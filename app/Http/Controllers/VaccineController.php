<?php

namespace App\Http\Controllers;

use App\Models\Cattle;
use App\Models\Vaccine;
use Illuminate\Http\Request;

class VaccineController extends Controller
{
    public function index()
    {
        $vaccines = Vaccine::with('cattle')->latest('application_date')->paginate(10);
        return view('vaccines.index', compact('vaccines'));
    }

    public function create()
    {
        $cattle = Cattle::where('status', 'Disponible')->orderBy('code')->get();
        return view('vaccines.create', compact('cattle'));
    }

    public function store(Request $request)
    {
        Vaccine::create($this->validateData($request));

        return redirect()->route('vacunas.index')
            ->with('success', 'Vacuna registrada correctamente.');
    }

    public function edit(Vaccine $vacuna)
    {
        $cattle = Cattle::orderBy('code')->get();
        return view('vaccines.edit', compact('vacuna', 'cattle'));
    }

    public function update(Request $request, Vaccine $vacuna)
    {
        $vacuna->update($this->validateData($request));

        return redirect()->route('vacunas.index')
            ->with('success', 'Vacuna actualizada correctamente.');
    }

    public function destroy(Vaccine $vacuna)
    {
        $vacuna->delete();

        return redirect()->route('vacunas.index')
            ->with('success', 'Registro de vacuna eliminado.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'cattle_id' => ['required', 'exists:cattle,id'],
            'vaccine_name' => ['required', 'string', 'max:150'],
            'application_date' => ['required', 'date'],
            'next_date' => ['nullable', 'date', 'after_or_equal:application_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
