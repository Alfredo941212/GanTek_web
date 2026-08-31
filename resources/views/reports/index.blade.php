@extends('layouts.app')
@section('title', 'Reportes | GanTek')
@section('heading', 'Reportes')

@section('content')
<div class="stats-grid">
    <div class="stat-card"><span>Total ganado</span><strong>{{ $report['total_cattle'] }}</strong></div>
    <div class="stat-card"><span>Disponibles</span><strong>{{ $report['available_cattle'] }}</strong></div>
    <div class="stat-card"><span>Vendidos</span><strong>{{ $report['sold_cattle'] }}</strong></div>
    <div class="stat-card"><span>Ventas completadas</span><strong>{{ $report['completed_sales'] }}</strong></div>
    <div class="stat-card"><span>Total vendido</span><strong>${{ number_format($report['total_sales_amount'], 2) }}</strong></div>
    <div class="stat-card"><span>Peso vendido</span><strong>{{ number_format($report['total_sold_weight'], 2) }} kg</strong></div>
    <div class="stat-card"><span>Promedio $/kg</span><strong>${{ number_format($report['average_price_per_kg'], 2) }}</strong></div>
    <div class="stat-card"><span>Vacunas aplicadas</span><strong>{{ $report['applied_vaccines'] }}</strong></div>
    <div class="stat-card"><span>Vacunas próximas</span><strong>{{ $report['upcoming_vaccines'] }}</strong></div>
    <div class="stat-card"><span>Vacunas vencidas</span><strong>{{ $report['overdue_vaccines'] }}</strong></div>
</div>
@endsection
