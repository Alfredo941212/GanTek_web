<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class SincronizarUbicaciones extends Command
{
    protected $signature = 'ubicaciones:sincronizar {--estado=* : Claves de dos dígitos; omitir para importar todo México}';

    protected $description = 'Importa catálogos oficiales del INEGI sin eliminar registros locales';

    public function handle(): int
    {
        $claves = $this->option('estado');
        foreach ($claves as $clave) {
            if (! preg_match('/^(0[1-9]|[12][0-9]|3[0-2])$/', $clave)) {
                $this->error('Cada estado debe ser una clave entre 01 y 32.');

                return self::FAILURE;
            }
        }
        $lock = Cache::lock('ubicaciones:sincronizar', 86400);
        if (! $lock->get()) {
            $this->error('Ya existe una sincronización en curso.');

            return self::FAILURE;
        }
        try {
            $estados = $this->descargar('mgee/', 2);
            if (count($estados) !== 32) {
                throw new RuntimeException('INEGI no devolvió los 32 estados; se cancela la importación.');
            }
            foreach ($estados as $estado) {
                if ($claves !== [] && ! in_array($estado['cvegeo'], $claves, true)) {
                    continue;
                }
                $municipios = $this->descargar('mgem/'.$estado['cvegeo'], 5, $estado['cvegeo']);
                $localidades = [];
                foreach ($municipios as $municipio) {
                    $localidades[$municipio['cvegeo']] = $this->descargar('localidades/'.$municipio['cvegeo'], 9, $municipio['cvegeo']);
                }
                DB::transaction(function () use ($estado, $municipios, $localidades): void {
                    $estadoId = $this->guardar('estados', $estado);
                    foreach ($municipios as $municipio) {
                        $municipioId = $this->guardar('municipios', $municipio, ['estado_id' => $estadoId]);
                        foreach (array_chunk($localidades[$municipio['cvegeo']], 500) as $bloque) {
                            DB::table('localidades')->upsert(array_map(fn (array $localidad): array => [
                                'clave_inegi' => $localidad['cvegeo'],
                                'nombre' => $localidad['nomgeo'],
                                'municipio_id' => $municipioId,
                            ], $bloque), ['clave_inegi'], ['nombre', 'municipio_id']);
                        }
                    }
                });
                $this->info($estado['cvegeo'].' '.$estado['nomgeo'].': importación completa.');
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('No se pudo completar la sincronización: '.$exception->getMessage());
            $this->warn('No se eliminaron catálogos. Los estados completados se conservan; puede reintentar.');

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }

    /** @return list<array{cvegeo: string, nomgeo: string}> */
    private function descargar(string $ruta, int $longitud, string $prefijo = ''): array
    {
        $respuesta = Http::acceptJson()->connectTimeout(10)->timeout(90)->retry(3, 500)
            ->get('https://gaia.inegi.org.mx/wscatgeo/v2/'.$ruta)->throw()->json();
        $datos = $respuesta['datos'] ?? null;
        if (! is_array($datos) || ! array_is_list($datos) || $datos === [] || (isset($respuesta['numReg']) && (int) $respuesta['numReg'] !== count($datos))) {
            throw new RuntimeException('Respuesta vacía, incompleta o inválida del INEGI: '.$ruta);
        }
        $claves = [];
        foreach ($datos as $fila) {
            $clave = $fila['cvegeo'] ?? null;
            if (! is_string($clave) || ! preg_match('/^[0-9]{'.$longitud.'}$/', $clave)
                || ! str_starts_with($clave, $prefijo) || isset($claves[$clave])
                || ! is_string($fila['nomgeo'] ?? null) || trim($fila['nomgeo']) === '' || mb_strlen($fila['nomgeo']) > 150
                || ($longitud === 2 && ! preg_match('/^(0[1-9]|[12][0-9]|3[0-2])$/', $clave))
                || ($longitud >= 5 && ($fila['cve_ent'] ?? null) !== substr($clave, 0, 2))
                || ($longitud >= 5 && ($fila['cve_mun'] ?? null) !== substr($clave, 2, 3))
                || ($longitud === 9 && ($fila['cve_loc'] ?? null) !== substr($clave, 5, 4))) {
                throw new RuntimeException('Registro geográfico inválido del INEGI: '.$ruta);
            }
            $claves[$clave] = true;
        }

        return $datos;
    }

    /** @param array{cvegeo: string, nomgeo: string} $fila
     * @param  array<string, int>  $padre
     */
    private function guardar(string $tabla, array $fila, array $padre = []): int
    {
        DB::table($tabla)->updateOrInsert(['clave_inegi' => $fila['cvegeo']], ['nombre' => $fila['nomgeo'], ...$padre]);

        return (int) DB::table($tabla)->where('clave_inegi', $fila['cvegeo'])->value('id');
    }
}
