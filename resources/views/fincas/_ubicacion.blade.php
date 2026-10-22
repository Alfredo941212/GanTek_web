@php($claves = $registro?->clavesUbicacion() ?? [])
@if($registro && !$registro->estado_id)
    <p class="full-row field-hint">Ubicación anterior: {{ $registro->estado }}, {{ $registro->municipio }}{{ $registro->localidad ? ', '.$registro->localidad : '' }}. Confirma la ubicación en los catálogos para guardar cambios.</p>
@endif
@foreach(['estado' => 'Estado de la República', 'municipio' => 'Municipio', 'localidad' => 'Localidad'] as $campo => $etiqueta)
    <div data-field>
        <label for="{{ $campo === 'localidad' ? 'buscar-localidad' : $campo.'_clave' }}">{{ $etiqueta }}{{ $campo !== 'localidad' ? ' *' : ' (opcional)' }}</label>
        @if($campo === 'localidad')
            <input type="search" id="buscar-localidad" placeholder="Escribe para buscar una localidad" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="resultados-localidades" aria-describedby="localidad-seleccionada localidad_clave-error" autocomplete="off" maxlength="100" disabled>
            <div id="resultados-localidades" role="listbox" aria-label="Localidades" hidden></div>
            <p id="localidad-seleccionada" class="field-hint" role="status" hidden></p>
        @endif
        <select @if($campo === 'localidad') hidden @endif id="{{ $campo }}_clave" name="{{ $campo }}_clave" data-inicial="{{ old($campo.'_clave', $claves[$campo] ?? '') }}" @required($campo !== 'localidad') disabled aria-describedby="ubicacion-estado {{ $campo }}_clave-error" aria-invalid="{{ $errors->has($campo.'_clave') ? 'true' : 'false' }}">
            <option value="">Seleccione</option>
        </select>
        <span id="{{ $campo }}_clave-error" class="field-error" data-field-error @if(!$errors->has($campo.'_clave')) hidden @endif>{{ $errors->first($campo.'_clave') }}</span>
        @if($campo === 'localidad')
            <button type="button" class="btn" id="mas-localidades" hidden>Ver más localidades</button>
        @endif
    </div>
@endforeach
<div class="full-row">
    <p id="ubicacion-estado" class="field-hint" role="status" aria-live="polite">Cargando ubicaciones…</p>
    <button type="button" class="btn" id="reintentar-ubicacion" hidden>Reintentar carga</button>
</div>
@push('scripts')
    <script src="{{ asset('js/fincas-ubicacion.js') }}?v={{ filemtime(public_path('js/fincas-ubicacion.js')) }}" data-estados="{{ route('api.ubicaciones.estados') }}" data-municipios="{{ route('api.ubicaciones.municipios', ['clave' => '00']) }}" data-localidades="{{ route('api.ubicaciones.localidades', ['clave' => '00000']) }}" defer></script>
@endpush
