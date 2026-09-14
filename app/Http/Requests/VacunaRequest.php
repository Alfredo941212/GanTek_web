<?php

namespace App\Http\Requests;

use App\Models\Vacuna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VacunaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registro = $this->route('vacuna');

        return $registro
            ? $this->user()->can('update', $registro)
            : $this->user()->can('create', Vacuna::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'fabricante' => ['nullable', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'dosis_recomendada' => ['nullable', 'string', 'max:100'],
            'intervalo_dias' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'estado' => ['required', Rule::in(['Activo', 'Inactivo'])],
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
