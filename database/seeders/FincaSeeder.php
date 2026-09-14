<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FincaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (User::whereIn('email', ['admin@gantek.test', 'ganadero@gantek.test'])->orderBy('id')->get() as $user) {
            for ($numero = 1; $numero <= 2; $numero++) {
                $user->fincas()->updateOrCreate(['nombre' => 'Finca demo '.$user->id.'-'.$numero], [
                    'municipio' => 'Municipio ficticio', 'localidad' => 'Localidad demo '.$numero,
                    'estado' => 'Veracruz', 'superficie' => 20 + $numero * 5, 'observaciones' => 'Datos ficticios de demostración.',
                ]);
            }
        }
    }
}
