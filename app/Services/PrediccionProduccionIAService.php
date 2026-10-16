<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class PrediccionProduccionIAService
{
    public function predecir(array $registros): array
    {
        if (count($registros) < 7) {
            return [
                'success' => false,
                'message' => 'Se requieren al menos 7 registros para realizar una predicción.',
            ];
        }

        $entrada = json_encode([
            'registros' => array_values($registros),
        ]);

        $script = base_path('ia/predecir_produccion.py');

        try {
            $resultado = Process::input($entrada)
                ->timeout(30)
                ->run([
                    //'C:\Users\NERD\AppData\Local\Programs\Python\Python312\python.exe',
                    'python',
                    $script,
                ]);

            if ($resultado->failed()) {
                return [
                    'success' => false,
                    'message' => 'No fue posible ejecutar el modelo de predicción.',
                ];
            }

            $datos = json_decode(
                $resultado->output(),
                true
            );

            if (!is_array($datos)) {
                return [
                    'success' => false,
                    'message' => 'El modelo devolvió una respuesta inválida.',
                ];
            }

            return $datos;
        } catch (\Throwable $error) {
            return [
                'success' => false,
                'message' => 'Error al ejecutar el modelo de IA.',
                'error' => $error->getMessage(),
            ];
        }
    }
}
