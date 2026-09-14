<?php

namespace App\Http\Requests;

use App\Models\Finca;
use App\Models\Lote;
use App\Models\RegistroOrdenio;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistroOrdenioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registro = $this->route('ordenio');

        return $registro
            ? $this->user()->can('update', $registro)
            : $this->user()->can('create', RegistroOrdenio::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $ordenio = $this->route('ordenio');
        $rules = [
            'litros' => ['required', 'numeric', 'gt:0', 'max:999999.99', 'decimal:0,2'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
        if ($ordenio) {
            return $rules + [
                'ganado_id' => ['missing'], 'lote_historico_id' => ['missing'],
                'fecha' => ['missing'], 'turno' => ['missing'],
            ];
        }

        return $rules + [
            'ganado_id' => ['required', 'integer', Rule::exists('ganado', 'id')->where(fn (Builder $query) => $query
                ->whereIn('lote_id', Lote::forUser($this->user())->select('id'))->where('sexo', 'Hembra')->where('estado', 'Activo'))],
            'lote_historico_id' => [Rule::requiredIf(fn () => $this->input('fecha') !== today()->toDateString()), 'nullable', 'integer',
                Rule::exists('lotes', 'id')->where(fn (Builder $query) => $query->whereIn('finca_id', Finca::forUser($this->user())->select('id')))],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'turno' => ['required', Rule::in(['Mañana', 'Tarde']),
                Rule::unique('registros_ordenio', 'turno')->where('ganado_id', $this->input('ganado_id'))->where('fecha', $this->input('fecha'))],
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
