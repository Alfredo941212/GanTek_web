<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    /** @param list<string> $relaciones */
    protected function deleteWithoutHistory(Model $registro, array $relaciones): void
    {
        foreach ($relaciones as $relacion) {
            if ($registro->{$relacion}()->exists()) {
                throw ValidationException::withMessages(['registro' => 'Este registro tiene información asociada y no se puede eliminar.']);
            }
        }
        try {
            $registro->delete();
        } catch (QueryException $exception) {
            if (str_starts_with((string) $exception->getCode(), '23')) {
                throw ValidationException::withMessages(['registro' => 'Este registro tiene información asociada y no se puede eliminar.']);
            }
            throw $exception;
        }
    }
}
