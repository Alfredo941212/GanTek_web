<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class, FincaSeeder::class, LoteSeeder::class, GanadoSeeder::class,
            VeterinarioSeeder::class, VacunaSeeder::class, VacunacionSeeder::class, RegistroOrdenioSeeder::class,
        ]);
    }
}
