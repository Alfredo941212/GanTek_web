<?php

namespace App\Policies;

use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Lote;
use App\Models\RegistroOrdenio;
use App\Models\User;
use App\Models\Vacunacion;

class GanaderiaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Finca|Lote|Ganado|Vacunacion|RegistroOrdenio $registro): bool
    {
        return $registro->newQuery()->forUser($user)->whereKey($registro->id)->exists();
    }

    public function update(User $user, Finca|Lote|Ganado|Vacunacion|RegistroOrdenio $registro): bool
    {
        return $this->view($user, $registro);
    }

    public function delete(User $user, Finca|Lote|Ganado|Vacunacion|RegistroOrdenio $registro): bool
    {
        return $this->view($user, $registro);
    }
}
