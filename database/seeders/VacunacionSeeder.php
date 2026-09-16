<?php

namespace Database\Seeders;

use App\Models\Ganado;
use App\Models\Vacuna;
use App\Models\Vacunacion;
use App\Models\Veterinario;
use Illuminate\Database\Seeder;

class VacunacionSeeder extends Seeder
{
    public function run(): void
    {
        $veterinarios = Veterinario::where('cedula_profesional', 'like', 'DEMO-CEDULA-%')->orderBy('id')->get();
        $vacunas = Vacuna::where('nombre', 'like', 'Vacuna demo %')->orderBy('id')->get();
        foreach (Ganado::where('arete_siniiga', 'like', 'DEMO-MX-%')->orderBy('id')->get() as $indice => $animal) {
            $vacuna = $vacunas[$indice % 4];
            foreach ([60, 15] as $dias) {
                $proxima = $dias === 60 ? today()->subDays(30) : match ($indice % 4) {
                    0 => today()->addDays(5), 1 => today()->subDays(2), 2 => today()->addDays(45), default => null,
                };
                Vacunacion::updateOrCreate([
                    'ganado_id' => $animal->id, 'vacuna_id' => $vacuna->id, 'fecha_aplicacion' => today()->subDays($dias)->toDateString(),
                ], [
                    'veterinario_id' => $veterinarios[($indice + $dias) % 3]->id,
                    'proxima_aplicacion' => $proxima, 'dosis' => '2 ml (demo)', 'observaciones' => 'Aplicación ficticia.',
                ]);
            }
        }
    }
}
