<?php

namespace App\Http\Requests;

use App\Models\Finca;
use App\Models\Ganado;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GanadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registro = $this->route('ganado');

        return $registro
            ? $this->user()->can('update', $registro)
            : $this->user()->can('create', Ganado::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $ganado = $this->route('ganado');

        return [
            'lote_id' => ['required', 'integer', Rule::exists('lotes', 'id')->where(fn (Builder $query) => $query
                ->whereIn('finca_id', Finca::forUser($this->user())->select('id'))
                ->where(function (Builder $lotes) use ($ganado): void {
                    $lotes->where('estado', 'Activo');
                    if ($ganado) {
                        $lotes->orWhere('id', $ganado->lote_id);
                    }
                }))],
            'arete_siniiga' => ['required', 'string', 'max:50', Rule::unique('ganado', 'arete_siniiga')->ignore($ganado)],
            'nombre' => ['nullable', 'string', 'max:100'],
            'sexo' => ['required', Rule::in(['Macho', 'Hembra'])],
            'raza' => ['nullable', 'string', 'max:100'],
            'fecha_nacimiento' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:fecha_ingreso'],
            'fecha_ingreso' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'peso_inicial' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'estado' => ['required', Rule::in(['Activo', 'Vendido', 'Fallecido', 'Baja'])],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'La selección de :attribute no está disponible.',
            'unique' => 'Ya existe un registro con esta combinación de :attribute.',
            'prohibited' => 'No se permite modificar :attribute.',
            'litros.gt' => 'Los litros deben ser mayores que cero.',
            'decimal' => 'El campo :attribute admite como máximo dos decimales.',
        ];
    }
}
