@extends('layouts.app')
@section('title', 'Dashboard | GanTek')
@section('heading', 'Dashboard')
@section('content')
    <div class="stats-grid">
        <div class="stat-card"><span>Animales activos</span><strong>{{ $activos }}</strong></div>
        <div class="stat-card"><span>Producción de hoy</span><strong>{{ number_format($litrosHoy, 2) }} L</strong></div>
        <div class="stat-card"><span>Promedio diario · últimos 7 días</span><strong>{{ number_format($promedio, 2) }}
                L</strong><small>{{ $diasPromedio }} días con registros; excluye hoy</small></div>
        <div class="stat-card"><span>Vacunas próximas · 30 días</span><strong>{{ $proximas->count() }}</strong></div>
        <div class="stat-card">
            <span>Alertas de producción</span>
            <strong>{{ $alertasProduccion->count() + $alertasLotes->count() }}</strong>
            <small>
                {{ $alertasProduccion->count() }} por vaca ·
                {{ $alertasLotes->count() }} por lote
            </small>
        </div>
        <div class="stat-card"><span>Vacunas vencidas</span><strong>{{ $vencidas->count() }}</strong></div>
    </div>
    <div class="toolbar"><a class="btn primary" href="{{ route('ordenios.create') }}">Registrar ordeño</a><a class="btn"
            href="{{ route('produccion.index') }}">Consultar producción</a></div>
    <div class="two-columns">
        <section class="panel">
            <h2>Ganado reciente</h2>
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
                            <td><a href="{{ route('ganado.show', $animal) }}">{{ $animal->arete_siniiga }}</a></td>
                            <td>{{ $animal->lote->nombre }}</td>
                            <td>{{ $animal->estado }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">Sin animales.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        <section class="panel">
            <h2>Alertas de baja producción</h2>
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

                    Mínimo esperado:
                    <strong>{{ number_format($alerta->produccion_minima_diaria, 2) }} L</strong>

                    <br>

                    <strong>Revisa el estado del animal.</strong>
                </div>
            @empty
                <p>No hay alertas de baja producción.</p>
            @endforelse
        </section>
        <section class="panel">
            <h2>Alertas de producción por lote</h2>

            <p>
                Se muestra una alerta cuando el promedio de producción de ayer
                por vaca está por debajo del mínimo establecido para el lote.
            </p>

            @forelse($alertasLotes as $alerta)
                <div class="alert error">
                    <strong>{{ $alerta->nombre }}</strong>

                    <br>

                    Vacas en producción:
                    <strong>{{ $alerta->vacas_produccion }}</strong>

                    <br>

                    Producción de ayer:
                    <strong>{{ number_format($alerta->produccion, 2) }} L</strong>

                    <br>

                    Promedio por vaca:
                    <strong>{{ number_format($alerta->promedio_por_vaca, 2) }} L</strong>

                    <br>

                    Mínimo por vaca:
                    <strong>{{ number_format($alerta->produccion_minima_por_vaca, 2) }} L</strong>

                    <br>

                    Producción mínima esperada del lote:
                    <strong>{{ number_format($alerta->produccion_minima_esperada, 2) }} L</strong>

                    <br>

                    <strong>Revisa la producción del lote.</strong>
                </div>
            @empty
                <p>No hay alertas de baja producción por lote.</p>
            @endforelse
        </section>

        <section class="panel">
            <h2>Próxima vacunación</h2>
            @forelse($proximas as $registro)
                <p><a href="{{ route('ganado.show', $registro->ganado) }}">{{ $registro->ganado->arete_siniiga }}</a> ·
                    {{ $registro->vacuna->nombre }} · {{ $registro->proxima_aplicacion->format('d/m/Y') }}</p>
            @empty
                <p>Sin próximas aplicaciones en los siguientes 30 días.</p>
            @endforelse
        </section>
        <section class="panel">
            <h2>Vacunación vencida</h2>
            @forelse($vencidas as $registro)
                <p><a href="{{ route('ganado.show', $registro->ganado) }}">{{ $registro->ganado->arete_siniiga }}</a> ·
                    {{ $registro->vacuna->nombre }} · {{ $registro->proxima_aplicacion->format('d/m/Y') }}</p>
            @empty
                <p>Sin aplicaciones vencidas.</p>
            @endforelse
        </section>
    </div>
@endsection
