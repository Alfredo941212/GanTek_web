@extends('layouts.app')
@section('title', 'Veterinarios | GanTek')
@section('heading', 'Veterinarios')
@section('content')
<div class="toolbar">
<div></div>
@can('create', \App\Models\Veterinario::class)
    <a class="btn primary" href="{{ route('veterinarios.create') }}">+ Registrar</a>
@endcan
</div>
<div class="panel table-scroll">
<table>
    <thead><tr><th>Nombre</th><th>Cédula</th><th>Teléfono</th><th>Correo</th><th>Especialidad</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($veterinarios as $item)
        <tr><td>{{ $item->nombre }}</td><td>{{ $item->cedula_profesional }}</td><td>{{ $item->telefono ?? '—' }}</td><td>{{ $item->correo ?? '—' }}</td><td>{{ $item->especialidad ?? '—' }}</td><td>{{ $item->estado }}</td>
            <td><div class="actions">

                @can('update', $item)<a href="{{ route('veterinarios.edit', $item) }}">Editar</a>@endcan
                @can('delete', $item)
                <form method="POST" action="{{ route('veterinarios.destroy', $item) }}" onsubmit="return confirm('¿Desactivar este registro?')">
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
{{ $veterinarios->links() }}
@endsection
