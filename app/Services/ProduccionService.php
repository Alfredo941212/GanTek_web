<?php

namespace App\Services;

use App\Models\RegistroOrdenio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProduccionService
{
    /** @param array{desde?: string, hasta?: string, finca_id?: int|string|null, lote_id?: int|string|null, ganado_id?: int|string|null} $filtros
     * @return Builder<RegistroOrdenio>
     */
    public function registros(User $user, array $filtros = []): Builder
    {
        return RegistroOrdenio::forUser($user)
            ->when($filtros['desde'] ?? null, fn (Builder $query, string $fecha) => $query->where('fecha', '>=', $fecha))
            ->when($filtros['hasta'] ?? null, fn (Builder $query, string $fecha) => $query->where('fecha', '<=', $fecha))
            ->when($filtros['ganado_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('ganado_id', $id))
            ->when($filtros['lote_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('lote_historico_id', $id))
            ->when($filtros['finca_id'] ?? null, fn (Builder $query, int|string $id) => $query->whereHas('loteHistorico', fn (Builder $lotes) => $lotes->where('finca_id', $id)));
    }

    /** @param array{desde?: string, hasta?: string, finca_id?: int|string|null, lote_id?: int|string|null, ganado_id?: int|string|null} $filtros
     * @return array{total: float, promedio: float, dias: int, diaria: mixed, animales: mixed, lotes: mixed, fincas: mixed}
     */
    public function resumen(User $user, array $filtros): array
    {
        $base = $this->registros($user, $filtros);
        $diaria = (clone $base)->select('fecha')->selectRaw('SUM(litros) as total')->groupBy('fecha');
        $indicadores = DB::query()->fromSub($diaria->toBase(), 'produccion_diaria')
            ->selectRaw('COALESCE(SUM(total), 0) as total, COALESCE(AVG(total), 0) as promedio, COUNT(*) as dias')->first();

        return [
            'total' => (float) $indicadores->total,
            'promedio' => (float) $indicadores->promedio,
            'dias' => (int) $indicadores->dias,
            'diaria' => $diaria->orderBy('fecha')->get(),
            'animales' => (clone $base)->select('ganado_id')->selectRaw('SUM(litros) as total')->groupBy('ganado_id')->with('ganado')->get(),
            'lotes' => (clone $base)->select('lote_historico_id')->selectRaw('SUM(litros) as total')->groupBy('lote_historico_id')->with('loteHistorico.finca')->get(),
            'fincas' => (clone $base)->join('lotes as historico', 'historico.id', '=', 'registros_ordenio.lote_historico_id')
                ->join('fincas as finca_historica', 'finca_historica.id', '=', 'historico.finca_id')
                ->select('finca_historica.id', 'finca_historica.nombre')->selectRaw('SUM(litros) as total')
                ->groupBy('finca_historica.id', 'finca_historica.nombre')->get(),
        ];
    }
}
