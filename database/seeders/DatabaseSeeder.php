<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gantek.com'],
            [
                'name' => 'Administrador GanTek',
                'password' => Hash::make('GanTek1234'),
            ]
        );
    }
}
