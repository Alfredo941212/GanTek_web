<?php

namespace App\Services;

use App\Models\Ganado;
use App\Models\RegistroOrdenio;
use App\Models\User;
use Carbon\Carbon;

class AnalisisPredictivoIAService
{
    public function __construct(
        private PrediccionProduccionIAService $prediccionIA
    ) {}

    public function analizar(User $user): array
    {
        $animales = Ganado::forUser($user)
            ->where('estado', 'Activo')
            ->where('sexo', 'Hembra')
            ->where('estado_productivo', 'En producción')
            ->get();

        $resultados = [];

        foreach ($animales as $animal) {
            $resultados[] = $this->analizarAnimal(
                $user,
                $animal
            );
        }

        return [
            'fecha_analisis' => now()->format('Y-m-d H:i:s'),
            'total_animales' => count($resultados),
            'animales' => $resultados,
        ];
    }

    private function analizarAnimal(
        User $user,
        Ganado $animal
    ): array {
        $registros = RegistroOrdenio::where('ganado_id', $animal->id)
            ->whereDate('fecha', '>=', $animal->fecha_ingreso)
            ->orderBy('fecha')
            ->get();

        /*
         * Sumamos las ordeñas del mismo día.
         * Ejemplo:
         *
         * Mañana = 4 L
         * Tarde  = 3 L
         *
         * Total del día = 7 L
         */
        $registrosPorDia = $registros
            ->groupBy(function ($registro) {
                return Carbon::parse($registro->fecha)
                    ->toDateString();
            });

        $diasIncompletos = $registrosPorDia
            ->filter(function ($registrosDia) {
                return $registrosDia->count() < 2;
            })
            ->count();

        $produccionDiaria = $registrosPorDia
            ->filter(function ($registrosDia) {
                return $registrosDia->count() >= 2;
            })
            ->map(function ($registrosDia) {
                return round(
                    (float) $registrosDia->sum('litros'),
                    2
                );
            })
            ->sortKeys();

        $diasRegistrados = $produccionDiaria->count();

        /*
 * Para evaluar el último día necesitamos:
 *
 * 7 días para entrenar
 * + 1 día para comparar
 *
 * Por eso pedimos mínimo 8 días.
 */
        if ($diasRegistrados < 8) {
            return [
                'ganado_id' => $animal->id,
                'arete' => $animal->arete_siniiga,
                'estado_ia' => 'sin_datos_suficientes',
                'dias_registrados' => $diasRegistrados,
                'dias_incompletos' => $diasIncompletos,
                'mensaje' =>
                'Se requieren al menos 8 días completos con producción registrada para realizar el análisis predictivo.',
            ];
        }

        /*
 * Usamos como máximo los últimos 30 días.
 */
        $produccionDiaria = $produccionDiaria->take(-30);

        $fechas = $produccionDiaria
            ->keys()
            ->values();

        $litros = $produccionDiaria
            ->values()
            ->map(fn($valor) => (float) $valor)
            ->values();

        /*
         * Último día conocido.
         */
        $produccionReal = (float) $litros->last();
        $fechaUltimaProduccion = $fechas->last();

        /*
         * Quitamos el último día para preguntarle al modelo:
         *
         * "Con base en los días anteriores,
         * ¿cuánto esperabas que produjera este día?"
         */
        $historialParaEvaluacion = $litros
            ->slice(0, -1)
            ->values()
            ->all();

        $prediccionEvaluacion = $this
            ->prediccionIA
            ->predecir($historialParaEvaluacion);

        if (!($prediccionEvaluacion['success'] ?? false)) {
            return [
                'ganado_id' => $animal->id,
                'arete' => $animal->arete_siniiga,
                'estado_ia' => 'error',
                'mensaje' => $prediccionEvaluacion['message']
                    ?? 'No fue posible realizar la predicción.',
            ];
        }

        $produccionEsperada = (float)
        $prediccionEvaluacion['prediccion'];

        $diferenciaLitros = round(
            $produccionReal - $produccionEsperada,
            2
        );

        $diferenciaPorcentual = $produccionEsperada > 0
            ? round(
                (($produccionReal - $produccionEsperada)
                    / $produccionEsperada) * 100,
                2
            )
            : 0;

        $estado = $this->clasificarEstado(
            $diferenciaPorcentual,
            $produccionReal,
            (float) $animal->produccion_minima_diaria
        );

        /*
         * Ahora sí utilizamos todos los registros para
         * predecir la producción del siguiente día.
         */
        $datosParaSiguientePrediccion = $litros->all();

        if ($estado === 'anomalia') {
            $datosParaSiguientePrediccion = $litros
                ->slice(0, -1)
                ->values()
                ->all();
        }

        $prediccionSiguienteDia = $this
            ->prediccionIA
            ->predecir($datosParaSiguientePrediccion);

        return [
            'ganado_id' => $animal->id,
            'arete' => $animal->arete_siniiga,

            'fecha_ultima_produccion' =>
            $fechaUltimaProduccion,

            'produccion_real' =>
            round($produccionReal, 2),

            'produccion_esperada' =>
            round($produccionEsperada, 2),

            'produccion_minima' =>
            (float) $animal->produccion_minima_diaria,

            'diferencia_litros' =>
            $diferenciaLitros,

            'diferencia_porcentual' =>
            $diferenciaPorcentual,

            'tendencia' =>
            $prediccionEvaluacion['tendencia'],

            'calidad_ajuste' =>
            $prediccionEvaluacion['calidad_ajuste'] ?? 'desconocida',

            'r2' =>
            $prediccionEvaluacion['r2'] ?? null,

            'estado_ia' =>
            $estado,

            'prediccion_siguiente_dia' =>
            $prediccionSiguienteDia['success']
                ? $prediccionSiguienteDia['prediccion']
                : null,

            'dias_utilizados' =>
            $litros->count(),

            'dias_incompletos' => $diasIncompletos,

            'mensaje' =>
            $this->generarMensaje(
                $animal->arete_siniiga,
                $estado,
                $diferenciaPorcentual,
                $produccionReal,
                (float) $animal->produccion_minima_diaria,
                $prediccionEvaluacion['tendencia'],
                $prediccionEvaluacion['calidad_ajuste'] ?? 'desconocida',
                $diasIncompletos
            ),
        ];
    }

