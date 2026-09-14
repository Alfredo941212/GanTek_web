@csrf
@php($registro = $vacuna ?? null)
<div class="form-grid">
@include('partials.field', ['name' => 'nombre', 'label' => 'Nombre', 'type' => 'text', 'required' => true, 'value' => $registro?->nombre ?? ''])
@include('partials.field', ['name' => 'fabricante', 'label' => 'Fabricante', 'type' => 'text', 'required' => false, 'value' => $registro?->fabricante ?? ''])
@include('partials.field', ['name' => 'descripcion', 'label' => 'Descripción', 'type' => 'textarea', 'required' => false, 'value' => $registro?->descripcion ?? ''])
@include('partials.field', ['name' => 'dosis_recomendada', 'label' => 'Dosis recomendada (incluye unidad)', 'type' => 'text', 'required' => false, 'value' => $registro?->dosis_recomendada ?? ''])
@include('partials.field', ['name' => 'intervalo_dias', 'label' => 'Intervalo de referencia (días)', 'type' => 'number', 'required' => false, 'value' => $registro?->intervalo_dias ?? '', 'min' => 1, 'max' => 3650])
@include('partials.field', ['name' => 'estado', 'label' => 'Estado', 'type' => 'select', 'required' => true, 'value' => $registro?->estado ?? '', 'options' => ['Activo' => 'Activo', 'Inactivo' => 'Inactivo']])
</div>
<button class="btn primary" type="submit">Guardar</button>
<a class="btn" href="{{ route('vacunas.index') }}">Cancelar</a>
