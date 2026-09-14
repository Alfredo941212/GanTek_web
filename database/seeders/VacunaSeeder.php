<?php

namespace Database\Seeders;

use App\Models\Vacuna;
use Illuminate\Database\Seeder;

class VacunaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Vacuna demo A', 'Vacuna demo B', 'Vacuna demo C', 'Vacuna demo D'] as $indice => $nombre) {
            Vacuna::updateOrCreate(['nombre' => $nombre], [
                'fabricante' => 'Laboratorio ficticio', 'descripcion' => 'Catálogo de demostración; no es una indicación veterinaria.',
                'dosis_recomendada' => '2 ml (demo)', 'intervalo_dias' => 30 + $indice * 30, 'estado' => 'Activo',
            ]);
        }
    }
}
