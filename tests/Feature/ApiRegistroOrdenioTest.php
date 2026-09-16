<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Lote;
use App\Models\RegistroOrdenio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRegistroOrdenioTest extends TestCase
{
    use RefreshDatabase;

    private function crearAnimal(User $user): array
    {
        $finca = Finca::factory()->create([
            'user_id' => $user->id,
        ]);

        $lote = Lote::factory()->create([
            'finca_id' => $finca->id,
            'estado' => 'Activo',
            'produccion_minima_por_vaca' => 4.00,
        ]);

        $animal = Ganado::factory()->create([
            'lote_id' => $lote->id,
            'sexo' => 'Hembra',
            'estado' => 'Activo',
            'estado_productivo' => 'En producción',
            'produccion_minima_diaria' => 4.00,
            'fecha_ingreso' => today()->subYear()->toDateString(),
        ]);

        return [$lote, $animal];
    }

    public function test_authenticated_user_can_list_only_own_milking_records(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        [$ownLote, $ownAnimal] = $this->crearAnimal($user);
        [$foreignLote, $foreignAnimal] = $this->crearAnimal($otherUser);

        $ownOrdenio = RegistroOrdenio::factory()->create([
            'ganado_id' => $ownAnimal->id,
            'lote_historico_id' => $ownLote->id,
            'fecha' => today()->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 4.50,
        ]);

        $foreignOrdenio = RegistroOrdenio::factory()->create([
            'ganado_id' => $foreignAnimal->id,
            'lote_historico_id' => $foreignLote->id,
            'fecha' => today()->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 6.00,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/ordenios')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ownOrdenio->id,
            ])
            ->assertJsonMissing([
                'id' => $foreignOrdenio->id,
            ]);
    }

    public function test_user_can_register_milking_for_own_active_female(): void
    {
        $user = User::factory()->create();
        [$lote, $animal] = $this->crearAnimal($user);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/ordenios', [
                'ganado_id' => $animal->id,
                'fecha' => today()->toDateString(),
                'numero_ordenio' => 1,
                'turno' => null,
                'litros' => 4.75,
                'observaciones' => 'Registro desde API.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.ganado_id', $animal->id)
            ->assertJsonPath('data.lote_historico_id', $lote->id)
            ->assertJsonPath('data.numero_ordenio', 1)
            ->assertJsonPath('data.litros', '4.75');

        $this->assertDatabaseHas('registros_ordenio', [
            'ganado_id' => $animal->id,
            'lote_historico_id' => $lote->id,
            'numero_ordenio' => 1,
        ]);
    }

    public function test_duplicate_milking_number_for_same_animal_and_date_is_rejected(): void
    {
        $user = User::factory()->create();
        [$lote, $animal] = $this->crearAnimal($user);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 4.00,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/ordenios', [
                'ganado_id' => $animal->id,
                'fecha' => today()->toDateString(),
                'numero_ordenio' => 1,
                'turno' => null,
                'litros' => 5.00,
                'observaciones' => null,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('numero_ordenio');

        $this->assertDatabaseCount('registros_ordenio', 1);
    }

    public function test_user_cannot_register_milking_for_another_users_animal(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        [, $foreignAnimal] = $this->crearAnimal($otherUser);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/ordenios', [
                'ganado_id' => $foreignAnimal->id,
                'fecha' => today()->toDateString(),
                'numero_ordenio' => 1,
                'turno' => null,
                'litros' => 4.00,
                'observaciones' => null,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ganado_id');
    }

    public function test_owner_can_show_and_correct_liters_without_changing_history(): void
    {
        $user = User::factory()->create();
        [$lote, $animal] = $this->crearAnimal($user);

        $ordenio = RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->subDay()->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 3.50,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/ordenios/{$ordenio->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $ordenio->id);

        $this->withToken($token)
            ->putJson("/api/ordenios/{$ordenio->id}", [
                'litros' => 4.25,
                'observaciones' => 'Cantidad corregida.',
            ])
            ->assertOk()
            ->assertJsonPath('data.litros', '4.25');

        $this->assertDatabaseHas('registros_ordenio', [
            'id' => $ordenio->id,
            'ganado_id' => $animal->id,
            'lote_historico_id' => $lote->id,
            'numero_ordenio' => 1,
            'litros' => 4.25,
        ]);
    }

    public function test_update_rejects_changes_to_historical_fields(): void
    {
        $user = User::factory()->create();
        [$lote, $animal] = $this->crearAnimal($user);

        $ordenio = RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 4.00,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/ordenios/{$ordenio->id}", [
                'litros' => 5.00,
                'observaciones' => null,
                'numero_ordenio' => 2,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('numero_ordenio');

        $this->assertDatabaseHas('registros_ordenio', [
            'id' => $ordenio->id,
            'numero_ordenio' => 1,
            'litros' => 4.00,
        ]);
    }

    public function test_user_cannot_access_another_users_milking_record(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        [$foreignLote, $foreignAnimal] = $this->crearAnimal($otherUser);

        $ordenio = RegistroOrdenio::factory()->create([
            'ganado_id' => $foreignAnimal->id,
            'lote_historico_id' => $foreignLote->id,
            'fecha' => today()->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 4.00,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/ordenios/{$ordenio->id}")
            ->assertForbidden();

        $this->withToken($token)
            ->deleteJson("/api/ordenios/{$ordenio->id}")
            ->assertForbidden();
    }

    public function test_owner_can_delete_own_milking_record(): void
    {
        $user = User::factory()->create();
        [$lote, $animal] = $this->crearAnimal($user);

        $ordenio = RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 4.00,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/ordenios/{$ordenio->id}")
            ->assertOk();

        $this->assertDatabaseMissing('registros_ordenio', [
            'id' => $ordenio->id,
        ]);
    }

    public function test_guest_cannot_access_milking_api(): void
    {
        $this->getJson('/api/ordenios')
            ->assertUnauthorized();
    }
}