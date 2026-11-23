<?php

namespace App\Services;

use App\Models\SesionOrdenio;
use App\Models\User;
use Carbon\Carbon;

class ReporteProduccionService
{
    public function resumen(
        User $user,
        string $desde,
        string $hasta
    ): array {
        $sesiones = SesionOrdenio::query()
            ->where('user_id', $user->id)
            ->where('estado', 'finalizado')
            ->whereBetween('fecha', [
                $desde,
                $hasta,
            ])
            ->with([
                'grupo:id,nombre,orden',
            ])
            ->orderBy('fecha')
            ->get();

        $total = (float) $sesiones
            ->sum('litros_totales');

        $produccionDiaria = $sesiones
            ->groupBy(function ($sesion) {
                return Carbon::parse(
                    $sesion->fecha
                )->toDateString();
            })
            ->map(function ($sesionesDia, $fecha) {
                return [
                    'fecha' => $fecha,
                    'litros' => round(
                        (float) $sesionesDia
                            ->sum('litros_totales'),
                        2
                    ),
                    'sesiones' =>
                        $sesionesDia->count(),
                ];
            })
            ->values();

        $dias = $produccionDiaria->count();

        $promedio = $dias > 0
            ? $total / $dias
            : 0;

        $porGrupo = $sesiones
            ->groupBy('grupo_ordenio_id')
            ->map(function ($sesionesGrupo) {
                $primera = $sesionesGrupo->first();

                return [
                    'grupo' =>
                        $primera->grupo?->nombre
                        ?? 'Sin grupo',

                    'litros' => round(
                        (float) $sesionesGrupo
                            ->sum('litros_totales'),
                        2
                    ),

                    'sesiones' =>
                        $sesionesGrupo->count(),
                ];
            })
            ->values();

        return [
            'total' => round($total, 2),

            'promedio' => round(
                $promedio,
                2
            ),

            'dias' => $dias,

            'sesiones' =>
                $sesiones->count(),

            'vacas_atendidas' =>
                (int) $sesiones
                    ->sum('cantidad_vacas'),

            'diaria' =>
                $produccionDiaria,

            'grupos' =>
                $porGrupo,
        ];
    }
}