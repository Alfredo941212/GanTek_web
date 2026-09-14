<?php

namespace App\Http\Controllers;

use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Lote;
use App\Services\ProduccionService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, ProduccionService $produccion): View
    {
        $filtros = $request->validate([
            'periodo' => ['nullable', Rule::in(['diario', 'semanal', 'mensual', 'personalizado'])],
            'desde' => ['required_if:periodo,personalizado', 'nullable', 'date_format:Y-m-d'],
            'hasta' => ['required_if:periodo,personalizado', 'nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'finca_id' => ['nullable', 'integer', Rule::exists('fincas', 'id')->where('user_id', $request->user()->id)],
            'lote_id' => ['nullable', 'integer', Rule::exists('lotes', 'id')->where(fn (Builder $query) => $query->whereIn('finca_id', Finca::forUser($request->user())->select('id')))],
            'ganado_id' => ['nullable', 'integer', Rule::exists('ganado', 'id')->where(fn (Builder $query) => $query->whereIn('lote_id', Lote::forUser($request->user())->select('id')))],
        ]);
        $periodo = $filtros['periodo'] ?? 'semanal';
        if ($periodo !== 'personalizado') {
            $filtros['desde'] = match ($periodo) {
                'diario' => today()->toDateString(),
                'mensual' => today()->startOfMonth()->toDateString(),
                default => today()->startOfWeek()->toDateString(),
            };
            $filtros['hasta'] = today()->toDateString();
        }

        return view('reports.index', [
            'resumen' => $produccion->resumen($request->user(), $filtros),
            'filtros' => $filtros,
            'periodo' => $periodo,
            'fincas' => Finca::forUser($request->user())->orderBy('nombre')->get(),
            'lotes' => Lote::forUser($request->user())->with('finca')->orderBy('nombre')->get(),
            'animales' => Ganado::forUser($request->user())->orderBy('arete_siniiga')->get(),
        ]);
    }
}
