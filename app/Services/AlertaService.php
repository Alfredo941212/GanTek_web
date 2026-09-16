<?php

namespace App\Services;

use App\Models\RegistroOrdenio;
use App\Models\User;
use App\Models\Vacunacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AlertaService
{
    /** @return Collection<int, object> */
    public function produccion(User $user): Collection
    {
        $inicioReciente = today()->subDays(3)->toDateString();
        $diarios = RegistroOrdenio::forUser($user)
            ->whereHas('ganado', fn (Builder $query) => $query->where('estado', 'Activo')->where('sexo', 'Hembra'))
            ->whereBetween('fecha', [today()->subDays(10)->toDateString(), today()->subDay()->toDateString()])
            ->select('ganado_id', 'fecha')->selectRaw('SUM(litros) as total')
            ->groupBy('ganado_id', 'fecha')->havingRaw('COUNT(*) = 2');

        $comparacion = DB::query()->fromSub($diarios->toBase(), 'diarios')
            ->select('ganado_id')
            ->selectRaw('SUM(CASE WHEN fecha >= ? THEN total ELSE 0 END) / 3.0 as reciente', [$inicioReciente])
            ->selectRaw('SUM(CASE WHEN fecha < ? THEN total ELSE 0 END) / 7.0 as referencia', [$inicioReciente])
            ->groupBy('ganado_id')
            ->havingRaw('SUM(CASE WHEN fecha >= ? THEN 1 ELSE 0 END) = 3', [$inicioReciente])
            ->havingRaw('SUM(CASE WHEN fecha < ? THEN 1 ELSE 0 END) = 7', [$inicioReciente]);

        return DB::query()->fromSub($comparacion, 'comparacion')
            ->join('ganado', 'ganado.id', '=', 'comparacion.ganado_id')
            ->select('ganado.id', 'ganado.arete_siniiga', 'ganado.nombre', 'comparacion.reciente', 'comparacion.referencia')
            ->where('referencia', '>', 0)->whereRaw('reciente <= referencia * 0.8')
            ->orderBy('ganado.arete_siniiga')->get();
    }

    /** @return Builder<Vacunacion> */
    public function ultimasVacunaciones(User $user): Builder
    {
        return Vacunacion::forUser($user)
            ->whereHas('ganado', fn (Builder $query) => $query->where('estado', 'Activo'))
            ->whereNotExists(function (QueryBuilder $query): void {
                $query->selectRaw('1')->from('vacunaciones as posterior')
                    ->whereColumn('posterior.ganado_id', 'vacunaciones.ganado_id')
                    ->whereColumn('posterior.vacuna_id', 'vacunaciones.vacuna_id')
                    ->where(function (QueryBuilder $fecha): void {
                        $fecha->whereColumn('posterior.fecha_aplicacion', '>', 'vacunaciones.fecha_aplicacion')
                            ->orWhere(function (QueryBuilder $empate): void {
                                $empate->whereColumn('posterior.fecha_aplicacion', 'vacunaciones.fecha_aplicacion')
                                    ->whereColumn('posterior.id', '>', 'vacunaciones.id');
                            });
                    });
            });
    }

    /** @return Builder<Vacunacion> */
    public function proximas(User $user): Builder
    {
        return $this->ultimasVacunaciones($user)->whereBetween('proxima_aplicacion', [
            today()->toDateString(), today()->addDays(30)->toDateString(),
        ]);
    }

    /** @return Builder<Vacunacion> */
    public function vencidas(User $user): Builder
    {
        return $this->ultimasVacunaciones($user)->where('proxima_aplicacion', '<', today()->toDateString());
    }
}
