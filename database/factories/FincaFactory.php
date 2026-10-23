<?php

namespace Database\Factories;

use App\Models\Finca;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/** @extends Factory<Finca> */
class FincaFactory extends Factory
{
    protected $model = Finca::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'nombre' => fake()->unique()->company(), 'municipio' => 'Municipio de prueba', 'localidad' => 'Localidad demo', 'estado' => 'Veracruz', 'superficie' => '25.50'];
    }

    public function conUbicacionCatalogada(): static
    {
        return $this->state(function (): array {
            DB::table('estados')->updateOrInsert(['clave_inegi' => '01'], ['nombre' => 'Estado de prueba']);
            $estadoId = DB::table('estados')->where('clave_inegi', '01')->value('id');
            DB::table('municipios')->updateOrInsert(['clave_inegi' => '01001'], ['nombre' => 'Municipio de prueba', 'estado_id' => $estadoId]);
            $municipioId = DB::table('municipios')->where('clave_inegi', '01001')->value('id');
            DB::table('localidades')->updateOrInsert(['clave_inegi' => '010010001'], ['nombre' => 'Localidad de prueba', 'municipio_id' => $municipioId]);

            return [
                'estado' => 'Estado de prueba', 'municipio' => 'Municipio de prueba', 'localidad' => 'Localidad de prueba',
                'estado_id' => $estadoId, 'municipio_id' => $municipioId,
                'localidad_id' => DB::table('localidades')->where('clave_inegi', '010010001')->value('id'),
            ];
        });
    }
}
