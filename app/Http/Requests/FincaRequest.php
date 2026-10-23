<?php

namespace App\Http\Requests;

use App\Models\Finca;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'estado_clave' => ['bail', 'required', 'string', 'regex:/^[0-9]{2}$/', Rule::exists('estados', 'clave_inegi')],
            'municipio_clave' => ['bail', 'required', 'string', 'regex:/^[0-9]{5}$/', Rule::exists('municipios', 'clave_inegi')->where('estado_id', DB::table('estados')->where('clave_inegi', is_string($this->estado_clave) ? $this->estado_clave : '')->value('id') ?? 0)],
            'localidad_clave' => ['bail', 'nullable', 'string', 'regex:/^[0-9]{9}$/', Rule::exists('localidades', 'clave_inegi')->where('municipio_id', DB::table('municipios')->where('clave_inegi', is_string($this->municipio_clave) ? $this->municipio_clave : '')->value('id') ?? 0)],
            'superficie' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'municipio_clave.exists' => 'El municipio seleccionado no pertenece al estado indicado.',
            'localidad_clave.exists' => 'La localidad seleccionada no pertenece al municipio indicado.',
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'La selección de :attribute no está disponible.',
            'unique' => 'Ya existe un registro con esta combinación de :attribute.',
            'prohibited' => 'No se permite modificar :attribute.',
            'litros.gt' => 'Los litros deben ser mayores que cero.',
            'decimal' => 'El campo :attribute admite como máximo dos decimales.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosFinca(): array
    {
        $datos = $this->safe()->except(['estado_clave', 'municipio_clave', 'localidad_clave']);
        foreach (['estado' => 'estados', 'municipio' => 'municipios', 'localidad' => 'localidades'] as $campo => $tabla) {
            $clave = $this->validated($campo.'_clave');
            $ubicacion = $clave ? DB::table($tabla)->where('clave_inegi', $clave)->first() : null;
            $datos[$campo.'_id'] = $ubicacion?->id;
            $datos[$campo] = $ubicacion?->nombre;
        }

        return $datos;
    }
}
