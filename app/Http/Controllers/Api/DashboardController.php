<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ganado;
use App\Services\AlertaService;
use App\Services\ProduccionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        ProduccionService $produccion,
        AlertaService $alertas
    ): JsonResponse {
        $user = $request->user();

        // =====================================================
        // PRODUCCIÓN ÚLTIMOS 7 DÍAS
        // =====================================================

        $resumen = $produccion->resumen($user, [
            'desde' => today()->subDays(7)->toDateString(),
            'hasta' => today()->subDay()->toDateString(),
        ]);

        // =====================================================
        // PRODUCCIÓN DE HOY
        // =====================================================

        $litrosHoy = (float) $produccion->registros($user, [
            'desde' => today()->toDateString(),
            'hasta' => today()->toDateString(),
        ])->sum('litros');

        // =====================================================
        // PRODUCCIÓN DEL MES
        // =====================================================

        $litrosMes = (float) $produccion->registros($user, [
            'desde' => today()->startOfMonth()->toDateString(),
            'hasta' => today()->toDateString(),
        ])->sum('litros');

        // =====================================================
        // GANADO ACTIVO
        // =====================================================

        $ganadoActivoQuery = Ganado::forUser($user)
            ->where('estado', 'Activo');

        $ganadoActivo = (clone $ganadoActivoQuery)->count();

        // =====================================================
        // VACAS EN PRODUCCIÓN
        // =====================================================

        $vacasProduccionQuery = Ganado::forUser($user)
            ->where('estado', 'Activo')
            ->where('sexo', 'Hembra')
            ->where('estado_productivo', 'En producción');

        $vacasProduccion = (clone $vacasProduccionQuery)->count();

        // =====================================================
        // PROMEDIO DE HOY POR VACA
        // =====================================================

        $promedioPorVaca = $vacasProduccion > 0
            ? $litrosHoy / $vacasProduccion
            : 0.0;

        // =====================================================
        // LOTES DEL GANADO ACTIVO
        // =====================================================

        $lotesGanado = (clone $ganadoActivoQuery)
            ->whereHas('lote', fn ($query) => $query->where('estado', 'Activo'))
            ->with('lote:id,nombre')
            ->get()
            ->pluck('lote.nombre')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // =====================================================
        // LOTES CON VACAS EN PRODUCCIÓN
        // =====================================================

        $lotesProduccion = (clone $vacasProduccionQuery)
            ->whereHas('lote', fn ($query) => $query->where('estado', 'Activo'))
            ->with('lote:id,nombre')
            ->get()
            ->pluck('lote.nombre')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // =====================================================
        // ALERTAS
        // =====================================================

        $alertasProduccion = $alertas->produccion($user);
        $alertasLotes = $alertas->produccionLotes($user);

        $proximas = $alertas->proximas($user)
            ->with([
                'ganado:id,arete_siniiga,nombre,lote_id',
                'vacuna:id,nombre',
                'veterinario:id,nombre',
            ])
            ->orderBy('proxima_aplicacion')
            ->get();

        $vencidas = $alertas->vencidas($user)
            ->with([
                'ganado:id,arete_siniiga,nombre,lote_id',
                'vacuna:id,nombre',
            ])
            ->orderBy('proxima_aplicacion')
            ->get();

        // =====================================================
        // ANIMALES RECIENTES
        // =====================================================

        $animalesRecientes = Ganado::forUser($user)
            ->with('lote.finca')
            ->latest()
            ->limit(5)
            ->get();

        // =====================================================
        // RESPUESTA API
        // =====================================================

        return response()->json([
            'data' => [
                'resumen' => [
                    'ganado_activo' => $ganadoActivo,

                    'vacas_en_produccion' => $vacasProduccion,

                    'litros_hoy' => $litrosHoy,

                    'litros_mes' => $litrosMes,

                    'promedio_por_vaca' => (float) $promedioPorVaca,

                    'promedio_ultimos_7_dias' => (float) $resumen['promedio'],

                    'dias_con_produccion' => (int) $resumen['dias'],

                    'alertas_produccion' =>
                        $alertasProduccion->count() + $alertasLotes->count(),

                    'alertas_vacas' => $alertasProduccion->count(),

                    'alertas_lotes' => $alertasLotes->count(),

                    'vacunas_proximas' => $proximas->count(),

                    'vacunas_vencidas' => $vencidas->count(),

                    'lotes_ganado' => $lotesGanado,

                    'lotes_produccion' => $lotesProduccion,
                ],

                'animales_recientes' => $animalesRecientes,

                'alertas' => [
                    'vacas' => $alertasProduccion,
                    'lotes' => $alertasLotes,
                    'vacunas_proximas' => $proximas,
                    'vacunas_vencidas' => $vencidas,
                ],
            ],
        ]);
    }
}