    private function clasificarEstado(
        float $diferenciaPorcentual,
        float $produccionReal,
        float $produccionMinima
    ): string {
        if ($produccionReal < $produccionMinima) {
            return 'anomalia';
        }

        if ($diferenciaPorcentual <= -25) {
            return 'anomalia';
        }

        if ($diferenciaPorcentual <= -15) {
            return 'atencion';
        }

        return 'normal';
    }

    private function generarMensaje(
        string $arete,
        string $estado,
        float $diferenciaPorcentual,
        float $produccionReal,
        float $produccionMinima,
        string $tendencia,
        string $calidadAjuste,
        int $diasIncompletos
    ): string {
        $mensajeDiasIncompletos = '';

        if ($diasIncompletos === 1) {
            $mensajeDiasIncompletos =
                ' Se encontró 1 día con registros incompletos '
                . 'que no fue utilizado en el análisis.';
        } elseif ($diasIncompletos > 1) {
            $mensajeDiasIncompletos =
                " Se encontraron {$diasIncompletos} días con registros "
                . 'incompletos que no fueron utilizados en el análisis.';
        }

        return match ($estado) {
            'anomalia' =>
            "GanTek IA detectó que la producción del animal {$arete} "
                . "se encuentra "
                . abs($diferenciaPorcentual)
                . "% por debajo de lo esperado. "
                . "La producción actual es de "
                . number_format($produccionReal, 2)
                . " L y el mínimo configurado es de "
                . number_format($produccionMinima, 2)
                . " L. "
                . "La tendencia histórica es {$tendencia} "
                . "y la calidad del ajuste del modelo es {$calidadAjuste}."
                . $mensajeDiasIncompletos
                . " Se recomienda revisar los registros "
                . "y las condiciones de manejo.",

            'atencion' =>
            "La producción del animal {$arete} está "
                . abs($diferenciaPorcentual)
                . "% por debajo de la producción estimada. "
                . "La producción actual es de "
                . number_format($produccionReal, 2)
                . " L y el mínimo configurado es de "
                . number_format($produccionMinima, 2)
                . " L. "
                . "La tendencia histórica es {$tendencia} "
                . "y la calidad del ajuste del modelo es {$calidadAjuste}."
                . $mensajeDiasIncompletos
                . " Se recomienda mantener al animal en observación.",

            default =>
            "La producción del animal {$arete} se encuentra "
                . "dentro del comportamiento esperado. "
                . "La producción actual es de "
                . number_format($produccionReal, 2)
                . " L y el mínimo configurado es de "
                . number_format($produccionMinima, 2)
                . " L. "
                . "La tendencia histórica es {$tendencia} "
                . "y la calidad del ajuste del modelo es {$calidadAjuste}."
                . $mensajeDiasIncompletos,
        };
    }
}
