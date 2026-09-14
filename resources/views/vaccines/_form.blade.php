@csrf
@php($registro = $vacunacion ?? null)
<div class="form-grid">
@if(!$registro)
@include('partials.field', ['name' => 'ganado_id', 'label' => 'Animal', 'type' => 'select', 'required' => true, 'value' => $registro?->ganado_id ?? '', 'options' => $animales->mapWithKeys(fn ($animal) => [$animal->id => $animal->arete_siniiga.' / '.$animal->nombre])])
@else
<p>Animal: {{ $registro->ganado->arete_siniiga }}</p>
@endif
@include('partials.field', ['name' => 'vacuna_id', 'label' => 'Vacuna', 'type' => 'select', 'required' => true, 'value' => $registro?->vacuna_id ?? '', 'options' => $vacunas->pluck('nombre', 'id')])
@include('partials.field', ['name' => 'veterinario_id', 'label' => 'Veterinario responsable', 'type' => 'select', 'required' => true, 'value' => $registro?->veterinario_id ?? '', 'options' => $veterinarios->pluck('nombre', 'id')])
@include('partials.field', ['name' => 'fecha_aplicacion', 'label' => 'Fecha de aplicación', 'type' => 'date', 'required' => true, 'value' => $registro?->fecha_aplicacion?->format('Y-m-d') ?? ''])
@include('partials.field', ['name' => 'proxima_aplicacion', 'label' => 'Próxima aplicación', 'type' => 'date', 'required' => false, 'value' => $registro?->proxima_aplicacion?->format('Y-m-d') ?? ''])
@include('partials.field', ['name' => 'dosis', 'label' => 'Dosis aplicada (incluye unidad)', 'type' => 'text', 'required' => true, 'value' => $registro?->dosis ?? ''])
@include('partials.field', ['name' => 'observaciones', 'label' => 'Observaciones', 'type' => 'textarea', 'required' => false, 'value' => $registro?->observaciones ?? ''])
</div>
<button class="btn primary" type="submit">Guardar</button>
<a class="btn" href="{{ route('vacunaciones.index') }}">Cancelar</a>
