@extends('layouts.app')

@section('title', 'Análisis IA | GanTek')

@section('heading', 'Análisis IA')

@section('subheading', 'Predicción y detección de anomalías en la producción de leche.')

@section('content')

    <div class="dashboard-page">

        <div class="stats-grid">

            <div class="stat-card">
                <span>Animales analizados</span>

                <strong>
                    {{ $resultado['total_animales'] }}
                </strong>

                <small>
                    Vacas en producción
                </small>
            </div>

            <div class="stat-card">
                <span>Producción normal</span>

                <strong>
                    {{ $normales }}
                </strong>

                <small>
                    Dentro de lo esperado
                </small>
            </div>

            <div class="stat-card">
                <span>Requieren atención</span>

                <strong>
                    {{ $atencion }}
                </strong>

                <small>
                    Variación moderada
                </small>
            </div>

            <div class="stat-card">
                <span>Anomalías</span>

                <strong>
                    {{ $anomalias }}
                </strong>

                <small>
                    Producción fuera de lo esperado
                </small>
            </div>

            <div class="stat-card">
                <span>Sin datos suficientes</span>

                <strong>
                    {{ $sinDatos }}
                </strong>

                <small>
                    Requieren más historial
                </small>
            </div>

        </div>


        <section class="panel">

            <div class="ia-heading">

                <div>
                    <h2>Resultados del análisis</h2>

                    <p>
                        Último análisis:
                        {{ $resultado['fecha_analisis'] }}
                    </p>
                </div>

            </div>


            @forelse($animales as $animal)

                <article class="ia-animal ia-{{ $animal['estado_ia'] }}">

                    <div class="ia-animal-header">

                        <div>
                            <small>Arete SINIIGA</small>

                            <h3>
                                {{ $animal['arete'] }}
                            </h3>
                        </div>

                        <span class="ia-state">
                            @switch($animal['estado_ia'])
                                @case('normal')
                                    Normal
                                @break

                                @case('atencion')
                                    Atención
                                @break

                                @case('anomalia')
                                    Anomalía
                                @break

                                @case('sin_datos_suficientes')
                                    Sin datos suficientes
                                @break

                                @default
                                    {{ ucfirst($animal['estado_ia']) }}
                            @endswitch
                        </span>

                    </div>


                    @if ($animal['estado_ia'] === 'sin_datos_suficientes')
                        <p class="ia-message">
                            {{ $animal['mensaje'] }}
                        </p>

                        <p class="ia-small">
                            Días registrados:
                            {{ $animal['dias_registrados'] ?? 0 }}
                        </p>

                        <p class="ia-small">
                            Días incompletos:
                            {{ $animal['dias_incompletos'] ?? 0 }}
                        </p>
                    @else
                        <div class="ia-metrics">

                            <div>
                                <span>Producción real</span>

                                <strong>
                                    {{ number_format($animal['produccion_real'], 2) }}
                                    L
                                </strong>
                            </div>

                            <div>
                                <span>Producción esperada</span>

                                <strong>
                                    {{ number_format($animal['produccion_esperada'], 2) }}
                                    L
                                </strong>
                            </div>

                            <div>
                                <span>Producción mínima</span>

                                <strong>
                                    {{ number_format($animal['produccion_minima'], 2) }}
                                    L
                                </strong>
                            </div>

                            <div>
                                <span>Diferencia</span>

                                <strong>
                                    {{ number_format($animal['diferencia_porcentual'], 2) }}
                                    %
                                </strong>
                            </div>

                            <div>
                                <span>Tendencia previa</span>

                                <strong>
                                    {{ ucfirst($animal['tendencia']) }}
                                </strong>
                            </div>

                            <div>
                                <span>Calidad del modelo</span>

                                <strong>
                                    {{ ucfirst($animal['calidad_ajuste']) }}
                                </strong>
                            </div>


                            <div>
                                <span>Ajuste R²</span>

                                <strong>
                                    {{ number_format($animal['r2'], 2) }}
                                </strong>
                            </div>

                            <div>
                                <span>Predicción siguiente día</span>

                                <strong>
                                    @if ($animal['prediccion_siguiente_dia'] !== null)
                                        {{ number_format($animal['prediccion_siguiente_dia'], 2) }}
                                        L
                                    @else
                                        Sin predicción
                                    @endif
                                </strong>
                            </div>

                            <div>
                                <span>Días utilizados</span>

                                <strong>
                                    {{ $animal['dias_utilizados'] ?? 0 }}
                                </strong>
                            </div>

                            <div>
                                <span>Días incompletos</span>

                                <strong>
                                    {{ $animal['dias_incompletos'] ?? 0 }}
                                </strong>
                            </div>
                        </div>

                        <div class="ia-interpretation">

                            <strong>
                                Interpretación GanTek
                            </strong>

                            <p>
                                {{ $animal['mensaje'] }}
                            </p>

                        </div>

                        @if ($animal['estado_ia'] === 'anomalia')
                            <form method="POST" action="{{ route('ia.agregar-agenda', $animal['ganado_id']) }}"
                                class="ia-agenda-action">
                                @csrf

                                <button type="submit" class="btn primary">
                                    Agregar a agenda
                                </button>
                            </form>
                        @endif
                    @endif

                </article>

                @empty

                    <p>
                        No hay animales disponibles para analizar.
                    </p>

                @endforelse

            </section>

        </div>

    @endsection
