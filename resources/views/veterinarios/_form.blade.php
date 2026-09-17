@csrf
<p class="form-intro">Los campos con * son obligatorios. Revisa los datos antes de guardar.</p>
@php($registro = $veterinario ?? null)
<div class="form-grid">
@include('partials.field', ['name' => 'nombre', 'label' => 'Nombre', 'type' => 'text', 'required' => true, 'value' => $registro?->nombre ?? ''])
@include('partials.field', ['name' => 'cedula_profesional', 'label' => 'Cédula profesional', 'type' => 'text', 'required' => true, 'value' => $registro?->cedula_profesional ?? ''])
@include('partials.field', ['name' => 'telefono', 'label' => 'Teléfono', 'type' => 'tel', 'required' => false, 'value' => $registro?->telefono ?? ''])
@include('partials.field', ['name' => 'correo', 'label' => 'Correo', 'type' => 'email', 'required' => false, 'value' => $registro?->correo ?? ''])
@include('partials.field', ['name' => 'especialidad', 'label' => 'Especialidad', 'type' => 'text', 'required' => false, 'value' => $registro?->especialidad ?? ''])
@include('partials.field', ['name' => 'estado', 'label' => 'Estado', 'type' => 'select', 'required' => true, 'value' => $registro?->estado ?? '', 'options' => ['Activo' => 'Activo', 'Inactivo' => 'Inactivo']])
@include('partials.field', ['name' => 'observaciones', 'label' => 'Observaciones', 'type' => 'textarea', 'required' => false, 'value' => $registro?->observaciones ?? ''])
</div>
<div class="form-actions"><button class="btn primary" type="submit">{{ $registro ? 'Guardar cambios' : 'Guardar registro' }}</button>
<a class="btn" href="{{ route('veterinarios.index') }}">Cancelar</a>
</div>
