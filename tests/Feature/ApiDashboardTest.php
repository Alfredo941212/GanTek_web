<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Lote;
use App\Models\RegistroOrdenio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDashboardTest extends TestCase
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
            'produccion_minima_por_vaca' => 4,
        ]);

        return Ganado::factory()->create([
            'lote_id' => $lote->id,
            'sexo' => 'Hembra',
            'estado' => 'Activo',
            'estado_productivo' => 'En producción',
            'produccion_minima_diaria' => 4,
            'fecha_ingreso' => today()->subYear()->toDateString(),
        ]);
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'resumen' => [
                        'ganado_activo',
                        'litros_hoy',
                        'promedio_ultimos_7_dias',
                        'dias_con_produccion',
                        'alertas_produccion',
                        'alertas_vacas',
                        'alertas_lotes',
                        'vacunas_proximas',
                        'vacunas_vencidas',
                    ],
                    'animales_recientes',
                    'alertas' => [
                        'vacas',
                        'lotes',
                        'vacunas_proximas',
                        'vacunas_vencidas',
                    ],
                ],
            ]);
    }

    public function test_dashboard_counts_only_authenticated_users_cattle(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->crearAnimal($user);

        $this->crearAnimal($otherUser);
        $this->crearAnimal($otherUser);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.resumen.ganado_activo', 1);
    }

    public function test_dashboard_returns_todays_milk_production(): void
    {
        $user = User::factory()->create();
        $animal = $this->crearAnimal($user);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $animal->lote_id,
            'fecha' => today()->toDateString(),
            'numero_ordenio' => 1,
            'turno' => 'Mañana',
            'litros' => 3.5,
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $animal->lote_id,
            'fecha' => today()->toDateString(),
            'numero_ordenio' => 2,
            'turno' => 'Tarde',
            'litros' => 2.5,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.resumen.litros_hoy', 6);
    }

    public function test_dashboard_calculates_average_from_previous_seven_days(): void
    {
        $user = User::factory()->create();
        $animal = $this->crearAnimal($user);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $animal->lote_id,
            'fecha' => today()->subDays(2)->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 4,
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $animal->lote_id,
            'fecha' => today()->subDay()->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 8,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.resumen.promedio_ultimos_7_dias', 6)
            ->assertJsonPath('data.resumen.dias_con_produccion', 2);
    }

    public function test_dashboard_does_not_include_other_users_recent_animals(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownAnimal = $this->crearAnimal($user);
        $otherAnimal = $this->crearAnimal($otherUser);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/dashboard')
            ->assertOk();

        $response->assertJsonFragment([
            'id' => $ownAnimal->id,
        ]);

        $ids = collect($response->json('data.animales_recientes'))
            ->pluck('id');

        $this->assertFalse($ids->contains($otherAnimal->id));
    }

    public function test_dashboard_reports_low_production_alert(): void
    {
        $user = User::factory()->create();
        $animal = $this->crearAnimal($user);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $animal->lote_id,
            'fecha' => today()->subDay()->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 3,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.resumen.alertas_vacas', 1)
            ->assertJsonPath('data.resumen.alertas_lotes', 1)
            ->assertJsonPath('data.resumen.alertas_produccion', 2);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->getJson('/api/dashboard')
            ->assertUnauthorized();
    }
}