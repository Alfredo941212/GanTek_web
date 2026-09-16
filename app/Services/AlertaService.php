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
    /** @return Collection<int, object> */
    public function produccion(User $user): Collection
    {
        $fecha = today()->subDay()->toDateString();

        return DB::table('ganado')
            ->join('lotes', 'lotes.id', '=', 'ganado.lote_id')
            ->join('fincas', 'fincas.id', '=', 'lotes.finca_id')
            ->join('registros_ordenio', function ($join) use ($fecha): void {
                $join->on('registros_ordenio.ganado_id', '=', 'ganado.id')
                    ->where('registros_ordenio.fecha', '=', $fecha);
            })
            ->where('fincas.user_id', $user->id)
            ->where('ganado.estado', 'Activo')
            ->where('ganado.sexo', 'Hembra')
            ->where('ganado.estado_productivo', 'En producción')
            ->groupBy(
                'ganado.id',
                'ganado.arete_siniiga',
                'ganado.nombre',
                'ganado.produccion_minima_diaria'
            )
            ->select(
                'ganado.id',
                'ganado.arete_siniiga',
                'ganado.nombre',
                'ganado.produccion_minima_diaria'
            )
            ->selectRaw('SUM(registros_ordenio.litros) as produccion')
            ->havingRaw(
                'SUM(registros_ordenio.litros) < ganado.produccion_minima_diaria'
            )
            ->orderBy('ganado.arete_siniiga')
            ->get();
    }

    /** @return Collection<int, object> */
    public function produccionLotes(User $user): Collection
    {
        $fecha = today()->subDay()->toDateString();

        return DB::table('lotes')
            ->join('fincas', 'fincas.id', '=', 'lotes.finca_id')
            ->join('ganado', function ($join): void {
                $join->on('ganado.lote_id', '=', 'lotes.id')
                    ->where('ganado.estado', '=', 'Activo')
                    ->where('ganado.sexo', '=', 'Hembra')
                    ->where('ganado.estado_productivo', '=', 'En producción');
            })
            ->leftJoin('registros_ordenio', function ($join) use ($fecha): void {
                $join->on('registros_ordenio.ganado_id', '=', 'ganado.id')
                    ->where('registros_ordenio.fecha', '=', $fecha);
            })
            ->where('fincas.user_id', $user->id)
            ->where('lotes.estado', 'Activo')
            ->groupBy(
                'lotes.id',
                'lotes.nombre',
                'lotes.produccion_minima_por_vaca'
            )
            ->select(
                'lotes.id',
                'lotes.nombre',
                'lotes.produccion_minima_por_vaca'
            )
            ->selectRaw(
                'COUNT(DISTINCT ganado.id) as vacas_produccion'
            )
            ->selectRaw(
                'COALESCE(SUM(registros_ordenio.litros), 0) as produccion'
            )
            ->selectRaw(
                'COALESCE(SUM(registros_ordenio.litros), 0) / COUNT(DISTINCT ganado.id) as promedio_por_vaca'
            )
            ->selectRaw(
                'COUNT(DISTINCT ganado.id) * lotes.produccion_minima_por_vaca as produccion_minima_esperada'
            )
            ->havingRaw(
                'COALESCE(SUM(registros_ordenio.litros), 0) < COUNT(DISTINCT ganado.id) * lotes.produccion_minima_por_vaca'
            )
            ->orderBy('lotes.nombre')
            ->get();
    }

    /** @return Builder<Vacunacion> */
    public function ultimasVacunaciones(User $user): Builder
    {
        return Vacunacion::forUser($user)
            ->whereHas('ganado', fn(Builder $query) => $query->where('estado', 'Activo'))
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
            today()->toDateString(),
            today()->addDays(30)->toDateString(),
        ]);
    }

    /** @return Builder<Vacunacion> */
    public function vencidas(User $user): Builder
    {
        return $this->ultimasVacunaciones($user)->where('proxima_aplicacion', '<', today()->toDateString());
    }
}
