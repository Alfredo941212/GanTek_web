<?php

namespace Database\Factories;

use App\Models\Ganado;
use App\Models\RegistroOrdenio;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RegistroOrdenio> */
class RegistroOrdenioFactory extends Factory
{
    protected $model = RegistroOrdenio::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'ganado_id' => Ganado::factory(),

            'lote_historico_id' => fn(array $attributes): int =>
            Ganado::findOrFail($attributes['ganado_id'])->lote_id,

            'fecha' => today(),

            'turno' => 'Mañana',

            'numero_ordenio' => function (array $attributes): int {
                return ($attributes['turno'] ?? 'Mañana') === 'Tarde' ? 2 : 1;
            },

            'litros' => '5.00',
        ];
    }
}
