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


                </div>

                <div class="agenda-actions">
                    <button class="btn primary" type="submit">
                        Consultar
                    </button>
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

                @php
                    $grupos = $resultado['grupos'] ?? [];

                    $produccionTotal = collect($grupos)->sum('produccion_actual');

                    $promedioGrupo = count($grupos) > 0 ? $produccionTotal / count($grupos) : 0;

                    $gruposConAnomalia = collect($grupos)->where('anomalia_modelo', true)->count();

                    $grupoMayorProduccion = collect($grupos)->sortByDesc('produccion_actual')->first();

                    $grupoMenorProduccion = collect($grupos)->sortBy('produccion_actual')->first();
                @endphp

                <div class="ia-kpi-grid">

                    <div class="ia-kpi-card ia-kpi-green">
                        <span>Producción total</span>

                        <strong>
                            {{ number_format($produccionTotal, 2) }} L
                        </strong>

                        <small>
                            Producción actual de los grupos analizados
                        </small>
                    </div>

                    <div class="ia-kpi-card ia-kpi-blue">
                        <span>Promedio por grupo</span>

                        <strong>
                            {{ number_format($promedioGrupo, 2) }} L
                        </strong>

                        <small>
                            Promedio de producción por grupo
                        </small>
                    </div>

                    <div class="ia-kpi-card ia-kpi-yellow">
                        <span>Horizonte de predicción</span>

                        <strong>
                            {{ $resultado['modelo']['horizonte_dias'] }} días
                        </strong>

                        <small>
                            Periodo proyectado por el modelo
                        </small>
                    </div>

                    <div class="ia-kpi-card ia-kpi-red">
                        <span>Grupos con anomalía</span>

                        <strong>
                            {{ $gruposConAnomalia }}
                        </strong>

                        <small>
                            de {{ count($grupos) }} grupos analizados
                        </small>
                    </div>

                </div>

            </div>


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

            @if (!empty($resultado['grupos']))
                <div class="card">

                    <h3>Producción por grupo</h3>

                    <p>
                        Comparación de la producción actual entre los grupos analizados.
                    </p>

                    <div class="ia-chart-container">
                        <canvas id="chart-produccion-grupos"></canvas>
                    </div>

                </div>
            @endif

            <!--Resumen inteligente + Hallazgos y recomendaciones. -->
            @if (!empty($resultado['grupos']))

                @php
                    $grupos = collect($resultado['grupos']);

                    $grupoMejor = $grupos->sortByDesc('produccion_actual')->first();

                    $grupoMenor = $grupos->sortBy('produccion_actual')->first();

                    $gruposAnomalia = $grupos->where('anomalia_modelo', true);

                    $tendenciasAscendentes = $grupos->where('tendencia', 'ascendente')->count();

                    $tendenciasDescendentes = $grupos->where('tendencia', 'descendente')->count();
                @endphp

                <div class="ia-insights-grid">

                    <div class="card">

                        <div class="ia-insight-title">
                            <div class="ia-insight-icon">
                                🤖
                            </div>

                            <div>
                                <h3>Resumen inteligente</h3>
                                <small>
                                    Interpretación general de la producción analizada
                                </small>
                            </div>
                        </div>

                        <p>
                            Se analizaron
                            <strong>{{ $grupos->count() }}</strong>
                            grupos de ordeña.
                        </p>

                        @if ($grupoMejor)
                            <p>
                                El grupo con mayor producción actual es
                                <strong>{{ $grupoMejor['grupo'] }}</strong>
                                con
                                <strong>
                                    {{ number_format($grupoMejor['produccion_actual'], 2) }}
                                    L
                                </strong>.
                            </p>
                        @endif

                        @if ($grupoMenor)
                            <p>
                                El grupo con menor producción actual es
                                <strong>{{ $grupoMenor['grupo'] }}</strong>
                                con
                                <strong>
                                    {{ number_format($grupoMenor['produccion_actual'], 2) }}
                                    L
                                </strong>.
                            </p>
                        @endif

                        <p>
                            Tendencias ascendentes:
                            <strong>{{ $tendenciasAscendentes }}</strong>
                            |
                            Tendencias descendentes:
                            <strong>{{ $tendenciasDescendentes }}</strong>
                        </p>

                    </div>

                    <div class="card">

                        <div class="ia-insight-title">
                            <div class="ia-insight-icon ia-insight-icon-warning">
                                💡
                            </div>

                            <div>
                                <h3>Hallazgos y recomendaciones</h3>
                                <small>
                                    Aspectos relevantes detectados en los grupos
                                </small>
                            </div>
                        </div>

                        @if ($gruposAnomalia->count() > 0)

                            @foreach ($gruposAnomalia as $grupo)
                                <div class="ia-finding ia-finding-warning">

                                    <strong>
                                        {{ $grupo['grupo'] }}
                                    </strong>

                                    <p>
                                        Se detectó un comportamiento atípico
                                        en la producción del grupo.
                                    </p>

                                    <small>
                                        {{ $grupo['clasificacion']['mensaje'] }}
                                    </small>

                                </div>
                            @endforeach
                        @else
                            <div class="ia-finding ia-finding-success">

                                <strong>
                                    Sin anomalías relevantes
                                </strong>

                                <p>
                                    No se detectaron comportamientos atípicos
                                    en los grupos analizados.
                                </p>

                            </div>

                        @endif

                        @if ($grupoMenor)
                            <div class="ia-finding">

                                <strong>
                                    Grupo a revisar:
                                    {{ $grupoMenor['grupo'] }}
                                </strong>

                                <p>
                                    Presenta la producción más baja del periodo.
                                    Se recomienda revisar alimentación,
                                    cantidad de vacas, duración del ordeño y
                                    posibles factores sanitarios.
                                </p>

                            </div>
                        @endif

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

            const barrasCanvas =
                document.getElementById(
                    'chart-produccion-grupos'
                );

            if (barrasCanvas) {

                const grupos =
                    @json($resultado['grupos'] ?? []);

                const labels =
                    grupos.map(
                        grupo => grupo.grupo
                    );

                const valores =
                    grupos.map(
                        grupo => grupo.produccion_actual
                    );

                const coloresBarras = [
                    '#15803d',
                    '#2563eb',
                    '#f97316',
                    '#9333ea',
                    '#dc2626',
                    '#0891b2'
                ];

                new Chart(
                    barrasCanvas, {
                        type: 'bar',

                        data: {
                            labels: labels,

                            datasets: [{
                                label: 'Producción actual',

                                data: valores,

                                backgroundColor: coloresBarras,

                                borderRadius: 8
                            }]
                        },

                        options: {
                            responsive: true,
                            maintainAspectRatio: false,

                            plugins: {
                                legend: {
                                    display: false
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
                                        text: 'Grupo'
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
