<?php

namespace Database\Seeders;

use App\Models\Finca;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class LoteSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Finca::whereHas('user', fn (Builder $query) => $query->whereIn('email', ['admin@gantek.test', 'ganadero@gantek.test']))->get() as $finca) {
            foreach (['Norte', 'Sur'] as $nombre) {
                $finca->lotes()->updateOrCreate(['nombre' => 'Lote '.$nombre], ['descripcion' => 'Lote de demostración', 'estado' => 'Activo']);
            }
        }
    }
}
