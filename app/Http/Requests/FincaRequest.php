<?php

namespace App\Http\Requests;

use App\Models\Finca;
use Illuminate\Foundation\Http\FormRequest;

class FincaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registro = $this->route('finca');

        return $registro
            ? $this->user()->can('update', $registro)
            : $this->user()->can('create', Finca::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['prohibited'],
            'nombre' => ['required', 'string', 'max:150'],
            'municipio' => ['required', 'string', 'max:100'],
            'localidad' => ['nullable', 'string', 'max:150'],
            'estado' => ['required', 'string', 'max:100'],
            'superficie' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
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
