<?php

namespace App\Services;

use App\Models\Ganado;
use App\Models\RegistroOrdenio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AnalisisProduccionIAService
{
    /**
     * Analiza la producción de todas las vacas activas del usuario.
     */
    public function analizarProduccion(User $user): array
    {
        $ganado = Ganado::forUser($user)
            ->where('estado', 'Activo')
            ->get();

        $resultados = [];

        foreach ($ganado as $animal) {
            $analisis = $this->analizarAnimal($user, $animal->id);

            if ($analisis !== null) {
                $resultados[] = $analisis;
            }
        }

        return [
            'fecha_analisis' => now()->toDateTimeString(),
            'total_animales_analizados' => count($resultados),
            'resumen' => $this->generarResumen($resultados),
            'animales' => $resultados,
        ];
    }

    /**
     * Analiza una vaca individual.
     */
    public function analizarAnimal(User $user, int $ganadoId): ?array
    {
        $hoy = Carbon::today();

        $inicioPeriodoReciente = $hoy->copy()->subDays(6);
        $finPeriodoReciente = $hoy;

        $inicioPeriodoAnterior = $hoy->copy()->subDays(13);
        $finPeriodoAnterior = $hoy->copy()->subDays(7);

        $registros = RegistroOrdenio::forUser($user)
            ->where('ganado_id', $ganadoId)
            ->whereBetween('fecha', [
                $inicioPeriodoAnterior->toDateString(),
                $finPeriodoReciente->toDateString(),
            ])
            ->orderBy('fecha')
            ->get();

        if ($registros->isEmpty()) {
            return null;
        }

        $produccionReciente = $this->produccionDiaria(
            $registros,
            $inicioPeriodoReciente,
            $finPeriodoReciente
        );

        $produccionAnterior = $this->produccionDiaria(
            $registros,
            $inicioPeriodoAnterior,
            $finPeriodoAnterior
        );

        $promedioReciente = $produccionReciente->avg() ?? 0;
        $promedioAnterior = $produccionAnterior->avg() ?? 0;

        $variacionPorcentual = $this->calcularVariacion(
            $promedioAnterior,
            $promedioReciente
        );

        $tendencia = $this->determinarTendencia($produccionReciente);

        $diasConRegistroReciente = $produccionReciente->count();
        $diasConRegistroAnterior = $produccionAnterior->count();

        if ($diasConRegistroReciente < 3) {
            $estado = 'sin_datos_suficientes';
        } else {
            $estado = $this->clasificarEstado(
                $promedioReciente,
                $variacionPorcentual
            );
        }

        $ganado = Ganado::forUser($user)
            ->with('lote')
            ->find($ganadoId);

        return [
            'ganado_id' => $ganadoId,
            'arete' => $ganado?->arete_siniiga ?? 'Sin arete',
            'lote' => $ganado?->lote?->nombre ?? 'Sin lote',

            'dias_con_registro_reciente' => $diasConRegistroReciente,
            'dias_con_registro_anterior' => $diasConRegistroAnterior,

            'promedio_anterior' => round($promedioAnterior, 2),
            'promedio_reciente' => round($promedioReciente, 2),

            'variacion_porcentual' => round($variacionPorcentual, 2),

            'tendencia' => $tendencia,
            'estado' => $estado,

            'produccion_ultimos_7_dias' => $produccionReciente->map(
                fn($litros, $fecha) => [
                    'fecha' => $fecha,
                    'litros' => round($litros, 2),
                ]
            )->values()->toArray(),

            'mensaje' => $this->generarMensaje(
                $estado,
                $tendencia,
                $promedioReciente,
                $variacionPorcentual
            ),
        ];
    }

    /**
     * Agrupa los ordeños por día.
     */
    private function produccionDiaria(
        Collection $registros,
        Carbon $inicio,
        Carbon $fin
    ): Collection {
        return $registros
            ->filter(function ($registro) use ($inicio, $fin) {
                $fecha = Carbon::parse($registro->fecha);

                return $fecha->betweenIncluded($inicio, $fin);
            })
            ->groupBy(function ($registro) {
                return Carbon::parse($registro->fecha)->toDateString();
            })
            ->map(function ($registrosDelDia) {
                return (float) $registrosDelDia->sum('litros');
            })
            ->sortKeys();
    }

    /**
     * Calcula la variación porcentual entre dos periodos.
     */
    private function calcularVariacion(
        float $anterior,
        float $reciente
    ): float {
        if ($anterior <= 0) {
            return 0;
        }

        return (($reciente - $anterior) / $anterior) * 100;
    }

    /**
     * Determina la tendencia de los últimos días.
     */
    private function determinarTendencia(Collection $produccion): string
    {
        $valores = $produccion->values();

        if ($valores->count() < 3) {
            return 'sin_datos_suficientes';
        }

        $mitad = (int) floor($valores->count() / 2);

        $primeraParte = $valores
            ->take($mitad)
            ->avg();

        $segundaParte = $valores
            ->slice($mitad)
            ->avg();

        if ($primeraParte <= 0) {
            return 'sin_datos_suficientes';
        }

        $variacion = (($segundaParte - $primeraParte) / $primeraParte) * 100;

        if ($variacion >= 10) {
            return 'ascendente';
        }

        if ($variacion <= -10) {
            return 'descendente';
        }

        return 'estable';
    }

    /**
     * Clasifica el nivel de riesgo productivo.
     */
    private function clasificarEstado(
        float $promedioReciente,
        float $variacion
    ): string {
        if ($promedioReciente < 4 && $variacion <= -30) {
            return 'critica';
        }

        if ($variacion <= -20) {
            return 'baja';
        }

        if ($variacion <= -10) {
            return 'atencion';
        }

        if ($promedioReciente < 4) {
            return 'atencion';
        }

        return 'normal';
    }

    /**
     * Genera un mensaje comprensible para el usuario.
     */
    private function generarMensaje(
        string $estado,
        string $tendencia,
        float $promedio,
        float $variacion
    ): string {
        $promedio = round($promedio, 2);
        $variacion = round($variacion, 2);

        return match ($estado) {
            'sin_datos_suficientes' =>
                "Aún no existen suficientes registros de producción para realizar un análisis confiable. "
                . "Se requieren al menos 3 días con producción registrada.",

            'critica' =>
            "La producción presenta una disminución crítica. "
                . "El promedio reciente es de {$promedio} L diarios "
                . "con una variación de {$variacion}%. "
                . "Se recomienda revisar al animal y dar seguimiento.",

            'baja' =>
            "La producción presenta una reducción importante. "
                . "El promedio reciente es de {$promedio} L diarios "
                . "y la tendencia es {$tendencia}.",

            'atencion' =>
            "La producción requiere atención. "
                . "El promedio reciente es de {$promedio} L diarios "
                . "con una variación de {$variacion}%.",

            default =>
            "La producción se mantiene dentro de un comportamiento normal. "
                . "El promedio reciente es de {$promedio} L diarios "
                . "y la tendencia es {$tendencia}.",
        };
    }

    /**
     * Genera un resumen general.
     */
    private function generarResumen(array $resultados): array
    {
        $coleccion = collect($resultados);

        return [
            'normal' => $coleccion
                ->where('estado', 'normal')
                ->count(),

            'atencion' => $coleccion
                ->where('estado', 'atencion')
                ->count(),

            'baja' => $coleccion
                ->where('estado', 'baja')
                ->count(),

            'critica' => $coleccion
                ->where('estado', 'critica')
                ->count(),

            'sin_datos_suficientes' => $coleccion
                ->where('estado', 'sin_datos_suficientes')
                ->count(),
        ];
    }
}
