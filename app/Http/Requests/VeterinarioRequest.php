<?php

namespace App\Http\Requests;

use App\Models\Veterinario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VeterinarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registro = $this->route('veterinario');

        return $registro
            ? $this->user()->can('update', $registro)
            : $this->user()->can('create', Veterinario::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'cedula_profesional' => ['required', 'string', 'max:50', Rule::unique('veterinarios', 'cedula_profesional')->ignore($this->route('veterinario'))],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:254'],
            'especialidad' => ['nullable', 'string', 'max:150'],
            'estado' => ['required', Rule::in(['Activo', 'Inactivo'])],
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
