@extends('layouts.app')
@section('title', 'Lotes | GanTek')
@section('heading', 'Lotes')
@section('content')
<div class="toolbar">
<div></div>
@can('create', \App\Models\Lote::class)
    <a class="btn primary" href="{{ route('lotes.create') }}">+ Registrar</a>
@endcan
</div>
<div class="panel table-scroll">
<table>
    <thead><tr><th>Nombre</th><th>Finca</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($lotes as $item)
        <tr><td>{{ $item->nombre }}</td><td>{{ $item->finca->nombre }}</td><td>{{ $item->descripcion ?? '—' }}</td><td>{{ $item->estado }}</td>
            <td><div class="actions">

                <a href="{{ route('lotes.edit', $item) }}">Editar</a>
                <form method="POST" action="{{ route('lotes.destroy', $item) }}" onsubmit="return confirm('¿Eliminar este registro?')">
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
{{ $lotes->links() }}
@endsection
