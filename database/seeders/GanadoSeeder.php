<?php

namespace Database\Seeders;

use App\Models\Ganado;
use App\Models\Lote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class GanadoSeeder extends Seeder
{
    public function run(): void
    {
        $numero = 0;
        foreach (Lote::whereHas('finca.user', fn (Builder $query) => $query->whereIn('email', ['admin@gantek.test', 'ganadero@gantek.test']))->orderBy('id')->get() as $lote) {
            for ($posicion = 1; $posicion <= 3; $posicion++) {
                $numero++;
                $estado = match ($numero) {
                    15, 18 => 'Vendido', 21 => 'Fallecido', 24 => 'Baja', default => 'Activo'
                };
                Ganado::updateOrCreate(['arete_siniiga' => sprintf('DEMO-MX-%06d', $numero)], [
                    'lote_id' => $lote->id, 'nombre' => 'Animal demo '.$numero, 'sexo' => $posicion <= 2 ? 'Hembra' : 'Macho',
                    'raza' => 'Mestiza', 'fecha_nacimiento' => today()->subYears(3), 'fecha_ingreso' => today()->subDays(180),
                    'peso_inicial' => 300 + $numero, 'estado' => $estado, 'observaciones' => 'Arete ficticio, sin validez oficial.',
                ]);
            }
        }
    }
}
