@extends('layouts.app')
@section('title', 'Dashboard | GanTek')

@section('heading', 'Tu finca, de un vistazo')
@section('subheading', 'Producción, salud y cuidados: cada decisión empieza con información.')

@section('content')

<div class="dashboard-page">

    <div class="stats-grid">

        <div class="stat-card">
            <span>@include('partials.icon', ['name' => 'Ganado']) Animales activos</span>
            <strong>{{ $activos }}</strong>
            <small>En tu hato</small>
        </div>

        <div class="stat-card">
            <span> Producción de hoy</span>
            <strong>{{ number_format($litrosHoy, 2) }} L</strong>
            <small>Litros de leche</small>
        </div>

        <div class="stat-card">
            <span> Promedio diario</span>
            <strong>{{ number_format($promedio, 2) }} L</strong>
            <small>
                {{ $diasPromedio }} días con registros
            </small>
        </div>

        <div class="stat-card">
            <span> Vacunas próximas</span>
            <strong>{{ $proximas->count() }}</strong>
            <small>En los próximos 30 días</small>
        </div>

        <div class="stat-card">
            <span>@include('partials.icon', ['name' => 'Alerta']) Alertas de producción</span>
            <strong>{{ $alertasProduccion->count() }}</strong>
            <small>{{ $alertasProduccion->isEmpty() ? 'Sin alertas detectadas' : 'Animales por revisar' }}</small>
        </div>

        <div class="stat-card">
            <span> Vacunas vencidas</span>
            <strong>{{ $vencidas->count() }}</strong>
            <small>Aplicaciones</small>
        </div>

    </div>

    <div class="toolbar">

        <a
            class="btn primary"
            href="{{ route('ordenios.create') }}"
        >
             Registrar ordeño
        </a>

        <a
            class="btn"
            href="{{ route('produccion.index') }}"
        >
             Consultar producción
        </a>

    </div>

    <div class="two-columns">

        <section class="panel">

            <h2>@include('partials.icon', ['name' => 'Ganado']) Ganado reciente</h2>

            <div class="table-scroll"><table>

                <thead>
                    <tr>
                        <th>Animal</th>
                        <th>Lote</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($animales as $animal)

                        <tr>

                            <td>
                                <a href="{{ route('ganado.show', $animal) }}">
                                    {{ $animal->arete_siniiga }}
                                </a>
                            </td>

                            <td>
                                {{ $animal->lote->nombre }}
                            </td>

                            <td>
                                {{ $animal->estado }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="3">
                                Sin animales registrados.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table></div>

        </section>

        <section class="panel">

            <h2 id="avisos" tabindex="-1">@include('partials.icon', ['name' => 'Alerta']) Baja producción individual</h2>

<p>
    Se muestra una alerta cuando la producción total de ayer
    está por debajo del mínimo diario establecido para la vaca.
</p>

@forelse($alertasProduccion as $alerta)

    <div class="alert error">

        <a href="{{ route('ganado.show', $alerta->id) }}">
            {{ $alerta->arete_siniiga }}
        </a>

        @if ($alerta->nombre)
            — {{ $alerta->nombre }}
        @endif

        <br>

        Producción de ayer:
        <strong>{{ number_format($alerta->produccion, 2) }} L</strong>

        <br>

        Mínimo diario:
        <strong>{{ number_format($alerta->produccion_minima_diaria, 2) }} L</strong>

        <br>

        <strong>Revisa el estado del animal.</strong>

    </div>

@empty

    <div class="dashboard-message">
         No hay alertas de baja producción individual.
    </div>

@endforelse

        </section>

        <section class="panel">

            <h2>@include('partials.icon', ['name' => 'Vacunaciones']) Próxima vacunación</h2>

            @forelse($proximas as $registro)

                <p>

                    <a href="{{ route('ganado.show', $registro->ganado) }}">

                        {{ $registro->ganado->arete_siniiga }}

                    </a>

                    · {{ $registro->vacuna->nombre }}

                    · {{ $registro->proxima_aplicacion->format('d/m/Y') }}

                </p>

            @empty

                <p>
                    Sin próximas aplicaciones en los siguientes 30 días.
                </p>

            @endforelse

        </section>

        <section class="panel">

            <h2>@include('partials.icon', ['name' => 'Alerta']) Vacunación vencida</h2>

            @forelse($vencidas as $registro)

                <p>

                    <a href="{{ route('ganado.show', $registro->ganado) }}">

                        {{ $registro->ganado->arete_siniiga }}

                    </a>

                    · {{ $registro->vacuna->nombre }}

                    · {{ $registro->proxima_aplicacion->format('d/m/Y') }}

                </p>

            @empty

                <p>
                    Sin aplicaciones vencidas.
                </p>

            @endforelse

        </section>

    </div>



</div>

@endsection
