<?php

namespace App\Http\Requests;

use App\Models\Lote;
use App\Models\Vacunacion;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class VacunacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registro = $this->route('vacunacion');

        return $registro
            ? $this->user()->can('update', $registro)
            : $this->user()->can('create', Vacunacion::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $vacunacion = $this->route('vacunacion');
        $catalogo = fn (string $tabla, ?int $actual): Exists => Rule::exists($tabla, 'id')->where(function (Builder $query) use ($actual): void {
            $query->where('estado', 'Activo');
            if ($actual) {
                $query->orWhere('id', $actual);
            }
        });

        return [
            'ganado_id' => $vacunacion ? ['missing'] : ['required', 'integer', Rule::exists('ganado', 'id')->where(fn (Builder $query) => $query->whereIn('lote_id', Lote::forUser($this->user())->select('id'))->where('estado', 'Activo'))],
            'veterinario_id' => ['required', 'integer', $catalogo('veterinarios', $vacunacion?->veterinario_id)],
            'vacuna_id' => ['required', 'integer', $catalogo('vacunas', $vacunacion?->vacuna_id)],
            'fecha_aplicacion' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'proxima_aplicacion' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_aplicacion'],
            'dosis' => ['required', 'string', 'max:100'],
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
            'missing' => 'No se permite enviar ni modificar :attribute.',
            'litros.gt' => 'Los litros deben ser mayores que cero.',
            'decimal' => 'El campo :attribute admite como máximo dos decimales.',
        ];
    }
}
