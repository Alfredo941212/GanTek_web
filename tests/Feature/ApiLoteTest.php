<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Lote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_only_own_lots(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownFinca = Finca::factory()->create([
            'user_id' => $user->id,
        ]);

        $foreignFinca = Finca::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $ownLote = Lote::factory()->create([
            'finca_id' => $ownFinca->id,
            'nombre' => 'Lote Propio',
        ]);

        $foreignLote = Lote::factory()->create([
            'finca_id' => $foreignFinca->id,
            'nombre' => 'Lote Ajeno',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/lotes')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ownLote->id,
                'nombre' => 'Lote Propio',
            ])
            ->assertJsonMissing([
                'id' => $foreignLote->id,
                'nombre' => 'Lote Ajeno',
            ]);
    }

    public function test_authenticated_user_can_create_lot_in_own_farm(): void
    {
        $user = User::factory()->create();

        $finca = Finca::factory()->create([
            'user_id' => $user->id,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/lotes', [
                'finca_id' => $finca->id,
                'nombre' => 'Lote Lechero',
                'descripcion' => 'Vacas en producción.',
                'produccion_minima_por_vaca' => 4.00,
                'estado' => 'Activo',
            ])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Lote Lechero')
            ->assertJsonPath('data.finca_id', $finca->id);

        $this->assertDatabaseHas('lotes', [
            'finca_id' => $finca->id,
            'nombre' => 'Lote Lechero',
        ]);
    }

    public function test_user_cannot_create_lot_in_another_users_farm(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $foreignFinca = Finca::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/lotes', [
                'finca_id' => $foreignFinca->id,
                'nombre' => 'Lote Manipulado',
                'descripcion' => null,
                'produccion_minima_por_vaca' => 4.00,
                'estado' => 'Activo',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('finca_id');

        $this->assertDatabaseMissing('lotes', [
            'finca_id' => $foreignFinca->id,
            'nombre' => 'Lote Manipulado',
        ]);
    }

    public function test_owner_can_show_and_update_own_lot(): void
    {
        $user = User::factory()->create();

        $finca = Finca::factory()->create([
            'user_id' => $user->id,
        ]);

        $lote = Lote::factory()->create([
            'finca_id' => $finca->id,
            'nombre' => 'Lote Original',
            'produccion_minima_por_vaca' => 4.00,
            'estado' => 'Activo',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/lotes/{$lote->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $lote->id);

        $this->withToken($token)
            ->putJson("/api/lotes/{$lote->id}", [
                'nombre' => 'Lote Actualizado',
                'descripcion' => 'Descripción actualizada',
                'produccion_minima_por_vaca' => 5.00,
                'estado' => 'Activo',
            ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Lote Actualizado')
            ->assertJsonPath('data.produccion_minima_por_vaca', '5.00');

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'finca_id' => $finca->id,
            'nombre' => 'Lote Actualizado',
            'produccion_minima_por_vaca' => 5.00,
        ]);
    }

    public function test_user_cannot_change_lot_farm_when_updating(): void
    {
        $user = User::factory()->create();

        $finca = Finca::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherFinca = Finca::factory()->create([
            'user_id' => $user->id,
        ]);

        $lote = Lote::factory()->create([
            'finca_id' => $finca->id,
            'produccion_minima_por_vaca' => 4.00,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/lotes/{$lote->id}", [
                'finca_id' => $otherFinca->id,
                'nombre' => $lote->nombre,
                'descripcion' => $lote->descripcion,
                'produccion_minima_por_vaca' => 4.00,
                'estado' => $lote->estado,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('finca_id');

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'finca_id' => $finca->id,
        ]);
    }

    public function test_user_cannot_access_another_users_lot(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $foreignFinca = Finca::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $foreignLote = Lote::factory()->create([
            'finca_id' => $foreignFinca->id,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/lotes/{$foreignLote->id}")
            ->assertForbidden();

        $this->withToken($token)
            ->putJson("/api/lotes/{$foreignLote->id}", [
                'nombre' => 'Intento de modificación',
                'descripcion' => null,
                'produccion_minima_por_vaca' => 4.00,
                'estado' => 'Activo',
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->deleteJson("/api/lotes/{$foreignLote->id}")
            ->assertForbidden();
    }

    public function test_owner_can_delete_own_lot_without_history(): void
    {
        $user = User::factory()->create();

        $finca = Finca::factory()->create([
            'user_id' => $user->id,
        ]);

        $lote = Lote::factory()->create([
            'finca_id' => $finca->id,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/lotes/{$lote->id}")
            ->assertOk()
            ->assertJson([
                'message' => 'Lote eliminado correctamente.',
            ]);

        $this->assertDatabaseMissing('lotes', [
            'id' => $lote->id,
        ]);
    }
}