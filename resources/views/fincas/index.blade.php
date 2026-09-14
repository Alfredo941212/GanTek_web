@extends('layouts.app')
@section('title', 'Fincas | GanTek')
@section('heading', 'Fincas')
@section('content')
<div class="toolbar">
<div></div>
@can('create', \App\Models\Finca::class)
    <a class="btn primary" href="{{ route('fincas.create') }}">+ Registrar</a>
@endcan
</div>
<div class="panel table-scroll">
<table>
    <thead><tr><th>Nombre</th><th>Municipio</th><th>Localidad</th><th>Estado</th><th>Superficie (ha)</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($fincas as $item)
        <tr><td>{{ $item->nombre }}</td><td>{{ $item->municipio }}</td><td>{{ $item->localidad ?? '—' }}</td><td>{{ $item->estado }}</td><td>{{ $item->superficie ?? '—' }}</td>
            <td><div class="actions">

                <a href="{{ route('fincas.edit', $item) }}">Editar</a>
                <form method="POST" action="{{ route('fincas.destroy', $item) }}" onsubmit="return confirm('¿Eliminar este registro?')">
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
{{ $fincas->links() }}
@endsection
