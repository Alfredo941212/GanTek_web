<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class ProduccionGrupoIAService
{
    public function analizar(
        array $registros,
        int $horizonte = 7
    ): array {
        $scriptPath = base_path(
            'ia/analizar_produccion_grupos.py'
        );

        if (!file_exists($scriptPath)) {
            throw new RuntimeException(
                'No se encontró el script de IA.'
            );
        }

        $payload = [
            'registros' => $registros,
            'horizonte' => $horizonte,
        ];

        $jsonInput = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );

        if ($jsonInput === false) {
            throw new RuntimeException(
                'No fue posible preparar los datos '
                    . 'para el modelo de IA.'
            );
        }

        $python = env(
            'PYTHON_EXECUTABLE',
            'python'
        );

        $process = new Process(
            [
                $python,
                $scriptPath,
            ],
            base_path(),
            [
                'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
                'SystemDrive' => getenv('SystemDrive') ?: 'C:',
                'TEMP' => getenv('TEMP') ?: sys_get_temp_dir(),
                'TMP' => getenv('TMP') ?: sys_get_temp_dir(),
                'PATH' => getenv('PATH') ?: '',
                'PYTHONIOENCODING' => 'utf-8',
            ]
        );

        $process->setInput(
            $jsonInput
        );

        $process->setTimeout(
            60
        );

        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(
                'Error al ejecutar el modelo de IA: '
                    . trim(
                        $process->getErrorOutput()
                    )
            );
        }

        $output = trim(
            $process->getOutput()
        );

        if ($output === '') {
            throw new RuntimeException(
                'El modelo de IA no devolvió resultados.'
            );
        }

        $decoded = json_decode(
            $output,
            true
        );

        if (!is_array($decoded)) {
            throw new RuntimeException(
                'La respuesta del modelo de IA '
                    . 'no tiene un formato válido.'
            );
        }

        if (
            isset($decoded['success'])
            && $decoded['success'] === false
        ) {
            throw new RuntimeException(
                $decoded['message']
                    ?? 'El modelo de IA reportó un error.'
            );
        }

        return $decoded;
    }
}
