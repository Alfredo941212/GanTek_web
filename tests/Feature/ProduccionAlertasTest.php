<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Lote;
use App\Models\RegistroOrdenio;
use App\Models\User;
use App\Models\Vacunacion;
use App\Services\AlertaService;
use App\Services\ProduccionService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class ProduccionAlertasTest extends TestCase
{
    public function test_cow_generates_alert_when_yesterday_production_is_below_its_minimum(): void
    {
        $animal = Ganado::factory()->create([
            'produccion_minima_diaria' => 4.00,
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'fecha' => today()->subDay(),
            'numero_ordenio' => 1,
            'turno' => 'Mañana',
            'litros' => 1.5,
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'fecha' => today()->subDay(),
            'numero_ordenio' => 2,
            'turno' => 'Tarde',
            'litros' => 2.0,
        ]);

        $alerts = app(AlertaService::class)
            ->produccion($animal->lote->finca->user);

        $this->assertCount(1, $alerts);
        $this->assertEquals(3.5, $alerts->first()->produccion);
        $this->assertEquals(4.0, $alerts->first()->produccion_minima_diaria);
    }

    public function test_cow_does_not_generate_alert_when_reaching_its_minimum(): void
    {
        $animal = Ganado::factory()->create([
            'produccion_minima_diaria' => 4.00,
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'fecha' => today()->subDay(),
            'numero_ordenio' => 1,
            'turno' => null,
            'litros' => 1.5,
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'fecha' => today()->subDay(),
            'numero_ordenio' => 2,
            'turno' => null,
            'litros' => 2.5,
        ]);

        $alerts = app(AlertaService::class)
            ->produccion($animal->lote->finca->user);

        $this->assertCount(0, $alerts);
    }

    public function test_alert_uses_all_milkings_and_excludes_today(): void
    {
        $animal = Ganado::factory()->create([
            'produccion_minima_diaria' => 4.00,
        ]);

        foreach ([1 => 1.0, 2 => 1.0, 3 => 1.0] as $numero => $litros) {
            RegistroOrdenio::factory()->create([
                'ganado_id' => $animal->id,
                'fecha' => today()->subDay(),
                'numero_ordenio' => $numero,
                'turno' => null,
                'litros' => $litros,
            ]);
        }

        // La producción de hoy no debe modificar la alerta de ayer.
        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'fecha' => today(),
            'numero_ordenio' => 1,
            'turno' => null,
            'litros' => 100,
        ]);

        $alerts = app(AlertaService::class)
            ->produccion($animal->lote->finca->user);

        $this->assertCount(1, $alerts);
        $this->assertEquals(3.0, $alerts->first()->produccion);
    }


    public function test_only_latest_application_per_animal_and_vaccine_generates_reminders(): void
    {
        $old = Vacunacion::factory()->create(['fecha_aplicacion' => today()->subDays(50), 'proxima_aplicacion' => today()->subDay()]);
        $user = $old->ganado->lote->finca->user;
        $latest = Vacunacion::factory()->create([
            'ganado_id' => $old->ganado_id,
            'vacuna_id' => $old->vacuna_id,
            'fecha_aplicacion' => today()->subDays(10),
            'proxima_aplicacion' => today()->addDays(5),
        ]);
        $service = app(AlertaService::class);
        $this->assertSame([$latest->id], $service->proximas($user)->pluck('id')->all());
        $this->assertSame(0, $service->vencidas($user)->count());
        $this->assertSame(0, $service->proximas(User::factory()->create())->count());
        $tie = Vacunacion::factory()->create([
            'ganado_id' => $old->ganado_id,
            'vacuna_id' => $old->vacuna_id,
            'fecha_aplicacion' => $latest->fecha_aplicacion,
            'proxima_aplicacion' => null,
        ]);
        $this->assertSame(0, $service->proximas($user)->count());
        $this->assertSame(0, $service->vencidas($user)->count());
        $tie->update(['proxima_aplicacion' => today()->subDay()]);
        $this->assertSame([$tie->id], $service->vencidas($user)->pluck('id')->all());
    }

    public function test_upcoming_window_includes_today_and_day_thirty_but_not_day_thirty_one(): void
    {
        $animal = Ganado::factory()->create();
        foreach ([0, 30, 31] as $days) {
            Vacunacion::factory()->create(['ganado_id' => $animal->id, 'proxima_aplicacion' => today()->addDays($days)]);
        }
        $this->assertSame(2, app(AlertaService::class)->proximas($animal->lote->finca->user)->count());
    }

    public function test_transfer_preserves_historical_farm_and_lot_totals(): void
    {
        $animal = Ganado::factory()->create();
        $user = $animal->lote->finca->user;
        $oldLot = $animal->lote;
        $newFarm = Finca::factory()->create(['user_id' => $user->id]);
        $newLot = Lote::factory()->create(['finca_id' => $newFarm->id]);
        RegistroOrdenio::factory()->create(['ganado_id' => $animal->id, 'fecha' => today()->subDay(), 'numero_ordenio' => 1, 'turno' => 'Mañana',  'litros' => 5.2]);
        RegistroOrdenio::factory()->create(['ganado_id' => $animal->id, 'fecha' => today()->subDay(), 'numero_ordenio' => 2, 'turno' => 'Tarde', 'litros' => 4.1]);

        $this->actingAs($user)->put(route('ganado.update', $animal), [
            'lote_id' => $newLot->id,
            'arete_siniiga' => $animal->arete_siniiga,
            'sexo' => 'Hembra',
            'fecha_ingreso' => $animal->fecha_ingreso->toDateString(),
            'estado_productivo' => 'En producción',
            'produccion_minima_diaria' => 4.00,
            'estado' => 'Activo',
        ])->assertRedirect();
        $this->post(route('ordenios.store'), ['ganado_id' => $animal->id, 'fecha' => today()->toDateString(), 'numero_ordenio' => 1, 'turno' => 'Mañana', 'litros' => 6])->assertRedirect();
        $service = app(ProduccionService::class);
        $this->assertEquals(9.3, $service->resumen($user, ['lote_id' => $oldLot->id])['total']);
        $this->assertEquals(9.3, $service->resumen($user, ['finca_id' => $oldLot->finca_id])['total']);
        $this->assertEquals(6, $service->resumen($user, ['finca_id' => $newFarm->id])['total']);
        $all = $service->resumen($user, ['ganado_id' => $animal->id]);
        $this->assertEquals(15.3, $all['total']);
        $this->assertEquals(7.65, $all['promedio']);
        $this->assertSame(2, $all['dias']);
    }

    public function test_calendar_filters_and_empty_reports(): void
    {
        $this->travelTo(Carbon::parse('2026-09-12 12:00:00'));
        $animal = Ganado::factory()->create();
        $this->actingAs($animal->lote->finca->user);
        foreach (['2026-09-12' => 5, '2026-09-08' => 10, '2026-09-01' => 20, '2026-08-31' => 30] as $fecha => $litros) {
            RegistroOrdenio::factory()->create(['ganado_id' => $animal->id, 'fecha' => $fecha, 'litros' => $litros]);
        }
        foreach (['diario' => 5, 'semanal' => 15, 'mensual' => 35] as $periodo => $total) {
            $this->get(route('produccion.index', ['periodo' => $periodo]))->assertOk()
                ->assertViewHas('resumen', fn(array $data): bool => $data['total'] === (float) $total);
        }
        $this->get(route('produccion.index', ['periodo' => 'personalizado', 'desde' => '2026-08-01', 'hasta' => '2026-08-02']))
            ->assertOk()->assertViewHas('resumen', fn(array $data): bool => $data['total'] === 0.0 && $data['promedio'] === 0.0);
    }

    public function test_seeders_provide_the_approved_dataset_and_demonstrable_alerts(): void
    {
        $this->seed(DatabaseSeeder::class);
        foreach (['users' => 2, 'fincas' => 4, 'lotes' => 8, 'ganado' => 24, 'veterinarios' => 3, 'vacunas' => 4, 'vacunaciones' => 48, 'registros_ordenio' => 464] as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertSame(16, Ganado::where('sexo', 'Hembra')->count());
        $this->assertSame(448, RegistroOrdenio::where('fecha', '<', today()->toDateString())->count());
        $user = User::where('email', 'admin@gantek.test')->firstOrFail();
        $this->assertCount(1, app(AlertaService::class)->produccion($user));
        $this->assertGreaterThan(0, app(AlertaService::class)->proximas($user)->count());
        $this->assertGreaterThan(0, app(AlertaService::class)->vencidas($user)->count());
    }

    public function test_alerts_exclude_other_owners_and_inactive_animals(): void
    {
        $animal = Ganado::factory()->create([
            'produccion_minima_diaria' => 4.00,
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $animal->id,
            'fecha' => today()->subDay(),
            'numero_ordenio' => 1,
            'turno' => null,
            'litros' => 3.0,
        ]);

        $service = app(AlertaService::class);

        $this->assertCount(
            0,
            $service->produccion(User::factory()->create())
        );

        $animal->update(['estado' => 'Baja']);

        $this->assertCount(
            0,
            $service->produccion($animal->lote->finca->user)
        );
    }

    public function test_lot_generates_alert_when_average_production_is_below_minimum(): void
    {
        $user = User::factory()->create();

        $finca = Finca::factory()->create([
            'user_id' => $user->id,
        ]);

        $lote = Lote::factory()->create([
            'finca_id' => $finca->id,
            'produccion_minima_por_vaca' => 4.00,
            'estado' => 'Activo',
        ]);

        $vaca1 = Ganado::factory()->create([
            'lote_id' => $lote->id,
            'sexo' => 'Hembra',
            'estado' => 'Activo',
            'estado_productivo' => 'En producción',
        ]);

        $vaca2 = Ganado::factory()->create([
            'lote_id' => $lote->id,
            'sexo' => 'Hembra',
            'estado' => 'Activo',
            'estado_productivo' => 'En producción',
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $vaca1->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->subDay(),
            'numero_ordenio' => 1,
            'turno' => null,
            'litros' => 3.00,
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $vaca2->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->subDay(),
            'numero_ordenio' => 1,
            'turno' => null,
            'litros' => 3.00,
        ]);

        $alertas = app(AlertaService::class)->produccionLotes($user);

        $this->assertCount(1, $alertas);

        $alerta = $alertas->first();

        $this->assertEquals($lote->id, $alerta->id);
        $this->assertEquals(2, $alerta->vacas_produccion);
        $this->assertEquals(6.00, $alerta->produccion);
        $this->assertEquals(3.00, $alerta->promedio_por_vaca);
        $this->assertEquals(8.00, $alerta->produccion_minima_esperada);
    }

    public function test_lot_does_not_generate_alert_when_reaching_minimum(): void
    {
        $user = User::factory()->create();

        $finca = Finca::factory()->create([
            'user_id' => $user->id,
        ]);

        $lote = Lote::factory()->create([
            'finca_id' => $finca->id,
            'produccion_minima_por_vaca' => 4.00,
            'estado' => 'Activo',
        ]);

        $vaca1 = Ganado::factory()->create([
            'lote_id' => $lote->id,
            'sexo' => 'Hembra',
            'estado' => 'Activo',
            'estado_productivo' => 'En producción',
        ]);

        $vaca2 = Ganado::factory()->create([
            'lote_id' => $lote->id,
            'sexo' => 'Hembra',
            'estado' => 'Activo',
            'estado_productivo' => 'En producción',
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $vaca1->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->subDay(),
            'numero_ordenio' => 1,
            'turno' => null,
            'litros' => 4.00,
        ]);

        RegistroOrdenio::factory()->create([
            'ganado_id' => $vaca2->id,
            'lote_historico_id' => $lote->id,
            'fecha' => today()->subDay(),
            'numero_ordenio' => 1,
            'turno' => null,
            'litros' => 4.00,
        ]);

        $alertas = app(AlertaService::class)->produccionLotes($user);

        $this->assertCount(0, $alertas);
    }
}
