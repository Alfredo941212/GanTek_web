<?php

namespace Database\Factories;

use App\Models\Vacuna;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vacuna> */
class VacunaFactory extends Factory
{
    protected $model = Vacuna::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['nombre' => 'Vacuna demo '.fake()->unique()->word(), 'fabricante' => 'Laboratorio ficticio', 'dosis_recomendada' => '2 ml (prueba)', 'intervalo_dias' => 30, 'estado' => 'Activo'];
    }
}
