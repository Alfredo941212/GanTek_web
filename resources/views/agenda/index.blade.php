@extends('layouts.app')

@section('title', 'Agenda inteligente | GanTek')

@section('heading', 'Agenda inteligente')
@section('subheading', 'Organiza tareas, recordatorios y actividades generadas por GanTek.')

@section('content')

    <div class="dashboard-page">

        @if (session('success'))
            <div class="alert success">
                {{ session('success') }}
            </div>
        @endif

        <div class="stats-grid">

            <div class="stat-card">
                <span>Pendientes</span>
                <strong>
                    {{ $tareas->where('estado', 'pendiente')->count() }}
                </strong>
                <small>Actividades por realizar</small>
            </div>

            <div class="stat-card">
                <span>Completadas</span>
                <strong>
                    {{ $tareas->where('estado', 'completada')->count() }}
                </strong>
                <small>Tareas finalizadas</small>
            </div>

            <div class="stat-card">
                <span>Vencidas</span>
                <strong>
                    {{ $tareas->where('estado', 'vencida')->count() }}
                </strong>
                <small>Actividades fuera de fecha</small>
            </div>

            <div class="stat-card">
                <span>Prioridad alta</span>
                <strong>
                    {{ $tareas->where('estado', 'pendiente')->where('prioridad', 'alta')->count() }}
                </strong>
                <small>Requieren atención</small>
            </div>

            <div class="stat-card">
                <span>Automática</span>
                <strong>
                    {{ $tareas->where('creada_por_ia', true)->count() }}
                </strong>
                <small>Tareas automáticas</small>
            </div>

        </div>

        <div class="two-columns">

            <section class="panel">

                <h2>Nueva tarea</h2>

                <form method="POST" action="{{ route('agenda.store') }}">
                    @csrf

                    <div class="form-group">
                        <label for="titulo">Título</label>

                        <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}" required>

                        @error('titulo')
                            <small class="error-text">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>

                        <textarea id="descripcion" name="descripcion" rows="3">{{ old('descripcion') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="tipo">Tipo</label>

                        <select id="tipo" name="tipo" required>
                            <option value="general">General</option>
                            <option value="ordenio">Ordeña</option>
                            <option value="vacunacion">Vacunación</option>
                            <option value="veterinaria">Veterinaria</option>
                            <option value="produccion">Producción</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fecha">Fecha</label>

                        <input type="date" id="fecha" name="fecha"
                            value="{{ old('fecha', today()->toDateString()) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="hora">Hora</label>

                        <input type="time" id="hora" name="hora" value="{{ old('hora') }}">
                    </div>

                    <div class="form-group">
                        <label for="prioridad">Prioridad</label>

                        <select id="prioridad" name="prioridad" required>
                            <option value="baja">Baja</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>

                    <div class="form-group">

                        <label>
                            <input type="checkbox" name="recordatorio" value="1" checked>

                            Activar recordatorio
                        </label>

                    </div>

                    <button class="btn primary" type="submit">
                        Crear tarea
                    </button>

                </form>

            </section>


            <section class="panel">

                <h2>Agenda</h2>

                @forelse($tareas as $tarea)
                    <div class="agenda-task agenda-{{ $tarea->prioridad }} {{ $tarea->estado }}">

                        <div class="agenda-task-header">

                            <div>
                                <strong>
                                    {{ $tarea->titulo }}
                                </strong>

                                <div class="agenda-meta">

                                    {{ $tarea->fecha->format('d/m/Y') }}

                                    @if ($tarea->hora)
                                        · {{ \Carbon\Carbon::parse($tarea->hora)->format('H:i') }}
                                    @endif

                                </div>
                            </div>

                            <span class="agenda-status">
                                {{ ucfirst($tarea->estado) }}
                            </span>

                        </div>

                        @if ($tarea->descripcion)
                            <p>
                                {{ $tarea->descripcion }}
                            </p>
                        @endif

                        <div class="agenda-labels">

                            <span>
                                {{ ucfirst($tarea->tipo) }}
                            </span>

                            <span>
                                Prioridad {{ $tarea->prioridad }}
                            </span>

                            @if ($tarea->creada_por_ia)
                                <span>
                                    Automática
                                </span>
                            @else
                                <span>
                                    Manual
                                </span>
                            @endif

                        </div>

                        @if ($tarea->estado === 'pendiente')
                            <div class="agenda-actions">

                                <form method="POST" action="{{ route('agenda.completar', $tarea) }}">
                                    @csrf
                                    @method('PATCH')

                                    <button class="btn primary" type="submit">
                                        Completar
                                    </button>

                                </form>

                                <form method="POST" action="{{ route('agenda.cancelar', $tarea) }}">
                                    @csrf
                                    @method('PATCH')

                                    <button class="btn" type="submit">
                                        Cancelar
                                    </button>

                                </form>

                            </div>
                        @endif

                    </div>

                @empty

                    <div class="dashboard-message">
                        No hay tareas registradas.
                    </div>
                @endforelse

                @if ($tareas->hasPages())
                    <div class="agenda-pagination">
                        {{ $tareas->links() }}
                    </div>
                @endif

            </section>

            </section>

        </div>

    </div>

@endsection
