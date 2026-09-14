<?php

namespace Database\Seeders;

use App\Models\Veterinario;
use Illuminate\Database\Seeder;

class VeterinarioSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Ana Prueba', 'Luis Ejemplo', 'Elena Demo'] as $indice => $nombre) {
            Veterinario::updateOrCreate(['cedula_profesional' => 'DEMO-CEDULA-'.($indice + 1)], [
                'nombre' => $nombre.' (ficticio)', 'correo' => 'veterinario'.($indice + 1).'@example.test',
                'telefono' => null, 'especialidad' => 'Bovinos', 'estado' => 'Activo', 'observaciones' => 'Identidad ficticia para pruebas.',
            ]);
        }
    }
}
