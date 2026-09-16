<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vacuna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiVacunaTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_vaccines(): void
    {
        $user = User::factory()->create();

        $vacuna = Vacuna::factory()->create([
            'nombre' => 'Vacuna API',
            'estado' => 'Activo',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/vacunas')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $vacuna->id,
                'nombre' => 'Vacuna API',
            ]);
    }

    public function test_authenticated_user_can_view_vaccine(): void
    {
        $user = User::factory()->create();

        $vacuna = Vacuna::factory()->create([
            'nombre' => 'Vacuna Individual',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/vacunas/{$vacuna->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $vacuna->id);
    }

    public function test_normal_user_cannot_create_vaccine(): void
    {
        $user = User::factory()->create([
            'puede_gestionar_catalogos' => false,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/vacunas', [
                'nombre' => 'Vacuna Sin Permiso',
                'fabricante' => 'Laboratorio API',
                'descripcion' => 'Prueba de seguridad.',
                'dosis_recomendada' => '2 ml',
                'intervalo_dias' => 180,
                'estado' => 'Activo',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('vacunas', [
            'nombre' => 'Vacuna Sin Permiso',
        ]);
    }

    public function test_catalog_manager_can_create_vaccine(): void
    {
        $admin = User::factory()->create([
            'puede_gestionar_catalogos' => true,
        ]);

        $token = $admin->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/vacunas', [
                'nombre' => 'Vacuna GanTek API',
                'fabricante' => 'Laboratorio GanTek',
                'descripcion' => 'Vacuna creada mediante API.',
                'dosis_recomendada' => '2 ml',
                'intervalo_dias' => 180,
                'estado' => 'Activo',
            ])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Vacuna GanTek API')
            ->assertJsonPath('data.intervalo_dias', 180);

        $this->assertDatabaseHas('vacunas', [
            'nombre' => 'Vacuna GanTek API',
            'estado' => 'Activo',
        ]);
    }

    public function test_normal_user_cannot_update_vaccine(): void
    {
        $user = User::factory()->create([
            'puede_gestionar_catalogos' => false,
        ]);

        $vacuna = Vacuna::factory()->create();

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/vacunas/{$vacuna->id}", [
                'nombre' => 'Intento de modificación',
                'fabricante' => $vacuna->fabricante,
                'descripcion' => $vacuna->descripcion,
                'dosis_recomendada' => $vacuna->dosis_recomendada,
                'intervalo_dias' => $vacuna->intervalo_dias,
                'estado' => 'Activo',
            ])
            ->assertForbidden();
    }

    public function test_catalog_manager_can_update_vaccine(): void
    {
        $admin = User::factory()->create([
            'puede_gestionar_catalogos' => true,
        ]);

        $vacuna = Vacuna::factory()->create([
            'nombre' => 'Vacuna Original',
            'estado' => 'Activo',
        ]);

        $token = $admin->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/vacunas/{$vacuna->id}", [
                'nombre' => 'Vacuna Actualizada',
                'fabricante' => 'Nuevo Laboratorio',
                'descripcion' => 'Información actualizada.',
                'dosis_recomendada' => '3 ml',
                'intervalo_dias' => 365,
                'estado' => 'Activo',
            ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Vacuna Actualizada')
            ->assertJsonPath('data.intervalo_dias', 365);

        $this->assertDatabaseHas('vacunas', [
            'id' => $vacuna->id,
            'nombre' => 'Vacuna Actualizada',
            'intervalo_dias' => 365,
        ]);
    }

    public function test_normal_user_cannot_deactivate_vaccine(): void
    {
        $user = User::factory()->create([
            'puede_gestionar_catalogos' => false,
        ]);

        $vacuna = Vacuna::factory()->create([
            'estado' => 'Activo',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/vacunas/{$vacuna->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('vacunas', [
            'id' => $vacuna->id,
            'estado' => 'Activo',
        ]);
    }

    public function test_catalog_manager_can_deactivate_vaccine_without_deleting_it(): void
    {
        $admin = User::factory()->create([
            'puede_gestionar_catalogos' => true,
        ]);

        $vacuna = Vacuna::factory()->create([
            'estado' => 'Activo',
        ]);

        $token = $admin->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/vacunas/{$vacuna->id}")
            ->assertOk()
            ->assertJsonPath('data.estado', 'Inactivo');

        $this->assertDatabaseHas('vacunas', [
            'id' => $vacuna->id,
            'estado' => 'Inactivo',
        ]);
    }

    public function test_guest_cannot_access_vaccines_api(): void
    {
        $this->getJson('/api/vacunas')
            ->assertUnauthorized();
    }
}