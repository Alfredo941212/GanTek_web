<?php

namespace Database\Factories;

use App\Models\Ganado;
use App\Models\Vacuna;
use App\Models\Vacunacion;
use App\Models\Veterinario;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vacunacion> */
class VacunacionFactory extends Factory
{
    protected $model = Vacunacion::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['ganado_id' => Ganado::factory(), 'veterinario_id' => Veterinario::factory(), 'vacuna_id' => Vacuna::factory(), 'fecha_aplicacion' => today()->subDays(20), 'proxima_aplicacion' => today()->addDays(10), 'dosis' => '2 ml (prueba)'];
    }
}
