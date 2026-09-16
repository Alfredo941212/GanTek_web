<?php

namespace App\Http\Controllers;

use App\Models\Ganado;
use App\Services\AlertaService;
use App\Services\ProduccionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, ProduccionService $produccion, AlertaService $alertas): View
    {
        $user = $request->user();
        $resumen = $produccion->resumen($user, ['desde' => today()->subDays(7)->toDateString(), 'hasta' => today()->subDay()->toDateString()]);

        return view('dashboard', [
            'activos' => Ganado::forUser($user)->where('estado', 'Activo')->count(),
            'litrosHoy' => $produccion->registros($user, ['desde' => today()->toDateString(), 'hasta' => today()->toDateString()])->sum('litros'),
            'promedio' => $resumen['promedio'],
            'diasPromedio' => $resumen['dias'],
            'animales' => Ganado::forUser($user)->with('lote.finca')->latest()->limit(5)->get(),
            'alertasProduccion' => $alertas->produccion($user),
            'proximas' => $alertas->proximas($user)->with(['ganado', 'vacuna', 'veterinario'])->orderBy('proxima_aplicacion')->get(),
            'vencidas' => $alertas->vencidas($user)->with(['ganado', 'vacuna'])->orderBy('proxima_aplicacion')->get(),
        ]);
    }
}
