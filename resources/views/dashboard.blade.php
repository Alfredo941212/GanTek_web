@extends('layouts.app')

@section('title', 'Dashboard | GanTek')

@section('heading', '')

@section('content')

<div class="dashboard-page">

    <div class="dashboard-hero">
        <div class="dashboard-welcome">
            <h1>🌿 ¡Bienvenido!</h1>
            <p>Sistema web de gestión ganadera</p>
        </div>
    </div>

    <div class="stats-grid">

        <div class="stat-card">
            <span>🐄 Animales activos</span>
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
            <span>🔔 Alertas de producción</span>
            <strong>{{ $alertasProduccion->count() }}</strong>
            <small>Revisión requerida</small>
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

            <h2>🐄 Ganado reciente</h2>

            <table>

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

            </table>

        </section>

        <section class="panel">

            <h2> Disminución de producción</h2>

            <p>
                Últimos 3 días completos frente a los 7 anteriores.
                Se requieren ambos turnos cada día.
            </p>

            @forelse($alertasProduccion as $alerta)

                <div class="alert error">

                    <a href="{{ route('ganado.show', $alerta->id) }}">
                        {{ $alerta->arete_siniiga }}
                    </a>

                    :

                    {{ number_format($alerta->reciente, 2) }} L/día

                    frente a

                    {{ number_format($alerta->referencia, 2) }} L/día.

                    <strong>
                        Revisa el estado del animal.
                    </strong>

                </div>

            @empty

                <div class="dashboard-message">
                    ℹ️ No hay alertas con los registros suficientes disponibles.
                </div>

            @endforelse

        </section>

        <section class="panel">

            <h2>💉 Próxima vacunación</h2>

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

            <h2>⚠️ Vacunación vencida</h2>

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

    <div class="dashboard-footer">
        “Tecnología para un campo más productivo.”
    </div>

</div>

@endsection