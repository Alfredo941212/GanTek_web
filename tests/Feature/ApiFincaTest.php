<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiFincaTest extends TestCase
{
    use RefreshDatabase;

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
        $user = User::factory()->create();
        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/fincas', [
                'nombre' => 'Finca Lechera',
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

        $finca = Finca::factory()->create([
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