<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\User;
use Tests\TestCase;

class ApiFincaTest extends TestCase
{
    /** @return list<string> */
    protected function migrationPaths(): array
    {
        return [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_09_12_161426_create_fincas_table.php',
            'database/migrations/2026_09_12_161428_create_lotes_table.php',
            'database/migrations/2026_09_15_143411_create_personal_access_tokens_table.php',
            'database/migrations/2026_09_12_161456_add_catalog_management_permission_to_users_table.php',
            'database/migrations/2026_10_20_121413_create_ubicaciones_tables.php',
        ];
    }

    public function test_authenticated_user_can_list_only_own_farms(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownFinca = Finca::factory()->create([
            'user_id' => $user->id,
            'nombre' => 'Mi Finca',
        ]);

        $foreignFinca = Finca::factory()->create([
            'user_id' => $otherUser->id,
            'nombre' => 'Finca Ajena',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/fincas')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ownFinca->id,
                'nombre' => 'Mi Finca',
            ])
            ->assertJsonMissing([
                'id' => $foreignFinca->id,
                'nombre' => 'Finca Ajena',
            ]);
    }

    public function test_authenticated_user_can_create_farm(): void
    {
        Finca::factory()->conUbicacionCatalogada()->make();
        $user = User::factory()->create();
        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/fincas', [
                'nombre' => 'Finca Lechera',
                'estado_clave' => '01',
                'municipio_clave' => '01001',
                'localidad_clave' => '010010001',
                'municipio' => 'Ocosingo',
                'localidad' => 'San José',
                'estado' => 'Chiapas',
                'superficie' => 25.50,
                'observaciones' => 'Finca para producción lechera.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Finca Lechera')
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('fincas', [
            'user_id' => $user->id,
            'nombre' => 'Finca Lechera',
        ]);
    }

    public function test_user_cannot_assign_farm_to_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/fincas', [
                'user_id' => $otherUser->id,
                'nombre' => 'Finca Manipulada',
                'municipio' => 'Ocosingo',
                'estado' => 'Chiapas',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');

        $this->assertDatabaseMissing('fincas', [
            'nombre' => 'Finca Manipulada',
        ]);
    }

    public function test_owner_can_show_and_update_own_farm(): void
    {
        $user = User::factory()->create();

        $finca = Finca::factory()->conUbicacionCatalogada()->create([
            'user_id' => $user->id,
            'nombre' => 'Nombre Original',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/fincas/{$finca->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $finca->id);

        $this->withToken($token)
            ->putJson("/api/fincas/{$finca->id}", [
                'nombre' => 'Nombre Actualizado',
                'estado_clave' => '01',
                'municipio_clave' => '01001',
                'localidad_clave' => '010010001',
                'municipio' => $finca->municipio,
                'localidad' => $finca->localidad,
                'estado' => $finca->estado,
                'superficie' => $finca->superficie,
                'observaciones' => $finca->observaciones,
            ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Nombre Actualizado');

        $this->assertDatabaseHas('fincas', [
            'id' => $finca->id,
            'user_id' => $user->id,
            'nombre' => 'Nombre Actualizado',
        ]);
    }

    public function test_user_cannot_access_another_users_farm(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $foreignFinca = Finca::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/fincas/{$foreignFinca->id}")
            ->assertForbidden();

        $this->withToken($token)
            ->putJson("/api/fincas/{$foreignFinca->id}", [
                'nombre' => 'Intento de cambio',
                'municipio' => $foreignFinca->municipio,
                'localidad' => $foreignFinca->localidad,
                'estado' => $foreignFinca->estado,
                'superficie' => $foreignFinca->superficie,
                'observaciones' => $foreignFinca->observaciones,
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->deleteJson("/api/fincas/{$foreignFinca->id}")
            ->assertForbidden();
    }

    public function test_owner_can_delete_own_farm_without_history(): void
    {
        $user = User::factory()->create();

        $finca = Finca::factory()->create([
            'user_id' => $user->id,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/fincas/{$finca->id}")
            ->assertOk()
            ->assertJson([
                'message' => 'Finca eliminada correctamente.',
            ]);

        $this->assertDatabaseMissing('fincas', [
            'id' => $finca->id,
        ]);
    }
}
