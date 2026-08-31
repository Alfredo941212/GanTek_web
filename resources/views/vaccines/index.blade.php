@extends('layouts.app')
@section('title', 'Vacunación | GanTek')
@section('heading', 'Vacunación')

@section('content')
<div class="toolbar">
    <div></div>
    <a class="btn primary" href="{{ route('vacunas.create') }}">+ Registrar vacuna</a>
</div>

<div class="panel">
<table>
    <thead><tr><th>Animal</th><th>Vacuna</th><th>Aplicación</th><th>Próxima</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($vaccines as $v)
        <tr>
            <td>{{ $v->cattle->code }}</td>
            <td>{{ $v->vaccine_name }}</td>
            <td>{{ $v->application_date->format('d/m/Y') }}</td>
            <td>{{ $v->next_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="actions">
                <a href="{{ route('vacunas.edit', $v) }}">Editar</a>
                <form method="POST" action="{{ route('vacunas.destroy', $v) }}">
                    @csrf @method('DELETE')
                    <button type="submit">Eliminar</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5">No hay vacunas registradas.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $vaccines->links() }}
@endsection
