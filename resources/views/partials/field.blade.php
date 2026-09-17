@php
    $fieldValue = old($name, $value ?? '');
    $fieldHint = $hint ?? match ($name) {
        'arete_siniiga' => 'Identificador único. Hasta 50 caracteres; respeta el arete original.',
        'cedula_profesional' => 'Captura la cédula del documento. No debe estar registrada.',
        'telefono' => 'Incluye la lada, por ejemplo +52 961 123 4567.',
        'correo' => 'Ejemplo: nombre@dominio.com.',
        'fecha', 'fecha_ingreso', 'fecha_aplicacion' => 'Puedes registrar una fecha anterior; no se permiten fechas futuras.',
        'fecha_nacimiento' => 'Opcional. No puede ser posterior a la fecha de ingreso.',
        'proxima_aplicacion' => 'Opcional. Debe ser igual o posterior a la aplicación.',
        'litros' => 'Mayor que cero. Puedes usar hasta 2 decimales.',
        'numero_ordenio' => 'Número de ordeño del animal en el día, del 1 al 20.',
        'dosis' => 'Incluye cantidad y unidad, por ejemplo 2 ml.',
        'intervalo_dias' => 'De 1 a 3650 días.',
        'observaciones', 'descripcion' => 'Hasta 2000 caracteres.',
        default => '',
    };
    $fieldLength = $maxlength ?? match ($name) {
        'arete_siniiga', 'cedula_profesional' => 50,
        'telefono' => 30,
        'correo' => 254,
        'observaciones', 'descripcion' => 2000,
        'municipio', 'raza', 'dosis', 'dosis_recomendada' => 100,
        'nombre' => request()->routeIs('ganado.*') ? 100 : 150,
        'localidad', 'especialidad', 'fabricante' => 150,
        default => null,
    };
    $fieldMax = $max ?? (($type ?? 'text') === 'date' && in_array($name, ['fecha', 'fecha_ingreso', 'fecha_aplicacion']) ? today()->toDateString() : null);
@endphp
<div data-field @class(['full-row' => ($type ?? 'text') === 'textarea'])>
    <label for="{{ $name }}">{{ $label }}{{ ($required ?? false) ? ' *' : '' }}</label>
    @if(($type ?? 'text') === 'select')
        <select id="{{ $name }}" name="{{ $name }}" @required($required ?? false) aria-describedby="{{ $name }}-hint {{ $name }}-error" aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}">
            <option value="">Seleccione</option>
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $fieldValue === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif(($type ?? 'text') === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="4" @required($required ?? false) @if($fieldLength) maxlength="{{ $fieldLength }}" @endif aria-describedby="{{ $name }}-hint {{ $name }}-error" aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}">{{ $fieldValue }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ $fieldValue }}"
            @required($required ?? false)
            aria-describedby="{{ $name }}-hint {{ $name }}-error" aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
            @if($fieldLength) maxlength="{{ $fieldLength }}" @endif
            @if(isset($step)) step="{{ $step }}" @endif
            @if(isset($min)) min="{{ $min }}" @endif
            @if($fieldMax !== null) max="{{ $fieldMax }}" @endif>
    @endif
    <small id="{{ $name }}-hint" class="field-hint" @if(!$fieldHint) hidden @endif>{{ $fieldHint }}</small>
    <span id="{{ $name }}-error" class="field-error" data-field-error aria-live="polite" @if(!$errors->has($name)) hidden @endif>{{ $errors->first($name) }}</span>
</div>
