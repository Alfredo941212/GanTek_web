@extends('layouts.app')
@section('title', 'Producción | GanTek')
@section('heading', 'Producción e historial')
@section('content')
<form method="GET" class="panel">
<div class="form-grid">
@include('partials.field', ['name' => 'periodo', 'label' => 'Período', 'type' => 'select', 'value' => $periodo, 'options' => ['diario' => 'Hoy', 'semanal' => 'Semana actual', 'mensual' => 'Mes actual', 'personalizado' => 'Personalizado']])
@include('partials.field', ['name' => 'desde', 'label' => 'Desde (personalizado)', 'type' => 'date', 'value' => $filtros['desde']])
@include('partials.field', ['name' => 'hasta', 'label' => 'Hasta (personalizado)', 'type' => 'date', 'value' => $filtros['hasta']])
@include('partials.field', ['name' => 'finca_id', 'label' => 'Finca histórica', 'type' => 'select', 'value' => $filtros['finca_id'] ?? '', 'options' => $fincas->pluck('nombre', 'id')])
@include('partials.field', ['name' => 'lote_id', 'label' => 'Lote histórico', 'type' => 'select', 'value' => $filtros['lote_id'] ?? '', 'options' => $lotes->mapWithKeys(fn ($lote) => [$lote->id => $lote->finca->nombre.' / '.$lote->nombre])])
@include('partials.field', ['name' => 'ganado_id', 'label' => 'Animal', 'type' => 'select', 'value' => $filtros['ganado_id'] ?? '', 'options' => $animales->pluck('arete_siniiga', 'id')])
</div>
<button class="btn primary" type="submit">Consultar</button>
<a class="btn" href="{{ route('produccion.index') }}">Restablecer</a>
</form>
<p>Período: {{ $filtros['desde'] }} al {{ $filtros['hasta'] }}. Los lotes y fincas corresponden al momento del ordeño.</p>
<div class="stats-grid">
<div class="stat-card"><span>Producción del período</span><strong>{{ number_format($resumen['total'], 2) }} L</strong></div>
<div class="stat-card"><span>Promedio por día registrado</span><strong>{{ number_format($resumen['promedio'], 2) }} L</strong></div>
<div class="stat-card"><span>Días con registros</span><strong>{{ $resumen['dias'] }}</strong></div>
</div>
<p>El promedio considera días con algún ordeño registrado; los días sin captura no se cuentan como cero. Hoy puede estar incompleto.</p>
<div class="two-columns">
<section class="panel"><h2>Por día</h2><table><thead><tr><th>Fecha</th><th>Litros</th></tr></thead><tbody>
@forelse($resumen['diaria'] as $fila)<tr><td>{{ $fila->fecha->format('d/m/Y') }}</td><td>{{ number_format($fila->total, 2) }}</td></tr>
@empty<tr><td colspan="2">Sin registros.</td></tr>@endforelse
</tbody></table></section>
<section class="panel"><h2>Por animal</h2><table><thead><tr><th>Animal</th><th>Litros</th></tr></thead><tbody>
@forelse($resumen['animales'] as $fila)<tr><td><a href="{{ route('ganado.show', $fila->ganado) }}">{{ $fila->ganado->arete_siniiga }}</a></td><td>{{ number_format($fila->total, 2) }}</td></tr>
@empty<tr><td colspan="2">Sin registros.</td></tr>@endforelse
</tbody></table></section>
<section class="panel"><h2>Por lote histórico</h2><table><thead><tr><th>Finca / lote</th><th>Litros</th></tr></thead><tbody>
@forelse($resumen['lotes'] as $fila)<tr><td>{{ $fila->loteHistorico->finca->nombre }} / {{ $fila->loteHistorico->nombre }}</td><td>{{ number_format($fila->total, 2) }}</td></tr>
@empty<tr><td colspan="2">Sin registros.</td></tr>@endforelse
</tbody></table></section>
<section class="panel"><h2>Por finca histórica</h2><table><thead><tr><th>Finca</th><th>Litros</th></tr></thead><tbody>
@forelse($resumen['fincas'] as $fila)<tr><td>{{ $fila->nombre }}</td><td>{{ number_format($fila->total, 2) }}</td></tr>
@empty<tr><td colspan="2">Sin registros.</td></tr>@endforelse
</tbody></table></section>
</div>
@endsection
