<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Veterinario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiVeterinarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_veterinarians(): void
    {
        $user = User::factory()->create();

        $veterinario = Veterinario::factory()->create([
            'nombre' => 'Dr. Juan Pérez',
            'estado' => 'Activo',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/veterinarios')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $veterinario->id,
                'nombre' => 'Dr. Juan Pérez',
            ]);
    }

    public function test_authenticated_user_can_view_veterinarian(): void
    {
        $user = User::factory()->create();

        $veterinario = Veterinario::factory()->create([
            'nombre' => 'Dra. María López',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/veterinarios/{$veterinario->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $veterinario->id);
    }

    public function test_normal_user_cannot_create_veterinarian(): void
    {
        $user = User::factory()->create([
            'puede_gestionar_catalogos' => false,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/veterinarios', [
                'nombre' => 'Dr. Sin Permiso',
                'cedula_profesional' => 'CED-API-001',
                'telefono' => '9991234567',
                'correo' => 'sinpermiso@example.com',
                'especialidad' => 'Bovinos',
                'estado' => 'Activo',
                'observaciones' => null,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('veterinarios', [
            'cedula_profesional' => 'CED-API-001',
        ]);
    }

    public function test_catalog_manager_can_create_veterinarian(): void
    {
        $admin = User::factory()->create([
            'puede_gestionar_catalogos' => true,
        ]);

        $token = $admin->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/veterinarios', [
                'nombre' => 'Dra. Ana García',
                'cedula_profesional' => 'CED-API-002',
                'telefono' => '9997654321',
                'correo' => 'ana@example.com',
                'especialidad' => 'Medicina bovina',
                'estado' => 'Activo',
                'observaciones' => 'Veterinaria de prueba.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Dra. Ana García')
            ->assertJsonPath('data.cedula_profesional', 'CED-API-002');

        $this->assertDatabaseHas('veterinarios', [
            'cedula_profesional' => 'CED-API-002',
            'estado' => 'Activo',
        ]);
    }

    public function test_normal_user_cannot_update_veterinarian(): void
    {
        $user = User::factory()->create([
            'puede_gestionar_catalogos' => false,
        ]);

        $veterinario = Veterinario::factory()->create();

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/veterinarios/{$veterinario->id}", [
                'nombre' => 'Nombre Modificado',
                'cedula_profesional' => $veterinario->cedula_profesional,
                'telefono' => null,
                'correo' => null,
                'especialidad' => null,
                'estado' => 'Activo',
                'observaciones' => null,
            ])
            ->assertForbidden();
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

        $token = $admin->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/veterinarios/{$veterinario->id}", [
                'nombre' => 'Nombre Actualizado',
                'cedula_profesional' => $veterinario->cedula_profesional,
                'telefono' => $veterinario->telefono,
                'correo' => $veterinario->correo,
                'especialidad' => $veterinario->especialidad,
                'estado' => 'Activo',
                'observaciones' => 'Actualizado desde API.',
            ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Nombre Actualizado');

        $this->assertDatabaseHas('veterinarios', [
            'id' => $veterinario->id,
            'nombre' => 'Nombre Actualizado',
        ]);
    }

    public function test_normal_user_cannot_deactivate_veterinarian(): void
    {
        $user = User::factory()->create([
            'puede_gestionar_catalogos' => false,
        ]);

        $veterinario = Veterinario::factory()->create([
            'estado' => 'Activo',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/veterinarios/{$veterinario->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('veterinarios', [
            'id' => $veterinario->id,
            'estado' => 'Activo',
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

        $token = $admin->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
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
    }
}