<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['email' => 'admin@gantek.test', 'name' => 'Administrador de catálogos (demo)', 'catalogos' => true],
            ['email' => 'ganadero@gantek.test', 'name' => 'Ganadero de prueba', 'catalogos' => false],
        ] as $datos) {
            $user = User::firstOrNew(['email' => $datos['email']]);
            $user->name = $datos['name'];
            $user->password = 'GanTekDemo2026!';
            $user->email_verified_at = now();
            $user->puede_gestionar_catalogos = $datos['catalogos'];
            $user->save();
        }
    }
}
