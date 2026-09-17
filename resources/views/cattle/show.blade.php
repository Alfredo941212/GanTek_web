@extends('layouts.app')
@section('title', 'Historial del animal | GanTek')
@section('heading', 'Historial del animal')
@section('content')
<div class="panel detail-grid">
@foreach(['Arete' => $animal->arete_siniiga, 'Nombre' => $animal->nombre, 'Sexo' => $animal->sexo, 'Raza' => $animal->raza, 'Nacimiento' => $animal->fecha_nacimiento?->format('d/m/Y'), 'Ingreso' => $animal->fecha_ingreso->format('d/m/Y'), 'Peso inicial (kg)' => $animal->peso_inicial, 'Finca actual' => $animal->lote->finca->nombre, 'Lote actual' => $animal->lote->nombre, 'Estado' => $animal->estado] as $label => $value)
    <div><span>{{ $label }}</span><strong>{{ $value ?? '—' }}</strong></div>
@endforeach
</div>
<div class="panel"><h2>Observaciones</h2><p>{{ $animal->observaciones ?? 'Sin observaciones.' }}</p></div>
<section class="panel table-scroll">
<h2>Vacunaciones</h2>
<table><thead><tr><th>Vacuna</th><th>Veterinario</th><th>Aplicación</th><th>Próxima</th><th>Dosis</th></tr></thead><tbody>
@forelse($vacunaciones as $registro)
<tr><td>{{ $registro->vacuna->nombre }}</td><td>{{ $registro->veterinario->nombre }}</td><td>{{ $registro->fecha_aplicacion->format('d/m/Y') }}</td><td>{{ $registro->proxima_aplicacion?->format('d/m/Y') ?? '—' }}</td><td>{{ $registro->dosis }}</td></tr>
@empty
<tr><td colspan="5">Sin vacunaciones.</td></tr>
@endforelse
</tbody></table>{{ $vacunaciones->links('partials.pagination') }}
</section>
<section class="panel table-scroll">
<h2>Ordeños</h2>
<table><thead><tr><th>Fecha</th><th>Turno</th><th>Litros</th><th>Finca / lote histórico</th></tr></thead><tbody>
@forelse($ordenios as $registro)
<tr><td>{{ $registro->fecha->format('d/m/Y') }}</td><td>{{ $registro->turno }}</td><td>{{ $registro->litros }}</td><td>{{ $registro->loteHistorico->finca->nombre }} / {{ $registro->loteHistorico->nombre }}</td></tr>
@empty
<tr><td colspan="4">Sin ordeños.</td></tr>
@endforelse
</tbody></table>{{ $ordenios->links('partials.pagination') }}
</section>
<a class="btn" href="{{ route('ganado.index') }}">Volver</a>
@endsection
