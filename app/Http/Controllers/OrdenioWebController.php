<?php

namespace App\Http\Controllers;

use App\Models\SesionOrdenio;
use Illuminate\Http\Request;

class OrdenioWebController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $sesionesHoy = SesionOrdenio::query()
            ->where('user_id', $userId)
            ->whereDate('fecha', now()->toDateString())
            ->with([
                'grupo:id,nombre,orden',
            ])
            ->get()
            ->sortBy(
                fn (SesionOrdenio $sesion) =>
                    $sesion->grupo?->orden ?? 999
            )
            ->values();

        $historial = SesionOrdenio::query()
            ->where('user_id', $userId)
            ->where('estado', 'finalizado')
            ->with([
                'grupo:id,nombre,orden',
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('ordenios.index', [
            'sesionesHoy' => $sesionesHoy,
            'historial' => $historial,
        ]);
    }
}