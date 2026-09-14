<?php

namespace Database\Factories;

use App\Models\Finca;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Finca> */
class FincaFactory extends Factory
{
    protected $model = Finca::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'nombre' => fake()->unique()->company(), 'municipio' => 'Municipio de prueba', 'localidad' => 'Localidad demo', 'estado' => 'Veracruz', 'superficie' => '25.50'];
    }
}
