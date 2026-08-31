<?php

namespace App\Http\Controllers;

use App\Models\Cattle;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with('cattle')->latest('sale_date')->paginate(10);
        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $cattle = Cattle::where('status', 'Disponible')->orderBy('code')->get();
        return view('sales.create', compact('cattle'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['total_amount'] = $data['weight'] * $data['price_per_kg'];

        DB::transaction(function () use ($data) {
            Sale::create($data);
            Cattle::whereKey($data['cattle_id'])->update(['status' => 'Vendido']);
        });

        return redirect()->route('ventas.index')
            ->with('success', 'Venta registrada correctamente.');
    }

    public function edit(Sale $venta)
    {
        $cattle = Cattle::orderBy('code')->get();
        return view('sales.edit', compact('venta', 'cattle'));
    }

    public function update(Request $request, Sale $venta)
    {
        $data = $this->validateData($request);
        $data['total_amount'] = $data['weight'] * $data['price_per_kg'];

        $venta->update($data);

        return redirect()->route('ventas.index')
            ->with('success', 'Venta actualizada correctamente.');
    }

    public function destroy(Sale $venta)
    {
        DB::transaction(function () use ($venta) {
            $cattleId = $venta->cattle_id;
            $venta->delete();

            if (!Sale::where('cattle_id', $cattleId)->exists()) {
                Cattle::whereKey($cattleId)->update(['status' => 'Disponible']);
            }
        });

        return redirect()->route('ventas.index')
            ->with('success', 'Venta eliminada correctamente.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'cattle_id' => ['required', 'exists:cattle,id'],
            'sale_date' => ['required', 'date'],
            'weight' => ['required', 'numeric', 'min:0.01'],
            'price_per_kg' => ['required', 'numeric', 'min:0.01'],
            'buyer' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
