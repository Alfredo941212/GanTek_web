@csrf
<p class="form-intro">Los campos con * son obligatorios. Revisa los datos antes de guardar.</p>
@php($registro = $finca ?? null)
<div class="form-grid">
@include('partials.field', ['name' => 'nombre', 'label' => 'Nombre', 'type' => 'text', 'required' => true, 'value' => $registro?->nombre ?? ''])
@include('partials.field', ['name' => 'municipio', 'label' => 'Municipio', 'type' => 'text', 'required' => true, 'value' => $registro?->municipio ?? ''])
@include('partials.field', ['name' => 'localidad', 'label' => 'Localidad', 'type' => 'text', 'required' => false, 'value' => $registro?->localidad ?? ''])
@include('partials.field', ['name' => 'estado', 'label' => 'Estado de la República', 'type' => 'text', 'required' => true, 'value' => $registro?->estado ?? ''])
@include('partials.field', ['name' => 'superficie', 'label' => 'Superficie (ha)', 'type' => 'number', 'required' => false, 'value' => $registro?->superficie ?? '', 'step' => '0.01', 'min' => 0])
@include('partials.field', ['name' => 'observaciones', 'label' => 'Observaciones', 'type' => 'textarea', 'required' => false, 'value' => $registro?->observaciones ?? ''])
</div>
<div class="form-actions"><button class="btn primary" type="submit">{{ $registro ? 'Guardar cambios' : 'Guardar registro' }}</button>
<a class="btn" href="{{ route('fincas.index') }}">Cancelar</a>
</div>
