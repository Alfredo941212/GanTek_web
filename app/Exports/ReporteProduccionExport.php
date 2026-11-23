<?php

namespace App\Exports;

use App\Models\User;
use App\Services\ReporteProduccionService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReporteProduccionExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private User $user,
        private string $desde,
        private string $hasta
    ) {}

    public function array(): array
    {
        $service = app(
            ReporteProduccionService::class
        );

        $resumen = $service->resumen(
            $this->user,
            $this->desde,
            $this->hasta
        );

        $filas = [];

        foreach ($resumen['diaria'] as $registro) {
            $filas[] = [
                'tipo' => 'Producción diaria',
                'fecha_o_grupo' => $registro['fecha'],
                'litros' => $registro['litros'],
                'sesiones' => $registro['sesiones'],
            ];
        }

        foreach ($resumen['grupos'] as $registro) {
            $filas[] = [
                'tipo' => 'Producción por grupo',
                'fecha_o_grupo' => $registro['grupo'],
                'litros' => $registro['litros'],
                'sesiones' => $registro['sesiones'],
            ];
        }

        return $filas;
    }

    public function headings(): array
    {
        return [
            'Tipo',
            'Fecha / Grupo',
            'Litros',
            'Sesiones',
        ];
    }

    public function title(): string
    {
        return 'Producción';
    }
}