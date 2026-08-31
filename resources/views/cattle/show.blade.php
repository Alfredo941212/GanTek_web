@extends('layouts.app')
@section('title', 'Detalle del animal | GanTek')
@section('heading', 'Detalle del animal')

@section('content')
<div class="panel detail-grid">
    <div><span>Código</span><strong>{{ $cattle->code }}</strong></div>
    <div><span>Nombre</span><strong>{{ $cattle->name ?? '—' }}</strong></div>
    <div><span>Sexo</span><strong>{{ $cattle->sex }}</strong></div>
    <div><span>Raza</span><strong>{{ $cattle->breed ?? '—' }}</strong></div>
    <div><span>Ingreso</span><strong>{{ $cattle->entry_date->format('d/m/Y') }}</strong></div>
    <div><span>Peso</span><strong>{{ $cattle->initial_weight ?? '—' }} kg</strong></div>
    <div><span>Lote</span><strong>{{ $cattle->lot ?? '—' }}</strong></div>
    <div><span>Corral</span><strong>{{ $cattle->corral ?? '—' }}</strong></div>
    <div><span>Estado</span><strong>{{ $cattle->status }}</strong></div>
</div>

<div class="panel">
    <h2>Observaciones</h2>
    <p>{{ $cattle->observations ?? 'Sin observaciones.' }}</p>
</div>
@endsection
