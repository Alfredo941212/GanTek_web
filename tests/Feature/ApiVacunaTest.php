<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vacuna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiVacunaTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('gantek-mobile')->plainTextToken;
    }

    private function vacunaPayload(array $changes = []): array
    {
        return array_merge([
            'nombre' => 'Vacuna GanTek API',
            'fabricante' => 'Laboratorio GanTek',
            'descripcion' => 'Vacuna creada mediante API.',
            'dosis_recomendada' => '2 ml',
            'intervalo_dias' => 180,
            'estado' => 'Activo',
        ], $changes);
    }

    public function test_authenticated_user_can_list_vaccines(): void
    {
        $user = User::factory()->create();

        $vacuna = Vacuna::factory()->create([
            'nombre' => 'Vacuna API',
            'estado' => 'Activo',
        ]);

        $this->withToken($this->tokenFor($user))
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

        $this->withToken($this->tokenFor($user))
            ->getJson("/api/vacunas/{$vacuna->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $vacuna->id);
    }

    public function test_normal_user_can_create_vaccine(): void
    {
        $user = User::factory()->create([
            'puede_gestionar_catalogos' => false,
        ]);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/vacunas', $this->vacunaPayload([
                'nombre' => 'Vacuna Usuario Normal',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Vacuna Usuario Normal');

        $this->assertDatabaseHas('vacunas', [
            'nombre' => 'Vacuna Usuario Normal',
            'estado' => 'Activo',
        ]);
    }

    public function test_catalog_manager_can_create_vaccine(): void
    {
        $admin = User::factory()->create([
            'puede_gestionar_catalogos' => true,
        ]);

        $this->withToken($this->tokenFor($admin))
            ->postJson('/api/vacunas', $this->vacunaPayload())
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Vacuna GanTek API')
            ->assertJsonPath('data.intervalo_dias', 180);

        $this->assertDatabaseHas('vacunas', [
            'nombre' => 'Vacuna GanTek API',
            'estado' => 'Activo',
        ]);
    }

    public function test_normal_user_can_update_vaccine(): void
    {
        $user = User::factory()->create([
            'puede_gestionar_catalogos' => false,
        ]);

        $vacuna = Vacuna::factory()->create([
            'nombre' => 'Vacuna Original',
            'estado' => 'Activo',
        ]);

        $this->withToken($this->tokenFor($user))
            ->putJson(
                "/api/vacunas/{$vacuna->id}",
                $this->vacunaPayload([
                    'nombre' => 'Vacuna Editada por Usuario',
                ])
            )
            ->assertOk()
            ->assertJsonPath(
                'data.nombre',
                'Vacuna Editada por Usuario'
            );

        $this->assertDatabaseHas('vacunas', [
            'id' => $vacuna->id,
            'nombre' => 'Vacuna Editada por Usuario',
        ]);
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

        $this->withToken($this->tokenFor($admin))
            ->putJson(
                "/api/vacunas/{$vacuna->id}",
                $this->vacunaPayload([
                    'nombre' => 'Vacuna Actualizada',
                    'fabricante' => 'Nuevo Laboratorio',
                    'descripcion' => 'Informacion actualizada.',
                    'dosis_recomendada' => '3 ml',
                    'intervalo_dias' => 365,
                ])
            )
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Vacuna Actualizada')
            ->assertJsonPath('data.intervalo_dias', 365);

        $this->assertDatabaseHas('vacunas', [
            'id' => $vacuna->id,
            'nombre' => 'Vacuna Actualizada',
            'intervalo_dias' => 365,
        ]);
    }

    public function test_normal_user_can_deactivate_vaccine(): void
    {
        $user = User::factory()->create([
            'puede_gestionar_catalogos' => false,
        ]);

        $vacuna = Vacuna::factory()->create([
            'estado' => 'Activo',
        ]);

        $this->withToken($this->tokenFor($user))
            ->deleteJson("/api/vacunas/{$vacuna->id}")
            ->assertOk()
            ->assertJsonPath('data.estado', 'Inactivo');

        $this->assertDatabaseHas('vacunas', [
            'id' => $vacuna->id,
            'estado' => 'Inactivo',
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

        $this->withToken($this->tokenFor($admin))
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

        $this->postJson(
            '/api/vacunas',
            $this->vacunaPayload()
        )->assertUnauthorized();

        $vacuna = Vacuna::factory()->create();

        $this->putJson(
            "/api/vacunas/{$vacuna->id}",
            $this->vacunaPayload()
        )->assertUnauthorized();

        $this->deleteJson("/api/vacunas/{$vacuna->id}")
            ->assertUnauthorized();
    }
}
