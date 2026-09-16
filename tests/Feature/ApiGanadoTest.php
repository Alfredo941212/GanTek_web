<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Lote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiGanadoTest extends TestCase
{
    use RefreshDatabase;

    private function crearLote(User $user): Lote
    {
        $finca = Finca::factory()->create([
            'user_id' => $user->id,
        ]);

        return Lote::factory()->create([
            'finca_id' => $finca->id,
            'estado' => 'Activo',
            'produccion_minima_por_vaca' => 4.00,
        ]);
    }

    public function test_authenticated_user_can_list_only_own_cattle(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownLote = $this->crearLote($user);
        $foreignLote = $this->crearLote($otherUser);

        $ownAnimal = Ganado::factory()->create([
            'lote_id' => $ownLote->id,
            'arete_siniiga' => 'MX-API-001',
        ]);

        $foreignAnimal = Ganado::factory()->create([
            'lote_id' => $foreignLote->id,
            'arete_siniiga' => 'MX-API-002',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/ganado')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ownAnimal->id,
                'arete_siniiga' => 'MX-API-001',
            ])
            ->assertJsonMissing([
                'id' => $foreignAnimal->id,
                'arete_siniiga' => 'MX-API-002',
            ]);
    }

    public function test_authenticated_user_can_create_cattle_in_own_lot(): void
    {
        $user = User::factory()->create();
        $lote = $this->crearLote($user);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/ganado', [
                'lote_id' => $lote->id,
                'arete_siniiga' => 'MX-API-100',
                'nombre' => 'Lola',
                'sexo' => 'Hembra',
                'raza' => 'Holstein',
                'fecha_nacimiento' => '2024-01-15',
                'fecha_ingreso' => '2025-01-15',
                'peso_inicial' => 450.50,
                'estado_productivo' => 'En producción',
                'produccion_minima_diaria' => 4.00,
                'estado' => 'Activo',
                'observaciones' => 'Animal de prueba API.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.arete_siniiga', 'MX-API-100')
            ->assertJsonPath('data.nombre', 'Lola');

        $this->assertDatabaseHas('ganado', [
            'lote_id' => $lote->id,
            'arete_siniiga' => 'MX-API-100',
            'nombre' => 'Lola',
        ]);
    }

    public function test_user_cannot_create_cattle_in_another_users_lot(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $foreignLote = $this->crearLote($otherUser);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/ganado', [
                'lote_id' => $foreignLote->id,
                'arete_siniiga' => 'MX-API-200',
                'nombre' => 'Intrusa',
                'sexo' => 'Hembra',
                'raza' => 'Holstein',
                'fecha_nacimiento' => '2024-01-15',
                'fecha_ingreso' => '2025-01-15',
                'peso_inicial' => 430,
                'estado_productivo' => 'En producción',
                'produccion_minima_diaria' => 4.00,
                'estado' => 'Activo',
                'observaciones' => null,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lote_id');

        $this->assertDatabaseMissing('ganado', [
            'arete_siniiga' => 'MX-API-200',
        ]);
    }

    public function test_owner_can_show_and_update_own_cattle(): void
    {
        $user = User::factory()->create();
        $lote = $this->crearLote($user);

        $animal = Ganado::factory()->create([
            'lote_id' => $lote->id,
            'arete_siniiga' => 'MX-API-300',
            'sexo' => 'Hembra',
            'fecha_ingreso' => '2025-01-15',
            'estado_productivo' => 'En producción',
            'produccion_minima_diaria' => 4.00,
            'estado' => 'Activo',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/ganado/{$animal->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $animal->id);

        $this->withToken($token)
            ->putJson("/api/ganado/{$animal->id}", [
                'lote_id' => $lote->id,
                'arete_siniiga' => 'MX-API-300',
                'nombre' => 'Lola Actualizada',
                'sexo' => 'Hembra',
                'raza' => 'Holstein',
                'fecha_nacimiento' => '2024-01-15',
                'fecha_ingreso' => '2025-01-15',
                'peso_inicial' => 470.00,
                'estado_productivo' => 'En producción',
                'produccion_minima_diaria' => 5.00,
                'estado' => 'Activo',
                'observaciones' => 'Actualizada mediante API.',
            ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Lola Actualizada')
            ->assertJsonPath('data.produccion_minima_diaria', '5.00');

        $this->assertDatabaseHas('ganado', [
            'id' => $animal->id,
            'nombre' => 'Lola Actualizada',
            'produccion_minima_diaria' => 5.00,
        ]);
    }

    public function test_user_cannot_access_another_users_cattle(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $foreignLote = $this->crearLote($otherUser);

        $animal = Ganado::factory()->create([
            'lote_id' => $foreignLote->id,
            'arete_siniiga' => 'MX-API-400',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/ganado/{$animal->id}")
            ->assertForbidden();

        $this->withToken($token)
            ->deleteJson("/api/ganado/{$animal->id}")
            ->assertForbidden();
    }

    public function test_destroy_marks_cattle_as_baja_instead_of_deleting_it(): void
    {
        $user = User::factory()->create();
        $lote = $this->crearLote($user);

        $animal = Ganado::factory()->create([
            'lote_id' => $lote->id,
            'estado' => 'Activo',
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/ganado/{$animal->id}")
            ->assertOk()
            ->assertJson([
                'message' => 'Animal dado de baja correctamente. Su historial se conserva.',
            ]);

        $this->assertDatabaseHas('ganado', [
            'id' => $animal->id,
            'estado' => 'Baja',
        ]);
    }

    public function test_guest_cannot_access_cattle_api(): void
    {
        $this->getJson('/api/ganado')
            ->assertUnauthorized();
    }
}