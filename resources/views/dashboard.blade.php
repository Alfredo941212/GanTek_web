@extends('layouts.app')
@section('title', 'Dashboard | GanTek')
@section('heading', 'Dashboard')
@section('content')
<div class="stats-grid">
    <div class="stat-card"><span>Animales activos</span><strong>{{ $activos }}</strong></div>
    <div class="stat-card"><span>Producción de hoy</span><strong>{{ number_format($litrosHoy, 2) }} L</strong></div>
    <div class="stat-card"><span>Promedio diario · últimos 7 días</span><strong>{{ number_format($promedio, 2) }} L</strong><small>{{ $diasPromedio }} días con registros; excluye hoy</small></div>
    <div class="stat-card"><span>Vacunas próximas · 30 días</span><strong>{{ $proximas->count() }}</strong></div>
    <div class="stat-card"><span>Alertas de producción</span><strong>{{ $alertasProduccion->count() }}</strong></div>
    <div class="stat-card"><span>Vacunas vencidas</span><strong>{{ $vencidas->count() }}</strong></div>
</div>
<div class="toolbar"><a class="btn primary" href="{{ route('ordenios.create') }}">Registrar ordeño</a><a class="btn" href="{{ route('produccion.index') }}">Consultar producción</a></div>
<div class="two-columns">
<section class="panel">
<h2>Ganado reciente</h2>
<table><thead><tr><th>Animal</th><th>Lote</th><th>Estado</th></tr></thead><tbody>
@forelse($animales as $animal)
<tr><td><a href="{{ route('ganado.show', $animal) }}">{{ $animal->arete_siniiga }}</a></td><td>{{ $animal->lote->nombre }}</td><td>{{ $animal->estado }}</td></tr>
@empty
<tr><td colspan="3">Sin animales.</td></tr>
@endforelse
</tbody></table>
</section>
<section class="panel">
<h2>Disminución de producción</h2>
<p>Últimos 3 días completos frente a los 7 anteriores. Se requieren ambos turnos cada día.</p>
@forelse($alertasProduccion as $alerta)
<div class="alert error">
<a href="{{ route('ganado.show', $alerta->id) }}">{{ $alerta->arete_siniiga }}</a>:
{{ number_format($alerta->reciente, 2) }} L/día frente a {{ number_format($alerta->referencia, 2) }} L/día.
<strong>Revisa el estado del animal.</strong>
</div>
@empty
<p>No hay alertas con los registros suficientes disponibles.</p>
@endforelse
</section>
<section class="panel">
<h2>Próxima vacunación</h2>
@forelse($proximas as $registro)
<p><a href="{{ route('ganado.show', $registro->ganado) }}">{{ $registro->ganado->arete_siniiga }}</a> · {{ $registro->vacuna->nombre }} · {{ $registro->proxima_aplicacion->format('d/m/Y') }}</p>
@empty
<p>Sin próximas aplicaciones en los siguientes 30 días.</p>
@endforelse
</section>
<section class="panel">
<h2>Vacunación vencida</h2>
@forelse($vencidas as $registro)
<p><a href="{{ route('ganado.show', $registro->ganado) }}">{{ $registro->ganado->arete_siniiga }}</a> · {{ $registro->vacuna->nombre }} · {{ $registro->proxima_aplicacion->format('d/m/Y') }}</p>
@empty
<p>Sin aplicaciones vencidas.</p>
@endforelse
</section>
</div>
@endsection
