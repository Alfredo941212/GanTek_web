@extends('layouts.app')
@section('title', 'Veterinarios | GanTek')
@section('heading', 'Veterinarios')
@section('content')
<div class="toolbar">
<div></div>
@can('create', \App\Models\Veterinario::class)
    <a class="btn primary" href="{{ route('veterinarios.create') }}">+ Nuevo veterinario</a>
@endcan
</div>
<div class="panel table-scroll" tabindex="0" role="region" aria-label="Tabla de registros">
<p class="table-guide">Desliza la tabla hacia los lados para ver todos los datos y las acciones.</p>
<table>
    <thead><tr><th>Nombre</th><th>Cédula</th><th>Teléfono</th><th>Correo</th><th>Especialidad</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($veterinarios as $item)
        <tr><td>{{ $item->nombre }}</td><td>{{ $item->cedula_profesional }}</td><td>{{ $item->telefono ?? '—' }}</td><td>{{ $item->correo ?? '—' }}</td><td>{{ $item->especialidad ?? '—' }}</td><td><span @class(['badge', 'inactive' => $item->estado !== 'Activo'])>{{ $item->estado }}</span></td>
            <td><div class="actions">

                @can('update', $item)<a href="{{ route('veterinarios.edit', $item) }}">Editar</a>@endcan
                @can('delete', $item)
                <form method="POST" action="{{ route('veterinarios.destroy', $item) }}" data-confirm-title="Desactivar veterinario" data-confirm-name="{{ $item->nombre }}" data-confirm-message="Quedará inactivo en el catálogo compartido. Su historial se conserva." data-confirm-label="Desactivar">
                    @csrf @method('DELETE')
                    <button type="submit">Desactivar</button>
                </form>
                @endcan
            </div></td>
        </tr>
    @empty
        <tr><td colspan="7">Sin registros.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $veterinarios->links('partials.pagination') }}
@endsection
