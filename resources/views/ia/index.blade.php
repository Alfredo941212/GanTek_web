@extends('layouts.app')

@section('title', 'IA de Producción | GanTek')

@section('heading', 'IA de Producción')
@section('subheading', 'Consulta predicciones, tendencias y anomalías en la producción de leche.')

@section('content')

    <div class="dashboard-page">

        <div class="card">

            <h3>Consultar predicción</h3>

            <form method="GET" action="{{ route('ia.index') }}">

                <div class="form-grid">

                    <div class="form-group">
                        <label for="horizonte">
                            Horizonte
                        </label>

                        <select name="horizonte" id="horizonte">
                            <option value="7" @selected($horizonte == 7)>
                                1 semana
                            </option>

                            <option value="30" @selected($horizonte == 30)>
                                1 mes
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="grupo_id">
                            Grupo
                        </label>

                        <select name="grupo_id" id="grupo_id">
                            <option value="">
                                Todos los grupos
                            </option>

                            @foreach ($gruposDisponibles as $grupo)
                                <option value="{{ $grupo->id }}" @selected((string) $grupoSeleccionado === (string) $grupo->id)>
                                    {{ $grupo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fecha_inicio">
                            Fecha inicio
                        </label>

                        <input type="date" name="fecha_inicio" id="fecha_inicio" value="{{ $fechaInicio }}">
                    </div>

                    <div class="form-group">
                        <label for="fecha_fin">
                            Fecha fin
                        </label>

                        <input type="date" name="fecha_fin" id="fecha_fin" value="{{ $fechaFin }}">
                    </div>

                </div>

                <div class="form-actions">

                    <button type="submit" class="btn-primary">
                        Consultar
                    </button>

                    <a href="{{ route('ia.index') }}" class="btn-secondary">
                        Limpiar
                    </a>

                </div>

            </form>

        </div>

        @if ($error)
            <div class="alert error">
                {{ $error }}
            </div>
        @endif

        @if ($resultado)

            <div class="card">

                <h3>Resumen del modelo</h3>

                <div class="stats-grid">

                    <div class="stat-card">
                        <span>Modelo de predicción</span>
                        <strong>
                            {{ $resultado['modelo']['prediccion'] }}
                        </strong>
                    </div>

                    <div class="stat-card">
                        <span>Detección de anomalías</span>
                        <strong>
                            {{ $resultado['modelo']['deteccion_anomalias'] }}
                        </strong>
                    </div>

                    <div class="stat-card">
                        <span>Registros analizados</span>
                        <strong>
                            {{ $resultado['modelo']['total_registros'] }}
                        </strong>
                    </div>

                    <div class="stat-card">
                        <span>Horizonte</span>
                        <strong>
                            {{ $resultado['modelo']['horizonte_dias'] }} días
                        </strong>
                    </div>

                </div>

            </div>

            @if (isset($resultado['modelo']['metricas']))
                <div class="card">

                    <h3>Métricas del modelo</h3>

                    @php
                        $metricas = $resultado['modelo']['metricas'];
                    @endphp

                    <div class="stats-grid">

                        <div class="stat-card">
                            <span>MAE</span>
                            <strong>
                                {{ number_format($metricas['mae'], 2) }}
                                L
                            </strong>
                        </div>

                        <div class="stat-card">
                            <span>RMSE</span>
                            <strong>
                                {{ number_format($metricas['rmse'], 2) }}
                                L
                            </strong>
                        </div>

                        <div class="stat-card">
                            <span>R²</span>
                            <strong>
                                {{ number_format($metricas['r2'], 3) }}
                            </strong>
                        </div>

                        <div class="stat-card">
                            <span>Entrenamiento</span>
                            <strong>
                                {{ $metricas['registros_entrenamiento'] }}
                            </strong>
                        </div>

                        <div class="stat-card">
                            <span>Prueba</span>
                            <strong>
                                {{ $metricas['registros_prueba'] }}
                            </strong>
                        </div>

                    </div>

                </div>
            @endif

            @if (empty($grupoSeleccionado))
                <div class="card">

                    <h3>Comparativa de producción</h3>

                    <p>
                        Producción histórica y predicción de todos los grupos.
                    </p>

                    <div class="ia-chart-container" data-groups='@json($resultado['grupos'])'>
                        <canvas id="chart-comparativa"></canvas>
                    </div>

                </div>
            @elseif (!empty($resultado['grupos']))
                @php
                    $grupoGrafica = $resultado['grupos'][0];
                @endphp

                <div class="card">

                    <h3>
                        Producción de {{ $grupoGrafica['grupo'] }}
                    </h3>

                    <p>
                        Historial de producción y predicción generada por IA.
                    </p>

                    <div class="ia-chart-container" data-group='@json($grupoGrafica)'>
                        <canvas id="chart-individual"></canvas>
                    </div>

                </div>
            @endif

            @foreach ($resultado['grupos'] as $grupo)
                <div class="card">

                    <div class="ia-group-header">

                        <div>
                            <h3>
                                {{ $grupo['grupo'] }}
                            </h3>

                            <small>
                                Tendencia:
                                {{ ucfirst(str_replace('_', ' ', $grupo['tendencia'])) }}
                            </small>
                        </div>

                        <span
                            class="
                            ia-status
                            ia-status-{{ $grupo['clasificacion']['color'] }}
                        ">
                            {{ ucfirst(str_replace('_', ' ', $grupo['clasificacion']['nivel'])) }}
                        </span>

                    </div>

                    <div class="stats-grid">

                        <div class="stat-card">
                            <span>Producción actual</span>
                            <strong>
                                {{ number_format($grupo['produccion_actual'], 2) }}
                                L
                            </strong>
                        </div>

                        <div class="stat-card">
                            <span>Vacas</span>
                            <strong>
                                {{ $grupo['cantidad_vacas'] }}
                            </strong>
                        </div>

                        <div class="stat-card">
                            <span>Promedio por vaca</span>
                            <strong>
                                {{ number_format($grupo['promedio_por_vaca'], 2) }}
                                L
                            </strong>
                        </div>

                        <div class="stat-card">
                            <span>Anomalía IA</span>
                            <strong>
                                {{ $grupo['anomalia_modelo'] ? 'Sí' : 'No' }}
                            </strong>
                        </div>

                    </div>

                    <div class="ia-message">
                        {{ $grupo['clasificacion']['mensaje'] }}
                    </div>

                </div>
            @endforeach

        @endif

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const colores = [
                '#2563eb',
                '#16a34a',
                '#dc2626',
                '#9333ea',
                '#f59e0b',
                '#0891b2',
                '#db2777',
                '#65a30d'
            ];

            /*
            |--------------------------------------------------------------------------
            | GRÁFICA COMPARATIVA
            |--------------------------------------------------------------------------
            */

            const comparativaContainer =
                document.querySelector(
                    '[data-groups]'
                );

            if (comparativaContainer) {

                const grupos =
                    JSON.parse(
                        comparativaContainer.dataset.groups
                    );

                const canvas =
                    comparativaContainer.querySelector(
                        'canvas'
                    );

                const fechas = new Set();

                grupos.forEach(function(grupo) {

                    (grupo.historial || []).forEach(
                        item => fechas.add(item.fecha)
                    );

                    (grupo.predicciones || []).forEach(
                        item => fechas.add(item.fecha)
                    );

                });

                const labels =
                    Array.from(fechas).sort();

                const datasets = [];

                grupos.forEach(function(
                    grupo,
                    index
                ) {

                    const color =
                        colores[
                            index % colores.length
                        ];

                    const historial =
                        grupo.historial || [];

                    const predicciones =
                        grupo.predicciones || [];

                    const mapaHistorico = {};

                    historial.forEach(function(item) {
                        mapaHistorico[item.fecha] =
                            item.litros;
                    });

                    const mapaPrediccion = {};

                    predicciones.forEach(function(item) {
                        mapaPrediccion[item.fecha] =
                            item.litros;
                    });

                    datasets.push({
                        label: grupo.grupo +
                            ' - Histórico',

                        data: labels.map(
                            fecha =>
                            mapaHistorico[fecha] ??
                            null
                        ),

                        borderColor: color,
                        backgroundColor: color,

                        tension: 0.3,
                        pointRadius: 3,
                        spanGaps: false
                    });

                    datasets.push({
                        label: grupo.grupo +
                            ' - Predicción',

                        data: labels.map(
                            fecha =>
                            mapaPrediccion[fecha] ??
                            null
                        ),

                        borderColor: color,
                        backgroundColor: color,

                        borderDash: [
                            6,
                            4
                        ],

                        tension: 0.3,
                        pointRadius: 3,
                        spanGaps: false
                    });

                });

                new Chart(
                    canvas, {
                        type: 'line',

                        data: {
                            labels: labels,
                            datasets: datasets
                        },

                        options: {
                            responsive: true,
                            maintainAspectRatio: false,

                            interaction: {
                                mode: 'index',
                                intersect: false
                            },

                            plugins: {
                                legend: {
                                    display: true
                                }
                            },

                            scales: {
                                y: {
                                    beginAtZero: true,

                                    title: {
                                        display: true,
                                        text: 'Litros'
                                    }
                                },

                                x: {
                                    title: {
                                        display: true,
                                        text: 'Fecha'
                                    }
                                }
                            }
                        }
                    }
                );
            }

            /*
            |--------------------------------------------------------------------------
            | GRÁFICA INDIVIDUAL
            |--------------------------------------------------------------------------
            */

            const individualContainer =
                document.querySelector(
                    '[data-group]'
                );

            if (individualContainer) {

                const grupo =
                    JSON.parse(
                        individualContainer.dataset.group
                    );

                const canvas =
                    individualContainer.querySelector(
                        'canvas'
                    );

                const historial =
                    grupo.historial || [];

                const predicciones =
                    grupo.predicciones || [];

                const labels = [
                    ...historial.map(
                        item => item.fecha
                    ),

                    ...predicciones.map(
                        item => item.fecha
                    )
                ];

                const historicoData = [
                    ...historial.map(
                        item => item.litros
                    ),

                    ...predicciones.map(
                        () => null
                    )
                ];

                const prediccionData = [
                    ...historial.map(
                        (item, index) => {

                            if (
                                index ===
                                historial.length - 1
                            ) {
                                return item.litros;
                            }

                            return null;
                        }
                    ),

                    ...predicciones.map(
                        item => item.litros
                    )
                ];

                new Chart(
                    canvas, {
                        type: 'line',

                        data: {
                            labels: labels,

                            datasets: [{
                                    label: 'Histórico',

                                    data: historicoData,

                                    borderColor: '#2563eb',

                                    backgroundColor: 'rgba(37, 99, 235, 0.10)',

                                    tension: 0.3,
                                    pointRadius: 4
                                },

                                {
                                    label: 'Predicción IA',

                                    data: prediccionData,

                                    borderColor: '#f59e0b',

                                    backgroundColor: 'rgba(245, 158, 11, 0.10)',

                                    borderDash: [
                                        6,
                                        4
                                    ],

                                    tension: 0.3,
                                    pointRadius: 4
                                }
                            ]
                        },

                        options: {
                            responsive: true,
                            maintainAspectRatio: false,

                            plugins: {
                                legend: {
                                    display: true
                                }
                            },

                            scales: {
                                y: {
                                    beginAtZero: true,

                                    title: {
                                        display: true,
                                        text: 'Litros'
                                    }
                                },

                                x: {
                                    title: {
                                        display: true,
                                        text: 'Fecha'
                                    }
                                }
                            }
                        }
                    }
                );
            }

        });
    </script>

@endsection
