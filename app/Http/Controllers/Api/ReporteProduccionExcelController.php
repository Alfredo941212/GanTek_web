<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReporteProduccionExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteProduccionExcelController extends Controller
{
    public function __invoke(
        Request $request
    ): BinaryFileResponse {
        $user = $request->user();

        $filtros = $request->validate([
            'periodo' => [
                'nullable',
                Rule::in([
                    'diario',
                    'semanal',
                    'mensual',
                    'personalizado',
                ]),
            ],

            'desde' => [
                'required_if:periodo,personalizado',
                'nullable',
                'date_format:Y-m-d',
            ],

            'hasta' => [
                'required_if:periodo,personalizado',
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:desde',
            ],
        ]);

        $periodo = $filtros['periodo'] ?? 'semanal';

        if ($periodo !== 'personalizado') {
            $filtros['desde'] = match ($periodo) {
                'diario' => today()->toDateString(),

                'mensual' => today()
                    ->startOfMonth()
                    ->toDateString(),

                default => today()
                    ->startOfWeek()
                    ->toDateString(),
            };

            $filtros['hasta'] = today()->toDateString();
        }

        return Excel::download(
            new ReporteProduccionExport(
                $user,
                $filtros['desde'],
                $filtros['hasta']
            ),
            'reporte-produccion-'
            . now()->format('Y-m-d')
            . '.xlsx'
        );
    }
}