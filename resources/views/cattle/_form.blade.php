@csrf
<p class="form-intro">Los campos con * son obligatorios. Revisa los datos antes de guardar.</p>
@php($registro = $animal ?? null)
<div class="form-grid">
    @include('partials.field', [
        'name' => 'lote_id',
        'label' => 'Lote actual',
        'type' => 'select',
        'required' => true,
        'value' => $registro?->lote_id ?? '',
        'options' => $lotes->mapWithKeys(fn($lote) => [$lote->id => $lote->finca->nombre . ' / ' . $lote->nombre]),
    ])
    @include('partials.field', [
        'name' => 'arete_siniiga',
        'label' => 'Arete SINIIGA',
        'type' => 'text',
        'required' => true,
        'value' => $registro?->arete_siniiga ?? '',
    ])
    @include('partials.field', [
        'name' => 'nombre',
        'label' => 'Nombre',
        'type' => 'text',
        'required' => false,
        'value' => $registro?->nombre ?? '',
    ])
    @include('partials.field', [
        'name' => 'sexo',
        'label' => 'Sexo',
        'type' => 'select',
        'required' => true,
        'value' => $registro?->sexo ?? '',
        'options' => ['Macho' => 'Macho', 'Hembra' => 'Hembra'],
    ])
    @include('partials.field', [
        'name' => 'raza',
        'label' => 'Raza',
        'type' => 'text',
        'required' => false,
        'value' => $registro?->raza ?? '',
    ])
    @include('partials.field', [
        'name' => 'fecha_nacimiento',
        'label' => 'Fecha de nacimiento',
        'type' => 'date',
        'required' => false,
        'value' => $registro?->fecha_nacimiento?->format('Y-m-d') ?? '',
    ])
    @include('partials.field', [
        'name' => 'fecha_ingreso',
        'label' => 'Fecha de ingreso',
        'type' => 'date',
        'required' => true,
        'value' => $registro?->fecha_ingreso?->format('Y-m-d') ?? today()->toDateString(),
    ])
    @include('partials.field', [
        'name' => 'peso_inicial',
        'label' => 'Peso inicial (kg)',
        'type' => 'number',
        'required' => false,
        'value' => $registro?->peso_inicial ?? '',
        'step' => '0.01',
        'min' => 0,
    ])
    @include('partials.field', [
        'name' => 'estado_productivo',
        'label' => 'Estado productivo',
        'type' => 'select',
        'required' => true,
        'value' => $registro?->estado_productivo ?? 'En producción',
        'options' => [
            'En producción' => 'En producción',
            'Seca' => 'Seca',
            'Gestante' => 'Gestante',
            'No aplica' => 'No aplica',
        ],
    ])

    @include('partials.field', [
        'name' => 'produccion_minima_diaria',
        'label' => 'Producción mínima diaria (L)',
        'type' => 'number',
        'required' => true,
        'value' => $registro?->produccion_minima_diaria ?? '4.00',
        'step' => '0.01',
        'min' => '0.01',
    ])
    @include('partials.field', [
        'name' => 'estado',
        'label' => 'Estado',
        'type' => 'select',
        'required' => true,
        'value' => $registro?->estado ?? '',
        'options' => ['Activo' => 'Activo', 'Vendido' => 'Vendido', 'Fallecido' => 'Fallecido', 'Baja' => 'Baja'],
    ])
    @include('partials.field', [
        'name' => 'observaciones',
        'label' => 'Observaciones',
        'type' => 'textarea',
        'required' => false,
        'value' => $registro?->observaciones ?? '',
    ])
</div>
<div class="form-actions"><button class="btn primary" type="submit">{{ $registro ? 'Guardar cambios' : 'Guardar registro' }}</button>
<a class="btn" href="{{ route('ganado.index') }}">Cancelar</a>
</div>
