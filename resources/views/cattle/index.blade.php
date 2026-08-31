@extends('layouts.app')
@section('title', 'Ganado | GanTek')
@section('heading', 'Ganado')

@section('content')
<div class="toolbar">
    <form method="GET">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Buscar por código, nombre o raza">
        <button class="btn" type="submit">Buscar</button>
    </form>
    <a class="btn primary" href="{{ route('ganado.create') }}">+ Registrar animal</a>
</div>

<div class="panel">
<table>
    <thead>
    <tr>
        <th>Código</th><th>Nombre</th><th>Sexo</th><th>Raza</th><th>Peso</th><th>Estado</th><th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    @forelse($cattle as $item)
        <tr>
            <td>{{ $item->code }}</td>
            <td>{{ $item->name ?? '—' }}</td>
            <td>{{ $item->sex }}</td>
            <td>{{ $item->breed ?? '—' }}</td>
            <td>{{ $item->initial_weight ? $item->initial_weight . ' kg' : '—' }}</td>
            <td>{{ $item->status }}</td>
            <td class="actions">
                <a href="{{ route('ganado.show', $item) }}">Ver</a>
                <a href="{{ route('ganado.edit', $item) }}">Editar</a>
                <form method="POST" action="{{ route('ganado.destroy', $item) }}" onsubmit="return confirm('¿Eliminar este animal?')">
                    @csrf @method('DELETE')
                    <button type="submit">Eliminar</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7">No hay animales registrados.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

{{ $cattle->links() }}
@endsection
