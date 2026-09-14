<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vacuna;
use App\Models\Veterinario;

class CatalogoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Vacuna|Veterinario $registro): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->puede_gestionar_catalogos;
    }

    public function update(User $user, Vacuna|Veterinario $registro): bool
    {
        return $user->puede_gestionar_catalogos;
    }

    public function delete(User $user, Vacuna|Veterinario $registro): bool
    {
        return $user->puede_gestionar_catalogos;
    }
}
