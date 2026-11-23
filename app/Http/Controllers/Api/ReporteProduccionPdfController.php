<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProduccionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ReporteProduccionService;

class ReporteProduccionPdfController extends Controller
{
    public function __invoke(
        Request $request,
        ReporteProduccionService $produccion
    ): Response {
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

        $resumen = $produccion->resumen(
            $user,
            $filtros['desde'],
            $filtros['hasta']
        );

        $pdf = Pdf::loadView(
            'reports.produccion_pdf',
            [
                'usuario' => $user,
                'periodo' => $periodo,
                'desde' => $filtros['desde'],
                'hasta' => $filtros['hasta'],
                'resumen' => $resumen,
            ]
        );

        $pdf->setPaper(
            'a4',
            'portrait'
        );

        return $pdf->download(
            'reporte-produccion-'
            . now()->format('Y-m-d')
            . '.pdf'
        );
    }
}