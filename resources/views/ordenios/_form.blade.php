@csrf
@php($registro = $ordenio ?? null)
<div class="form-grid">
    @if (!$registro)
        @include('partials.field', [
            'name' => 'ganado_id',
            'label' => 'Hembra',
            'type' => 'select',
            'required' => true,
            'value' => $registro?->ganado_id ?? '',
            'options' => $animales->mapWithKeys(
                fn($animal) => [
                    $animal->id =>
                        $animal->arete_siniiga .
                        ' / ' .
                        $animal->lote->finca->nombre .
                        ' / ' .
                        $animal->lote->nombre,
                ]),
        ])
        @include('partials.field', [
            'name' => 'fecha',
            'label' => 'Fecha',
            'type' => 'date',
            'required' => true,
            'value' => $registro?->fecha?->format('Y-m-d') ?? '',
        ])
        @include('partials.field', [
            'name' => 'numero_ordenio',
            'label' => 'Número de ordeño',
            'type' => 'number',
            'required' => true,
            'value' => $registro?->numero_ordenio ?? '1',
            'min' => '1',
            'max' => '20',
            'step' => '1',
        ])
        @include('partials.field', [
            'name' => 'turno',
            'label' => 'Turno',
            'type' => 'select',
            'required' => false,
            'value' => $registro?->turno ?? '',
            'options' => ['Mañana' => 'Mañana', 'Tarde' => 'Tarde'],
        ])
        @include('partials.field', [
            'name' => 'lote_historico_id',
            'label' => 'Lote al momento del ordeño',
            'type' => 'select',
            'required' => false,
            'value' => $registro?->lote_historico_id ?? '',
            'options' => $lotes->mapWithKeys(
                fn($lote) => [$lote->id => $lote->finca->nombre . ' / ' . $lote->nombre]),
        ])
        <p class="full-row">Deja el lote vacío solo para un ordeño de hoy realizado en el lote actual. Si ocurrió antes
            de un traslado, incluso hoy, selecciona el lote donde se realizó. Para fechas anteriores es obligatorio
            confirmarlo. Un traslado posterior no cambiará este registro.</p>
    @else
        <p class="full-row">{{ $registro->ganado->arete_siniiga }} · {{ $registro->fecha->format('d/m/Y') }} · 
            . Ordeño #{{ $registro->numero_ordenio }}
            {{ $registro->turno }} · {{ $registro->loteHistorico->finca->nombre }} /
            {{ $registro->loteHistorico->nombre }}</p>
        <p class="full-row">Puedes corregir litros y observaciones. Si el animal, fecha, número de oreño o turno son incorrectos, elimina
            el registro y vuelve a capturarlo.</p>
    @endif
    @include('partials.field', [
        'name' => 'litros',
        'label' => 'Litros',
        'type' => 'number',
        'required' => true,
        'value' => $registro?->litros ?? '',
        'step' => '0.01',
        'min' => '0.01',
        'max' => '999999.99',
    ])
    @include('partials.field', [
        'name' => 'observaciones',
        'label' => 'Observaciones',
        'type' => 'textarea',
        'required' => false,
        'value' => $registro?->observaciones ?? '',
    ])
</div>
<button class="btn primary" type="submit">Guardar</button>
<a class="btn" href="{{ route('ordenios.index') }}">Cancelar</a>
