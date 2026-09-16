<?php

namespace App\Http\Requests;

use App\Models\Lote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registro = $this->route('lote');

        return $registro
            ? $this->user()->can('update', $registro)
            : $this->user()->can('create', Lote::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $lote = $this->route('lote');

        return [
            'finca_id' => $lote ? ['missing'] : ['required', 'integer', Rule::exists('fincas', 'id')->where('user_id', $this->user()->id)],
            'nombre' => ['required', 'string', 'max:150', Rule::unique('lotes', 'nombre')->where('finca_id', $lote?->finca_id ?? $this->input('finca_id'))->ignore($lote)],
            'descripcion' => ['nullable', 'string', 'max:2000'],
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
            'missing' => 'No se permite enviar ni modificar :attribute.',
            'litros.gt' => 'Los litros deben ser mayores que cero.',
            'decimal' => 'El campo :attribute admite como máximo dos decimales.',
        ];
    }
}
