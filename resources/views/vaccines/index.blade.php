@extends('layouts.app')
@section('title', 'Vacunaciones | GanTek')
@section('heading', 'Vacunaciones')
@section('content')
<div class="toolbar">
<div></div>
@can('create', \App\Models\Vacunacion::class)
    <a class="btn primary" href="{{ route('vacunaciones.create') }}">+ Registrar</a>
@endcan
</div>
<div class="panel table-scroll">
<table>
    <thead><tr><th>Animal</th><th>Vacuna</th><th>Veterinario</th><th>Aplicación</th><th>Próxima</th><th>Dosis</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($vacunaciones as $item)
        <tr><td>{{ $item->ganado->arete_siniiga }}</td><td>{{ $item->vacuna->nombre }}</td><td>{{ $item->veterinario->nombre }}</td><td>{{ $item->fecha_aplicacion->format('d/m/Y') }}</td><td>{{ $item->proxima_aplicacion?->format('d/m/Y') ?? '—' }}</td><td>{{ $item->dosis }}</td>
            <td><div class="actions">

                <a href="{{ route('vacunaciones.edit', $item) }}">Editar</a>
                <form method="POST" action="{{ route('vacunaciones.destroy', $item) }}" onsubmit="return confirm('¿Eliminar este registro?')">
                    @csrf @method('DELETE')
                    <button type="submit">Eliminar</button>
                </form>
            </div></td>
        </tr>
    @empty
        <tr><td colspan="7">Sin registros.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $vacunaciones->links() }}
@endsection
