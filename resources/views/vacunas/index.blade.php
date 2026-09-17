@extends('layouts.app')
@section('title', 'Catálogo de vacunas | GanTek')
@section('heading', 'Catálogo de vacunas')
@section('content')
<div class="toolbar">
<div></div>
@can('create', \App\Models\Vacuna::class)
    <a class="btn primary" href="{{ route('vacunas.create') }}">+ Nueva vacuna</a>
@endcan
</div>
<div class="panel table-scroll" tabindex="0" role="region" aria-label="Tabla de registros">
<p class="table-guide">Desliza la tabla hacia los lados para ver todos los datos y las acciones.</p>
<table>
    <thead><tr><th>Nombre</th><th>Fabricante</th><th>Dosis de referencia</th><th>Intervalo (días)</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($vacunas as $item)
        <tr><td>{{ $item->nombre }}</td><td>{{ $item->fabricante ?? '—' }}</td><td>{{ $item->dosis_recomendada ?? '—' }}</td><td>{{ $item->intervalo_dias ?? '—' }}</td><td><span @class(['badge', 'inactive' => $item->estado !== 'Activo'])>{{ $item->estado }}</span></td>
            <td><div class="actions">

                @can('update', $item)<a href="{{ route('vacunas.edit', $item) }}">Editar</a>@endcan
                @can('delete', $item)
                <form method="POST" action="{{ route('vacunas.destroy', $item) }}" data-confirm-title="Desactivar vacuna" data-confirm-name="{{ $item->nombre }}" data-confirm-message="Quedará inactiva en el catálogo compartido. Su historial se conserva." data-confirm-label="Desactivar">
                    @csrf @method('DELETE')
                    <button type="submit">Desactivar</button>
                </form>
                @endcan
            </div></td>
        </tr>
    @empty
        <tr><td colspan="6">Sin registros.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $vacunas->links('partials.pagination') }}
@endsection
