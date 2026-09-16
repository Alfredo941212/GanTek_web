<?php

namespace Database\Factories;

use App\Models\Veterinario;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Veterinario> */
class VeterinarioFactory extends Factory
{
    protected $model = Veterinario::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['nombre' => 'Dra. '.fake()->name().' (ficticio)', 'cedula_profesional' => 'DEMO-'.fake()->unique()->numerify('########'), 'correo' => fake()->unique()->safeEmail(), 'estado' => 'Activo'];
    }
}
