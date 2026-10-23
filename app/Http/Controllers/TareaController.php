<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\AgendaIAService;

class TareaController extends Controller
{
    public function index(
        Request $request,
        AgendaIAService $agendaIA
    ): View {
        $user = $request->user();

        $agendaIA->actualizarTareasVencidas($user);

        $consulta = Tarea::where('user_id', $user->id);

        // Estadísticas de todas las tareas
        $estadisticas = [
            'pendientes' => (clone $consulta)
                ->where('estado', 'pendiente')->count(),

            'completadas' => (clone $consulta)
                ->where('estado', 'completada')->count(),

            'vencidas' => (clone $consulta)
                ->where('estado', 'vencida')->count(),

            'prioridad_alta' => (clone $consulta)
                ->where('estado', 'pendiente')
                ->where('prioridad', 'alta')->count(),

            'automaticas' => (clone $consulta)
                ->where('creada_por_ia', true)->count(),
        ];

        // Paginación de cinco tareas
        $tareas = $consulta
            ->orderBy('fecha')
            ->orderBy('hora')
            ->orderBy('id')
            ->paginate(5);

        return view('agenda.index', [
            'tareas' => $tareas,
            'estadisticas' => $estadisticas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'tipo' => [
                'required',
                'in:ordenio,vacunacion,veterinaria,produccion,general',
            ],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora' => ['nullable', 'date_format:H:i'],
            'prioridad' => ['required', 'in:baja,media,alta'],
            'recordatorio' => ['nullable', 'boolean'],
        ]);

        $data['user_id'] = $request->user()->id;
        $data['estado'] = 'pendiente';
        $data['creada_por_ia'] = false;
        $data['recordatorio'] = $request->boolean('recordatorio');

        Tarea::create($data);

        return redirect()
            ->route('agenda.index')
            ->with('success', 'Tarea creada correctamente.');
    }

    public function completar(
        Request $request,
        Tarea $tarea
    ): RedirectResponse {
        $this->validarPropietario($request, $tarea);

        $tarea->update([
            'estado' => 'completada',
        ]);

        return redirect()
            ->route('agenda.index')
            ->with('success', 'Tarea marcada como completada.');
    }

    public function cancelar(
        Request $request,
        Tarea $tarea
    ): RedirectResponse {
        $this->validarPropietario($request, $tarea);

        $tarea->update([
            'estado' => 'cancelada',
        ]);

        return redirect()
            ->route('agenda.index')
            ->with('success', 'Tarea cancelada.');
    }

    private function validarPropietario(
        Request $request,
        Tarea $tarea
    ): void {
        abort_if(
            $tarea->user_id !== $request->user()->id,
            403,
            'No tienes permiso para acceder a esta tarea.'
        );
    }
}
