<?php

namespace Database\Factories;

use App\Models\Finca;
use App\Models\Lote;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lote> */
class LoteFactory extends Factory
{
    protected $model = Lote::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['finca_id' => Finca::factory(), 'nombre' => fake()->unique()->word(), 'estado' => 'Activo'];
    }
}
