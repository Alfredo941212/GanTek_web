<?php

namespace Database\Seeders;

use App\Models\Ganado;
use App\Models\Lote;
use App\Models\RegistroOrdenio;
use Illuminate\Database\Seeder;

class RegistroOrdenioSeeder extends Seeder
{
    public function run(): void
    {
        $hembras = Ganado::where('arete_siniiga', 'like', 'DEMO-MX-%')
            ->where('sexo', 'Hembra')
            ->orderBy('id')
            ->get();

        foreach ($hembras as $indice => $animal) {
            $loteAnterior = Lote::where('finca_id', $animal->lote->finca_id)
                ->where('id', '!=', $animal->lote_id)
                ->firstOrFail();

            for ($dias = 14; $dias >= 1; $dias--) {
                foreach ([
                    1 => 'Mañana',
                    2 => 'Tarde',
                ] as $numeroOrdenio => $turno) {
                    $litros = $indice === 0 && $dias <= 3 ? 1.5 : 5.0;

                    if ($indice % 3 === 1) {
                        $litros += (($dias % 3) - 1) * 0.4;
                    }

                    RegistroOrdenio::updateOrCreate(
                        [
                            'ganado_id' => $animal->id,
                            'fecha' => today()->subDays($dias)->toDateString(),
                            'numero_ordenio' => $numeroOrdenio,
                        ],
                        [
                            'turno' => $turno,
                            'lote_historico_id' => $indice === 0 && $dias > 7
                                ? $loteAnterior->id
                                : $animal->lote_id,
                            'litros' => $litros,
                            'observaciones' => 'Producción ficticia.',
                        ]
                    );
                }
            }

            RegistroOrdenio::updateOrCreate(
                [
                    'ganado_id' => $animal->id,
                    'fecha' => today()->toDateString(),
                    'numero_ordenio' => 1,
                ],
                [
                    'turno' => 'Mañana',
                    'lote_historico_id' => $animal->lote_id,
                    'litros' => $indice === 0 ? 3.5 : 5.0,
                    'observaciones' => 'Hoy, todavía incompleto.',
                ]
            );
        }
    }
}