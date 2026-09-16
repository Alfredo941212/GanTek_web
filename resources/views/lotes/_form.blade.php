@csrf
@php($registro = $lote ?? null)
<div class="form-grid">
@if(!$registro)
@include('partials.field', ['name' => 'finca_id', 'label' => 'Finca', 'type' => 'select', 'required' => true, 'value' => $registro?->finca_id ?? '', 'options' => $fincas->pluck('nombre', 'id')])
@else
<p>Finca: {{ $registro->finca->nombre }}. La finca del lote se conserva.</p>
@endif
@include('partials.field', ['name' => 'nombre', 'label' => 'Nombre', 'type' => 'text', 'required' => true, 'value' => $registro?->nombre ?? ''])
@include('partials.field', ['name' => 'descripcion', 'label' => 'Descripción', 'type' => 'textarea', 'required' => false, 'value' => $registro?->descripcion ?? ''])
@include('partials.field', ['name' => 'estado', 'label' => 'Estado', 'type' => 'select', 'required' => true, 'value' => $registro?->estado ?? '', 'options' => ['Activo' => 'Activo', 'Inactivo' => 'Inactivo']])
</div>
<button class="btn primary" type="submit">Guardar</button>
<a class="btn" href="{{ route('lotes.index') }}">Cancelar</a>
