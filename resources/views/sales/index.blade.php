@extends('layouts.app')
@section('title', 'Ventas | GanTek')
@section('heading', 'Ventas')

@section('content')
<div class="toolbar">
    <div></div>
    <a class="btn primary" href="{{ route('ventas.create') }}">+ Registrar venta</a>
</div>

<div class="panel">
<table>
<thead><tr><th>Animal</th><th>Fecha</th><th>Peso</th><th>Precio/kg</th><th>Total</th><th>Comprador</th><th>Acciones</th></tr></thead>
<tbody>
@forelse($sales as $sale)
<tr>
    <td>{{ $sale->cattle->code }}</td>
    <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
    <td>{{ $sale->weight }} kg</td>
    <td>${{ number_format($sale->price_per_kg, 2) }}</td>
    <td>${{ number_format($sale->total_amount, 2) }}</td>
    <td>{{ $sale->buyer ?? '—' }}</td>
    <td class="actions">
        <a href="{{ route('ventas.edit', $sale) }}">Editar</a>
        <form method="POST" action="{{ route('ventas.destroy', $sale) }}">
            @csrf @method('DELETE')
            <button type="submit">Eliminar</button>
        </form>
    </td>
</tr>
@empty
<tr><td colspan="7">No hay ventas registradas.</td></tr>
@endforelse
</tbody>
</table>
</div>
{{ $sales->links() }}
@endsection
