@extends('layouts.app')
@section('title', 'Lotes | GanTek')
@section('heading', 'Lotes')
@section('content')
<div class="toolbar">
<div></div>
@can('create', \App\Models\Lote::class)
    <a class="btn primary" href="{{ route('lotes.create') }}">+ Nuevo lote</a>
@endcan
</div>
<div class="panel table-scroll" tabindex="0" role="region" aria-label="Tabla de registros">
<p class="table-guide">Desliza la tabla hacia los lados para ver todos los datos y las acciones.</p>
<table>
    <thead><tr><th>Nombre</th><th>Finca</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($lotes as $item)
        <tr><td>{{ $item->nombre }}</td><td>{{ $item->finca->nombre }}</td><td>{{ $item->descripcion ?? '—' }}</td><td><span @class(['badge', 'inactive' => $item->estado !== 'Activo'])>{{ $item->estado }}</span></td>
            <td><div class="actions">

                <a href="{{ route('lotes.edit', $item) }}">Editar</a>
                <form method="POST" action="{{ route('lotes.destroy', $item) }}" data-confirm-title="Eliminar lote" data-confirm-name="{{ $item->nombre }}" data-confirm-message="Se eliminará si no tiene ganado ni historial de ordeño. Esta acción no se puede deshacer." data-confirm-label="Eliminar">
                    @csrf @method('DELETE')
                    <button type="submit">Eliminar</button>
                </form>
            </div></td>
        </tr>
    @empty
        <tr><td colspan="5">Sin registros.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $lotes->links('partials.pagination') }}
@endsection
