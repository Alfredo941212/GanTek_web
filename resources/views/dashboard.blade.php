@extends('layouts.app')

@section('title', 'Dashboard | GanTek')
@section('heading', 'Dashboard')

@section('content')
<div class="stats-grid">
    <div class="stat-card"><span>Total ganado</span><strong>{{ $stats['total_cattle'] }}</strong></div>
    <div class="stat-card"><span>Disponibles</span><strong>{{ $stats['available_cattle'] }}</strong></div>
    <div class="stat-card"><span>Vendidos</span><strong>{{ $stats['sold_cattle'] }}</strong></div>
    <div class="stat-card"><span>Vacunas aplicadas</span><strong>{{ $stats['applied_vaccines'] }}</strong></div>
    <div class="stat-card"><span>Próximas vacunas</span><strong>{{ $stats['upcoming_vaccines'] }}</strong></div>
    <div class="stat-card"><span>Ingresos</span><strong>${{ number_format($stats['total_sales'], 2) }}</strong></div>
</div>

<div class="two-columns">
    <section class="panel">
        <div class="panel-head"><h2>Ganado reciente</h2></div>
        <table>
            <thead><tr><th>Código</th><th>Sexo</th><th>Estado</th></tr></thead>
            <tbody>
            @forelse($recentCattle as $item)
                <tr>
                    <td>{{ $item->code }}</td>
                    <td>{{ $item->sex }}</td>
                    <td>{{ $item->status }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Sin registros.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Ventas recientes</h2></div>
        <table>
            <thead><tr><th>Animal</th><th>Fecha</th><th>Total</th></tr></thead>
            <tbody>
            @forelse($recentSales as $sale)
                <tr>
                    <td>{{ $sale->cattle->code }}</td>
                    <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                    <td>${{ number_format($sale->total_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Sin ventas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
