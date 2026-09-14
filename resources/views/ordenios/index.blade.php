@extends('layouts.app')
@section('title', 'Registros de ordeño | GanTek')
@section('heading', 'Registros de ordeño')
@section('content')
<div class="toolbar">
<div></div>
@can('create', \App\Models\RegistroOrdenio::class)
    <a class="btn primary" href="{{ route('ordenios.create') }}">+ Registrar</a>
@endcan
</div>
<div class="panel table-scroll">
<table>
    <thead><tr><th>Animal</th><th>Fecha</th><th>Turno</th><th>Litros</th><th>Finca / lote histórico</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($ordenios as $item)
        <tr><td>{{ $item->ganado->arete_siniiga }}</td><td>{{ $item->fecha->format('d/m/Y') }}</td><td>{{ $item->turno }}</td><td>{{ $item->litros }}</td><td>{{ $item->loteHistorico->finca->nombre.' / '.$item->loteHistorico->nombre }}</td>
            <td><div class="actions">

                <a href="{{ route('ordenios.edit', $item) }}">Editar</a>
                <form method="POST" action="{{ route('ordenios.destroy', $item) }}" onsubmit="return confirm('¿Eliminar este registro?')">
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
{{ $ordenios->links() }}
@endsection
