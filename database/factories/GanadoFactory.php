<?php

namespace Database\Factories;

use App\Models\Ganado;
use App\Models\Lote;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ganado> */
class GanadoFactory extends Factory
{
    protected $model = Ganado::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['lote_id' => Lote::factory(), 'arete_siniiga' => 'DEMO-'.fake()->unique()->numerify('##########'), 'nombre' => fake()->firstName(), 'sexo' => 'Hembra', 'raza' => 'Mestiza', 'fecha_nacimiento' => today()->subYears(3), 'fecha_ingreso' => today()->subYear(), 'peso_inicial' => '350.00', 'estado' => 'Activo'];
    }
}
