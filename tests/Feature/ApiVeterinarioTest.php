<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Veterinario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiVeterinarioTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('gantek-mobile')->plainTextToken;
    }

    private function veterinarioPayload(array $changes = []): array
    {
        return array_merge([
            'nombre' => 'Dr. Juan Perez',
            'cedula_profesional' => 'CED-API-001',
            'telefono' => '9991234567',
            'correo' => 'juan@example.com',
            'especialidad' => 'Bovinos',
            'estado' => 'Activo',
            'observaciones' => null,
        ], $changes);
    }

    public function test_authenticated_user_can_list_veterinarians(): void
    {
        $user = User::factory()->create();

        $veterinario = Veterinario::factory()->create([
            'nombre' => 'Dr. Juan Perez',
            'estado' => 'Activo',
        ]);

        $this->withToken($this->tokenFor($user))
            ->getJson('/api/veterinarios')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $veterinario->id,
                'nombre' => 'Dr. Juan Perez',
            ]);
    }

    public function test_authenticated_user_can_view_veterinarian(): void
    {
        $user = User::factory()->create();

        $veterinario = Veterinario::factory()->create([
            'nombre' => 'Dra. Maria Lopez',
        ]);

        $this->withToken($this->tokenFor($user))
            ->getJson("/api/veterinarios/{$veterinario->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $veterinario->id);
    }

    public function test_normal_user_can_create_veterinarian(): void
    {
        $user = User::factory()->create([
            'puede_gestionar_catalogos' => false,
        ]);

        $this->withToken($this->tokenFor($user))
            ->postJson(
                '/api/veterinarios',
                $this->veterinarioPayload()
            )
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Dr. Juan Perez')
            ->assertJsonPath('data.cedula_profesional', 'CED-API-001');

        $this->assertDatabaseHas('veterinarios', [
            'cedula_profesional' => 'CED-API-001',
            'estado' => 'Activo',
        ]);
    }

    public function test_catalog_manager_can_create_veterinarian(): void
    {
        $admin = User::factory()->create([
            'puede_gestionar_catalogos' => true,
        ]);

        $this->withToken($this->tokenFor($admin))
            ->postJson('/api/veterinarios', $this->veterinarioPayload([
                'nombre' => 'Dra. Ana Garcia',
                'cedula_profesional' => 'CED-API-002',
                'telefono' => '9997654321',
                'correo' => 'ana@example.com',
                'especialidad' => 'Medicina bovina',
                'observaciones' => 'Veterinaria de prueba.',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Dra. Ana Garcia')
            ->assertJsonPath('data.cedula_profesional', 'CED-API-002');

        $this->assertDatabaseHas('veterinarios', [
            'cedula_profesional' => 'CED-API-002',
            'estado' => 'Activo',
        ]);
    }

    public function test_normal_user_can_update_veterinarian(): void
    {
        $user = User::factory()->create([
            'puede_gestionar_catalogos' => false,
        ]);

        $veterinario = Veterinario::factory()->create([
            'estado' => 'Activo',
        ]);

        $this->withToken($this->tokenFor($user))
            ->putJson(
                "/api/veterinarios/{$veterinario->id}",
                $this->veterinarioPayload([
                    'nombre' => 'Nombre Modificado',
                    'cedula_profesional' => $veterinario->cedula_profesional,
                    'telefono' => null,
                    'correo' => null,
                    'especialidad' => null,
                ])
            )
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Nombre Modificado');

        $this->assertDatabaseHas('veterinarios', [
            'id' => $veterinario->id,
            'nombre' => 'Nombre Modificado',
        ]);
    }

    public function test_catalog_manager_can_update_veterinarian(): void
    {
        $admin = User::factory()->create([
            'puede_gestionar_catalogos' => true,
        ]);

        $veterinario = Veterinario::factory()->create([
            'nombre' => 'Nombre Original',
            'estado' => 'Activo',
        ]);

        $this->withToken($this->tokenFor($admin))
            ->putJson(
                "/api/veterinarios/{$veterinario->id}",
                $this->veterinarioPayload([
                    'nombre' => 'Nombre Actualizado',
                    'cedula_profesional' => $veterinario->cedula_profesional,
                    'telefono' => $veterinario->telefono,
                    'correo' => $veterinario->correo,
                    'especialidad' => $veterinario->especialidad,
                    'observaciones' => 'Actualizado desde API.',
                ])
            )
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Nombre Actualizado');

        $this->assertDatabaseHas('veterinarios', [
            'id' => $veterinario->id,
            'nombre' => 'Nombre Actualizado',
        ]);
    }

    public function test_normal_user_can_deactivate_veterinarian(): void
    {
        $user = User::factory()->create([
            'puede_gestionar_catalogos' => false,
        ]);

        $veterinario = Veterinario::factory()->create([
            'estado' => 'Activo',
        ]);

        $this->withToken($this->tokenFor($user))
            ->deleteJson("/api/veterinarios/{$veterinario->id}")
            ->assertOk()
            ->assertJsonPath('data.estado', 'Inactivo');

        $this->assertDatabaseHas('veterinarios', [
            'id' => $veterinario->id,
            'estado' => 'Inactivo',
        ]);
    }

    public function test_catalog_manager_can_deactivate_veterinarian_without_deleting_it(): void
    {
        $admin = User::factory()->create([
            'puede_gestionar_catalogos' => true,
        ]);

        $veterinario = Veterinario::factory()->create([
            'estado' => 'Activo',
        ]);

        $this->withToken($this->tokenFor($admin))
            ->deleteJson("/api/veterinarios/{$veterinario->id}")
            ->assertOk()
            ->assertJsonPath('data.estado', 'Inactivo');

        $this->assertDatabaseHas('veterinarios', [
            'id' => $veterinario->id,
            'estado' => 'Inactivo',
        ]);
    }

    public function test_guest_cannot_access_veterinarians_api(): void
    {
        $this->getJson('/api/veterinarios')
            ->assertUnauthorized();

        $this->postJson(
            '/api/veterinarios',
            $this->veterinarioPayload()
        )->assertUnauthorized();

        $veterinario = Veterinario::factory()->create();

        $this->putJson(
            "/api/veterinarios/{$veterinario->id}",
            $this->veterinarioPayload([
                'cedula_profesional' => $veterinario->cedula_profesional,
            ])
        )->assertUnauthorized();

        $this->deleteJson("/api/veterinarios/{$veterinario->id}")
            ->assertUnauthorized();
    }
}
