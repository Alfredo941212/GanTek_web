<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Lote;
use App\Models\RegistroOrdenio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiReporteProduccionTest extends TestCase
{
    use RefreshDatabase;

    private function crearEstructura(User $user): array
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

        $animal = Ganado::factory()->create([
            'lote_id' => $lote->id,
            'sexo' => 'Hembra',
            'estado' => 'Activo',
            'estado_productivo' => 'En producción',
            'produccion_minima_diaria' => 4,
            'fecha_ingreso' => today()->subYear()->toDateString(),
        ]);

        return compact('finca', 'lote', 'animal');
    }

    public function test_authenticated_user_can_access_production_report(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/reportes/produccion')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'periodo',
                    'filtros' => [
                        'desde',
                        'hasta',
                        'finca_id',
                        'lote_id',
                        'ganado_id',
                    ],
                    'indicadores' => [
                        'total_litros',
                        'promedio_diario',
                        'dias_con_registros',
                    ],
                    'produccion_diaria',
                    'produccion_por_animal',
                    'produccion_por_lote',
                    'produccion_por_finca',
                ],
            ]);
    }

    public function test_default_period_is_current_week(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/reportes/produccion')
            ->assertOk()
            ->assertJsonPath('data.periodo', 'semanal')
            ->assertJsonPath(
                'data.filtros.desde',
                today()->startOfWeek()->toDateString()
            )
            ->assertJsonPath(
                'data.filtros.hasta',
                today()->toDateString()
            );
    }

    public function test_monthly_report_calculates_production_correctly(): void
    {
        $user = User::factory()->create();

        ['lote' => $lote, 'animal' => $animal] =
            $this->crearEstructura($user);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->subDays(2)->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 4,
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->subDay()->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 8,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/reportes/produccion?periodo=mensual')
            ->assertOk()
            ->assertJsonPath('data.periodo', 'mensual')
            ->assertJsonPath('data.indicadores.total_litros', 12)
            ->assertJsonPath('data.indicadores.promedio_diario', 6)
            ->assertJsonPath('data.indicadores.dias_con_registros', 2);
    }

    public function test_custom_period_is_respected(): void
    {
        $user = User::factory()->create();

        ['lote' => $lote, 'animal' => $animal] =
            $this->crearEstructura($user);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->subDays(10)->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 5,
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->subDays(2)->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 9,
        ]);

        $desde = today()->subDays(3)->toDateString();
        $hasta = today()->toDateString();

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson(
                "/api/reportes/produccion?periodo=personalizado&desde={$desde}&hasta={$hasta}"
            )
            ->assertOk()
            ->assertJsonPath('data.periodo', 'personalizado')
            ->assertJsonPath('data.filtros.desde', $desde)
            ->assertJsonPath('data.filtros.hasta', $hasta)
            ->assertJsonPath('data.indicadores.total_litros', 9)
            ->assertJsonPath('data.indicadores.dias_con_registros', 1);
    }

    public function test_report_can_be_filtered_by_animal(): void
    {
        $user = User::factory()->create();

        ['lote' => $lote, 'animal' => $animal] =
            $this->crearEstructura($user);

        $otroAnimal = Ganado::factory()->create([
            'lote_id' => $lote->id,
            'sexo' => 'Hembra',
            'estado' => 'Activo',
            'estado_productivo' => 'En producción',
            'fecha_ingreso' => today()->subYear()->toDateString(),
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 4,
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $otroAnimal->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->toDateString(),
            'numero_ordenio' => 1,
            'litros' => 10,
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson(
                "/api/reportes/produccion?periodo=diario&ganado_id={$animal->id}"
            )
            ->assertOk()
            ->assertJsonPath('data.indicadores.total_litros', 4);
    }

    public function test_user_cannot_filter_report_with_another_users_farm(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ['finca' => $otherFarm] = $this->crearEstructura($otherUser);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson(
                "/api/reportes/produccion?periodo=mensual&finca_id={$otherFarm->id}"
            )
            ->assertUnprocessable();
    }

    public function test_user_cannot_filter_report_with_another_users_lot(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ['lote' => $otherLot] = $this->crearEstructura($otherUser);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson(
                "/api/reportes/produccion?periodo=mensual&lote_id={$otherLot->id}"
            )
            ->assertUnprocessable();
    }

    public function test_user_cannot_filter_report_with_another_users_animal(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ['animal' => $otherAnimal] =
            $this->crearEstructura($otherUser);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson(
                "/api/reportes/produccion?periodo=mensual&ganado_id={$otherAnimal->id}"
            )
            ->assertUnprocessable();
    }

    public function test_custom_period_requires_dates(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/reportes/produccion?periodo=personalizado')
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'desde',
                'hasta',
            ]);
    }

    public function test_end_date_cannot_be_before_start_date(): void
    {
        $user = User::factory()->create();

        $desde = today()->toDateString();
        $hasta = today()->subDay()->toDateString();

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson(
                "/api/reportes/produccion?periodo=personalizado&desde={$desde}&hasta={$hasta}"
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('hasta');
    }

    public function test_guest_cannot_access_production_report(): void
    {
        $this->getJson('/api/reportes/produccion')
            ->assertUnauthorized();
    }
}