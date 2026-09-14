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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProduccionAlertasTest extends TestCase
{
    #[DataProvider('productionThresholds')]
    public function test_production_alert_has_an_inclusive_twenty_percent_threshold(float $recent, bool $expected): void
    {
        $animal = Ganado::factory()->create();
        $this->history($animal, $recent);
        $alerts = app(AlertaService::class)->produccion($animal->lote->finca->user);
        $this->assertCount($expected ? 1 : 0, $alerts);
        if ($expected) {
            $this->assertEquals(10, $alerts->first()->referencia);
            $this->assertEquals($recent, $alerts->first()->reciente);
        }
    }

    /** @return array<string, array{float, bool}> */
    public static function productionThresholds(): array
    {
        return ['exactly 20 percent' => [8.0, true], 'less than 20 percent' => [8.02, false], '30 percent' => [7.0, true], 'stable' => [10.0, false]];
    }

    public function test_missing_shifts_suppress_alerts_and_today_is_excluded(): void
    {
        $animal = Ganado::factory()->create();
        $this->history($animal, 7);
        RegistroOrdenio::factory()->create(['ganado_id' => $animal->id, 'fecha' => today(), 'litros' => 1000]);
        $service = app(AlertaService::class);
        $user = $animal->lote->finca->user;
        $this->assertCount(1, $service->produccion($user));
        $animal->registrosOrdenio()->where('fecha', today()->subDays(5)->toDateString())->where('turno', 'Tarde')->delete();
        $this->assertCount(0, $service->produccion($user));
    }

    public function test_alerts_exclude_other_owners_and_inactive_animals(): void
    {
        $animal = Ganado::factory()->create();
        $this->history($animal, 7);
        $service = app(AlertaService::class);
        $this->assertCount(0, $service->produccion(User::factory()->create()));
        $animal->update(['estado' => 'Baja']);
        $this->assertCount(0, $service->produccion($animal->lote->finca->user));
    }

    public function test_only_latest_application_per_animal_and_vaccine_generates_reminders(): void
    {
        $old = Vacunacion::factory()->create(['fecha_aplicacion' => today()->subDays(50), 'proxima_aplicacion' => today()->subDay()]);
        $user = $old->ganado->lote->finca->user;
        $latest = Vacunacion::factory()->create([
            'ganado_id' => $old->ganado_id, 'vacuna_id' => $old->vacuna_id,
            'fecha_aplicacion' => today()->subDays(10), 'proxima_aplicacion' => today()->addDays(5),
        ]);
        $service = app(AlertaService::class);
        $this->assertSame([$latest->id], $service->proximas($user)->pluck('id')->all());
        $this->assertSame(0, $service->vencidas($user)->count());
        $this->assertSame(0, $service->proximas(User::factory()->create())->count());
        $tie = Vacunacion::factory()->create([
            'ganado_id' => $old->ganado_id, 'vacuna_id' => $old->vacuna_id,
            'fecha_aplicacion' => $latest->fecha_aplicacion, 'proxima_aplicacion' => null,
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
        RegistroOrdenio::factory()->create(['ganado_id' => $animal->id, 'fecha' => today()->subDay(), 'litros' => 5.2]);
        RegistroOrdenio::factory()->create(['ganado_id' => $animal->id, 'fecha' => today()->subDay(), 'turno' => 'Tarde', 'litros' => 4.1]);

        $this->actingAs($user)->put(route('ganado.update', $animal), [
            'lote_id' => $newLot->id, 'arete_siniiga' => $animal->arete_siniiga, 'sexo' => 'Hembra',
            'fecha_ingreso' => $animal->fecha_ingreso->toDateString(), 'estado' => 'Activo',
        ])->assertRedirect();
        $this->post(route('ordenios.store'), ['ganado_id' => $animal->id, 'fecha' => today()->toDateString(), 'turno' => 'Mañana', 'litros' => 6])->assertRedirect();
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
                ->assertViewHas('resumen', fn (array $data): bool => $data['total'] === (float) $total);
        }
        $this->get(route('produccion.index', ['periodo' => 'personalizado', 'desde' => '2026-08-01', 'hasta' => '2026-08-02']))
            ->assertOk()->assertViewHas('resumen', fn (array $data): bool => $data['total'] === 0.0 && $data['promedio'] === 0.0);
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

    private function history(Ganado $animal, float $recent): void
    {
        for ($day = 10; $day >= 1; $day--) {
            foreach (['Mañana', 'Tarde'] as $shift) {
                RegistroOrdenio::factory()->create([
                    'ganado_id' => $animal->id, 'fecha' => today()->subDays($day), 'turno' => $shift,
                    'litros' => ($day <= 3 ? $recent : 10) / 2,
                ]);
            }
        }
    }
}
