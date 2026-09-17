@extends('layouts.app')
@section('title', 'Fincas | GanTek')
@section('heading', 'Fincas')
@section('content')
<div class="toolbar">
<div></div>
@can('create', \App\Models\Finca::class)
    <a class="btn primary" href="{{ route('fincas.create') }}">+ Nueva finca</a>
@endcan
</div>
<div class="panel table-scroll" tabindex="0" role="region" aria-label="Tabla de registros">
<p class="table-guide">Desliza la tabla hacia los lados para ver todos los datos y las acciones.</p>
<table>
    <thead><tr><th>Nombre</th><th>Municipio</th><th>Localidad</th><th>Estado</th><th>Superficie (ha)</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($fincas as $item)
        <tr><td>{{ $item->nombre }}</td><td>{{ $item->municipio }}</td><td>{{ $item->localidad ?? '—' }}</td><td>{{ $item->estado }}</td><td>{{ $item->superficie ?? '—' }}</td>
            <td><div class="actions">

                <a href="{{ route('fincas.edit', $item) }}">Editar</a>
                <form method="POST" action="{{ route('fincas.destroy', $item) }}" data-confirm-title="Eliminar finca" data-confirm-name="{{ $item->nombre }}" data-confirm-message="Se eliminará si no tiene lotes asociados. Esta acción no se puede deshacer." data-confirm-label="Eliminar">
                    @csrf @method('DELETE')
                    <button type="submit">Eliminar</button>
                </form>
            </div></td>
        </tr>
    @empty
        <tr><td colspan="6">Sin registros.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $fincas->links('partials.pagination') }}
@endsection
