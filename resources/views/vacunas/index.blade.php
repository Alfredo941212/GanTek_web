@extends('layouts.app')
@section('title', 'Catálogo de vacunas | GanTek')
@section('heading', 'Catálogo de vacunas')
@section('content')
<div class="toolbar">
<div></div>
@can('create', \App\Models\Vacuna::class)
    <a class="btn primary" href="{{ route('vacunas.create') }}">+ Registrar</a>
@endcan
</div>
<div class="panel table-scroll">
<table>
    <thead><tr><th>Nombre</th><th>Fabricante</th><th>Dosis de referencia</th><th>Intervalo (días)</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($vacunas as $item)
        <tr><td>{{ $item->nombre }}</td><td>{{ $item->fabricante ?? '—' }}</td><td>{{ $item->dosis_recomendada ?? '—' }}</td><td>{{ $item->intervalo_dias ?? '—' }}</td><td>{{ $item->estado }}</td>
            <td><div class="actions">

                @can('update', $item)<a href="{{ route('vacunas.edit', $item) }}">Editar</a>@endcan
                @can('delete', $item)
                <form method="POST" action="{{ route('vacunas.destroy', $item) }}" onsubmit="return confirm('¿Desactivar este registro?')">
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
{{ $vacunas->links() }}
@endsection
