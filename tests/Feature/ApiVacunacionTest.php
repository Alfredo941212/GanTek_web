<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Lote;
use App\Models\User;
use App\Models\Vacuna;
use App\Models\Vacunacion;
use App\Models\Veterinario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiVacunacionTest extends TestCase
{
    use RefreshDatabase;

    private function crearAnimal(User $user): Ganado
    {
        $finca = Finca::factory()->create([
            'user_id' => $user->id,
            'estado' => 'Activo',
        ]);

        $lote = Lote::factory()->create([
            'finca_id' => $finca->id,
            'estado' => 'Activo',
        ]);

        return Ganado::factory()->create([
            'lote_id' => $lote->id,
            'estado' => 'Activo',
            'fecha_ingreso' => today()->subYear()->toDateString(),
        ]);
    }

    public function test_authenticated_user_can_list_only_own_vaccinations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $animal = $this->crearAnimal($user);
        $otherAnimal = $this->crearAnimal($otherUser);

        $vacuna = Vacuna::factory()->create(['estado' => 'Activo']);
        $veterinario = Veterinario::factory()->create(['estado' => 'Activo']);

        $propia = Vacunacion::factory()->create([
            'ganado_id' => $animal->id,
            'vacuna_id' => $vacuna->id,
            'veterinario_id' => $veterinario->id,
        ]);

        $ajena = Vacunacion::factory()->create([
            'ganado_id' => $otherAnimal->id,
            'vacuna_id' => $vacuna->id,
            'veterinario_id' => $veterinario->id,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/vacunaciones')
            ->assertOk();

        $response->assertJsonFragment([
            'id' => $propia->id,
        ]);

        $response->assertJsonMissing([
            'id' => $ajena->id,
        ]);
    }

    public function test_user_can_register_vaccination_for_own_active_animal(): void
    {
        $user = User::factory()->create();
        $animal = $this->crearAnimal($user);

        $vacuna = Vacuna::factory()->create([
            'estado' => 'Activo',
        ]);

        $veterinario = Veterinario::factory()->create([
            'estado' => 'Activo',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/vacunaciones', [
                'ganado_id' => $animal->id,
                'vacuna_id' => $vacuna->id,
                'veterinario_id' => $veterinario->id,
                'fecha_aplicacion' => today()->toDateString(),
                'proxima_aplicacion' => today()->addMonths(6)->toDateString(),
                'dosis' => '2 ml',
                'observaciones' => 'Aplicación desde API.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.ganado_id', $animal->id)
            ->assertJsonPath('data.vacuna_id', $vacuna->id)
            ->assertJsonPath('data.veterinario_id', $veterinario->id);

        $this->assertDatabaseHas('vacunaciones', [
            'ganado_id' => $animal->id,
            'vacuna_id' => $vacuna->id,
            'veterinario_id' => $veterinario->id,
            'dosis' => '2 ml',
        ]);
    }

    public function test_user_cannot_register_vaccination_for_another_users_animal(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherAnimal = $this->crearAnimal($otherUser);

        $vacuna = Vacuna::factory()->create(['estado' => 'Activo']);
        $veterinario = Veterinario::factory()->create(['estado' => 'Activo']);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/vacunaciones', [
                'ganado_id' => $otherAnimal->id,
                'vacuna_id' => $vacuna->id,
                'veterinario_id' => $veterinario->id,
                'fecha_aplicacion' => today()->toDateString(),
                'proxima_aplicacion' => today()->addMonths(6)->toDateString(),
                'dosis' => '2 ml',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('vacunaciones', [
            'ganado_id' => $otherAnimal->id,
        ]);
    }

    public function test_inactive_vaccine_cannot_be_used_for_new_vaccination(): void
    {
        $user = User::factory()->create();
        $animal = $this->crearAnimal($user);

        $vacuna = Vacuna::factory()->create([
            'estado' => 'Inactivo',
        ]);

        $veterinario = Veterinario::factory()->create([
            'estado' => 'Activo',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/vacunaciones', [
                'ganado_id' => $animal->id,
                'vacuna_id' => $vacuna->id,
                'veterinario_id' => $veterinario->id,
                'fecha_aplicacion' => today()->toDateString(),
                'dosis' => '2 ml',
            ])
            ->assertUnprocessable();
    }

    public function test_inactive_veterinarian_cannot_be_used_for_new_vaccination(): void
    {
        $user = User::factory()->create();
        $animal = $this->crearAnimal($user);

        $vacuna = Vacuna::factory()->create([
            'estado' => 'Activo',
        ]);

        $veterinario = Veterinario::factory()->create([
            'estado' => 'Inactivo',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/vacunaciones', [
                'ganado_id' => $animal->id,
                'vacuna_id' => $vacuna->id,
                'veterinario_id' => $veterinario->id,
                'fecha_aplicacion' => today()->toDateString(),
                'dosis' => '2 ml',
            ])
            ->assertUnprocessable();
    }

    public function test_application_date_cannot_be_before_animal_entry_date(): void
    {
        $user = User::factory()->create();

        $finca = Finca::factory()->create([
            'user_id' => $user->id,
            'estado' => 'Activo',
        ]);

        $lote = Lote::factory()->create([
            'finca_id' => $finca->id,
            'estado' => 'Activo',
        ]);

        $animal = Ganado::factory()->create([
            'lote_id' => $lote->id,
            'estado' => 'Activo',
            'fecha_ingreso' => today()->subDays(5)->toDateString(),
        ]);

        $vacuna = Vacuna::factory()->create(['estado' => 'Activo']);
        $veterinario = Veterinario::factory()->create(['estado' => 'Activo']);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/vacunaciones', [
                'ganado_id' => $animal->id,
                'vacuna_id' => $vacuna->id,
                'veterinario_id' => $veterinario->id,
                'fecha_aplicacion' => today()->subDays(10)->toDateString(),
                'dosis' => '2 ml',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('vacunaciones', [
            'ganado_id' => $animal->id,
        ]);
    }

    public function test_owner_can_view_and_update_vaccination(): void
    {
        $user = User::factory()->create();
        $animal = $this->crearAnimal($user);

        $vacuna = Vacuna::factory()->create(['estado' => 'Activo']);
        $veterinario = Veterinario::factory()->create(['estado' => 'Activo']);

        $vacunacion = Vacunacion::factory()->create([
            'ganado_id' => $animal->id,
            'vacuna_id' => $vacuna->id,
            'veterinario_id' => $veterinario->id,
            'fecha_aplicacion' => today()->subMonth()->toDateString(),
            'dosis' => '2 ml',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/vacunaciones/{$vacunacion->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $vacunacion->id);

        $this->withToken($token)
            ->putJson("/api/vacunaciones/{$vacunacion->id}", [
                'vacuna_id' => $vacuna->id,
                'veterinario_id' => $veterinario->id,
                'fecha_aplicacion' => today()->subMonth()->toDateString(),
                'proxima_aplicacion' => today()->addMonths(5)->toDateString(),
                'dosis' => '3 ml',
                'observaciones' => 'Dosis actualizada.',
            ])
            ->assertOk()
            ->assertJsonPath('data.dosis', '3 ml');

        $this->assertDatabaseHas('vacunaciones', [
            'id' => $vacunacion->id,
            'dosis' => '3 ml',
        ]);
    }

    public function test_ganado_id_cannot_be_changed_when_updating_vaccination(): void
    {
        $user = User::factory()->create();

        $animal = $this->crearAnimal($user);
        $otherAnimal = $this->crearAnimal($user);

        $vacuna = Vacuna::factory()->create(['estado' => 'Activo']);
        $veterinario = Veterinario::factory()->create(['estado' => 'Activo']);

        $vacunacion = Vacunacion::factory()->create([
            'ganado_id' => $animal->id,
            'vacuna_id' => $vacuna->id,
            'veterinario_id' => $veterinario->id,
            'fecha_aplicacion' => today()->subMonth()->toDateString(),
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/vacunaciones/{$vacunacion->id}", [
                'ganado_id' => $otherAnimal->id,
                'vacuna_id' => $vacuna->id,
                'veterinario_id' => $veterinario->id,
                'fecha_aplicacion' => today()->subMonth()->toDateString(),
                'dosis' => '2 ml',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('vacunaciones', [
            'id' => $vacunacion->id,
            'ganado_id' => $animal->id,
        ]);
    }

    public function test_user_cannot_access_another_users_vaccination(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherAnimal = $this->crearAnimal($otherUser);

        $vacuna = Vacuna::factory()->create(['estado' => 'Activo']);
        $veterinario = Veterinario::factory()->create(['estado' => 'Activo']);

        $vacunacion = Vacunacion::factory()->create([
            'ganado_id' => $otherAnimal->id,
            'vacuna_id' => $vacuna->id,
            'veterinario_id' => $veterinario->id,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/vacunaciones/{$vacunacion->id}")
            ->assertForbidden();
    }

    public function test_owner_can_delete_vaccination(): void
    {
        $user = User::factory()->create();
        $animal = $this->crearAnimal($user);

        $vacuna = Vacuna::factory()->create(['estado' => 'Activo']);
        $veterinario = Veterinario::factory()->create(['estado' => 'Activo']);

        $vacunacion = Vacunacion::factory()->create([
            'ganado_id' => $animal->id,
            'vacuna_id' => $vacuna->id,
            'veterinario_id' => $veterinario->id,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/vacunaciones/{$vacunacion->id}")
            ->assertOk();

        $this->assertDatabaseMissing('vacunaciones', [
            'id' => $vacunacion->id,
        ]);
    }

    public function test_guest_cannot_access_vaccinations_api(): void
    {
        $this->getJson('/api/vacunaciones')
            ->assertUnauthorized();
    }
}