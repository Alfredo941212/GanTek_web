@extends('layouts.app')
@section('title', 'Ganado | GanTek')
@section('heading', 'Ganado')
@section('content')
<div class="toolbar">
<form method="GET"><input type="search" aria-label="Buscar por arete, nombre o raza" name="search" value="{{ request('search') }}" placeholder="Buscar por arete, nombre o raza"><button class="btn">Buscar</button></form>
@can('create', \App\Models\Ganado::class)
    <a class="btn primary" href="{{ route('ganado.create') }}">+ Registrar animal</a>
@endcan
</div>
<div class="panel table-scroll" tabindex="0" role="region" aria-label="Tabla de registros">
<p class="table-guide">Desliza la tabla hacia los lados para ver todos los datos y las acciones.</p>
<table>
    <thead><tr><th>Arete</th><th>Nombre</th><th>Sexo</th><th>Finca / lote</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($ganado as $item)
        <tr><td>{{ $item->arete_siniiga }}</td><td>{{ $item->nombre ?? '—' }}</td><td>{{ $item->sexo }}</td><td>{{ $item->lote->finca->nombre.' / '.$item->lote->nombre }}</td><td><span @class(['badge', 'inactive' => $item->estado !== 'Activo'])>{{ $item->estado }}</span></td>
            <td><div class="actions">
                <a href="{{ route('ganado.show', $item) }}">Historial</a>
                <a href="{{ route('ganado.edit', $item) }}">Editar</a>
                <form method="POST" action="{{ route('ganado.destroy', $item) }}" data-confirm-title="Dar de baja al animal" data-confirm-name="{{ $item->arete_siniiga }}" data-confirm-message="El animal quedará en estado Baja. Su historial se conserva." data-confirm-label="Dar de baja">
                    @csrf @method('DELETE')
                    <button type="submit">Dar de baja</button>
                </form>
            </div></td>
        </tr>
    @empty
        <tr><td colspan="6">Sin registros.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $ganado->links('partials.pagination') }}
@endsection
