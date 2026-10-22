<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vacuna;
use App\Models\Veterinario;

class CatalogoPolicy
{
    // Todos pueden consultar
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Vacuna|Veterinario $registro): bool
    {
        return true;
    }

    // Todos pueden registrar
    public function create(User $user): bool
    {
        return true;
    }

    // Todos pueden editar
    public function update(User $user, Vacuna|Veterinario $registro): bool
    {
        return true;
    }

    // Todos pueden desactivar
    public function delete(User $user, Vacuna|Veterinario $registro): bool
    {
        return true;
    }
}
