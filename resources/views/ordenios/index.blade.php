@extends('layouts.app')

@section('title', 'Jornadas de ordeña | GanTek')

@section('heading', 'Jornadas de ordeña')
@section('subheading', 'Consulta la producción registrada desde la aplicación móvil.')

@section('content')

    <div class="dashboard-page">

        {{-- ========================= --}}
        {{-- JORNADA DE HOY --}}
        {{-- ========================= --}}

        <div class="section-header">
            <div>
                <h2>Jornada de hoy</h2>
                <p>
                    Estado actual de los grupos de ordeña.
                </p>
            </div>
        </div>

        @if ($sesionesHoy->isEmpty())

            <div class="empty-state">
                <h3>No hay jornada registrada hoy</h3>
                <p>
                    La jornada debe iniciarse desde la aplicación móvil.
                </p>
            </div>
        @else
            <div class="cards-grid">

                @foreach ($sesionesHoy as $sesion)
                    @php
                        $promedio = null;

                        if ($sesion->litros_totales !== null && $sesion->cantidad_vacas > 0) {
                            $promedio = $sesion->litros_totales / $sesion->cantidad_vacas;
                        }
                    @endphp

                    <div class="ordenio-card">

                        <div class="ordenio-card-header">

                            <div>
                                <h3>
                                    {{ $sesion->grupo?->nombre ?? 'Grupo' }}
                                </h3>

                                <small>
                                    Orden:
                                    {{ $sesion->grupo?->orden ?? '-' }}
                                </small>
                            </div>

                            <span class="status-badge
                            status-{{ $sesion->estado }}">
                                {{ strtoupper(str_replace('_', ' ', $sesion->estado)) }}
                            </span>

                        </div>

                        <div class="ordenio-info">

                            <div>
                                <span>Vacas</span>
                                <strong>
                                    {{ $sesion->cantidad_vacas }}
                                </strong>
                            </div>

                            <div>
                                <span>Producción</span>
                                <strong>
                                    @if ($sesion->litros_totales !== null)
                                        {{ number_format($sesion->litros_totales, 2) }}
                                        L
                                    @else
                                        -
                                    @endif
                                </strong>
                            </div>

                            <div>
                                <span>Duración</span>
                                <strong>
                                    @if ($sesion->duracion_minutos !== null)
                                        {{ $sesion->duracion_minutos }} min
                                    @else
                                        -
                                    @endif
                                </strong>
                            </div>

                            <div>
                                <span>Promedio por vaca</span>
                                <strong>
                                    @if ($promedio !== null)
                                        {{ number_format($promedio, 2) }}
                                        L
                                    @else
                                        -
                                    @endif
                                </strong>
                            </div>

                        </div>

                        <div class="ordenio-times">

                            <span>
                                Inicio:
                                @if ($sesion->hora_inicio)
                                    {{ $sesion->hora_inicio->format('H:i') }}
                                @else
                                    -
                                @endif
                            </span>

                            <span>
                                Fin:
                                @if ($sesion->hora_fin)
                                    {{ $sesion->hora_fin->format('H:i') }}
                                @else
                                    -
                                @endif
                            </span>

                        </div>

                        @if ($sesion->observaciones)
                            <div class="ordenio-observations">
                                <strong>Observaciones:</strong>

                                <p>
                                    {{ $sesion->observaciones }}
                                </p>
                            </div>
                        @endif

                    </div>
                @endforeach

            </div>

        @endif


        {{-- ========================= --}}
        {{-- HISTORIAL --}}
        {{-- ========================= --}}

        <div class="section-header history-header">

            <div>
                <h2>Historial reciente</h2>
                <p>
                    Últimas jornadas de ordeña finalizadas.
                </p>
            </div>

        </div>

        @if ($historial->isEmpty())

            <div class="empty-state">

                <h3>Sin historial disponible</h3>

                <p>
                    Cuando se finalicen jornadas aparecerán aquí.
                </p>

            </div>
        @else
            <div class="table-container">

                <table class="ordenio-table">

                    <thead>

                        <tr>
                            <th>Fecha</th>
                            <th>Grupo</th>
                            <th>Vacas</th>
                            <th>Producción</th>
                            <th>Duración</th>
                            <th>Promedio</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($historial as $sesion)
                            @php
                                $promedio = null;

                                if ($sesion->litros_totales !== null && $sesion->cantidad_vacas > 0) {
                                    $promedio = $sesion->litros_totales / $sesion->cantidad_vacas;
                                }
                            @endphp

                            <tr>

                                <td>
                                    {{ $sesion->fecha->format('d/m/Y') }}
                                </td>

                                <td>
                                    {{ $sesion->grupo?->nombre ?? 'Grupo' }}
                                </td>

                                <td>
                                    {{ $sesion->cantidad_vacas }}
                                </td>

                                <td>
                                    {{ number_format($sesion->litros_totales, 2) }}
                                    L
                                </td>

                                <td>
                                    {{ $sesion->duracion_minutos ?? '-' }}
                                    min
                                </td>

                                <td>
                                    @if ($promedio !== null)
                                        {{ number_format($promedio, 2) }}
                                        L/vaca
                                    @else
                                        -
                                    @endif
                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

            @if ($historial->hasPages())
                <div class="gantek-pagination">

                    <div class="pagination-info">
                        Mostrando
                        <strong>{{ $historial->firstItem() }}</strong>
                        a
                        <strong>{{ $historial->lastItem() }}</strong>
                        de
                        <strong>{{ $historial->total() }}</strong>
                        jornadas
                    </div>

                    <div class="pagination-controls">

                        @if ($historial->onFirstPage())
                            <span class="pagination-btn disabled">
                                ‹ Anterior
                            </span>
                        @else
                            <a href="{{ $historial->previousPageUrl() }}" class="pagination-btn">
                                ‹ Anterior
                            </a>
                        @endif

                        @foreach ($historial->getUrlRange(1, $historial->lastPage()) as $page => $url)
                            @if ($page == $historial->currentPage())
                                <span class="pagination-number active">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="pagination-number">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach

                        @if ($historial->hasMorePages())
                            <a href="{{ $historial->nextPageUrl() }}" class="pagination-btn">
                                Siguiente ›
                            </a>
                        @else
                            <span class="pagination-btn disabled">
                                Siguiente ›
                            </span>
                        @endif

                    </div>

                </div>
            @endif

        @endif

    </div>

@endsection
