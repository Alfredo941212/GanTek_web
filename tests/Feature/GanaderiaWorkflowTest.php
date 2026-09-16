<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Lote;
use App\Models\RegistroOrdenio;
use App\Models\User;
use App\Models\Vacuna;
use App\Models\Vacunacion;
use App\Models\Veterinario;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class GanaderiaWorkflowTest extends TestCase
{
    public function test_owner_can_complete_the_main_workflow(): void
    {
        $user = User::factory()->create(['puede_gestionar_catalogos' => true]);
        $this->actingAs($user);
        $this->post(route('fincas.store'), ['nombre' => 'Mi finca', 'municipio' => 'Prueba', 'estado' => 'Veracruz'])->assertRedirect(route('fincas.index'));
        $finca = Finca::firstOrFail();
        $this->assertSame($user->id, $finca->user_id);
        $this->post(route('lotes.store'), ['finca_id' => $finca->id, 'nombre' => 'Mi lote', 'estado' => 'Activo'])->assertRedirect(route('lotes.index'));
        $lote = Lote::firstOrFail();
        $this->post(route('ganado.store'), [
            'lote_id' => $lote->id, 'arete_siniiga' => 'DEMO-001', 'sexo' => 'Hembra',
            'fecha_ingreso' => today()->subDays(30)->toDateString(), 'estado' => 'Activo',
        ])->assertRedirect(route('ganado.index'));
        $animal = Ganado::firstOrFail();
        $this->post(route('veterinarios.store'), ['nombre' => 'Dra. Demo', 'cedula_profesional' => 'DEMO-01', 'estado' => 'Activo'])->assertRedirect(route('veterinarios.index'));
        $this->post(route('vacunas.store'), ['nombre' => 'Vacuna demo', 'estado' => 'Activo'])->assertRedirect(route('vacunas.index'));
        $this->post(route('vacunaciones.store'), [
            'ganado_id' => $animal->id, 'veterinario_id' => Veterinario::firstOrFail()->id, 'vacuna_id' => Vacuna::firstOrFail()->id,
            'fecha_aplicacion' => today()->toDateString(), 'proxima_aplicacion' => today()->addDays(5)->toDateString(), 'dosis' => '2 ml',
        ])->assertRedirect(route('vacunaciones.index'));
        foreach (['Mañana' => 5.2, 'Tarde' => 4.1] as $turno => $litros) {
            $this->post(route('ordenios.store'), ['ganado_id' => $animal->id, 'fecha' => today()->toDateString(), 'turno' => $turno, 'litros' => $litros])->assertRedirect(route('ordenios.index'));
        }
        $this->assertEquals(9.3, RegistroOrdenio::sum('litros'));
        $this->assertSame($lote->id, RegistroOrdenio::firstOrFail()->lote_historico_id);
        $this->get(route('produccion.index', ['periodo' => 'diario']))->assertOk()->assertSee('9.30');
        $this->get(route('ganado.show', $animal))->assertOk()->assertSee('Dra. Demo')->assertSee('Vacuna demo');
        $this->delete(route('ganado.destroy', $animal))->assertRedirect();
        $this->assertSame('Baja', $animal->fresh()->estado);
        $this->assertDatabaseCount('registros_ordenio', 2);
        $this->assertDatabaseCount('vacunaciones', 1);
    }

    public function test_all_forms_render_with_seeded_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@gantek.test')->firstOrFail();
        $this->actingAs($user);
        foreach (['fincas' => Finca::class, 'lotes' => Lote::class, 'ganado' => Ganado::class, 'vacunaciones' => Vacunacion::class, 'ordenios' => RegistroOrdenio::class] as $resource => $model) {
            $this->get(route($resource.'.create'))->assertOk();
            $this->get(route($resource.'.edit', $model::forUser($user)->firstOrFail()))->assertOk();
        }
        foreach (['vacunas' => Vacuna::class, 'veterinarios' => Veterinario::class] as $resource => $model) {
            $this->get(route($resource.'.index'))->assertOk();
            $this->get(route($resource.'.create'))->assertOk();
            $this->get(route($resource.'.edit', $model::firstOrFail()))->assertOk();
        }
        $this->get(route('reportes.index'))->assertOk();
        $this->get('/ventas')->assertNotFound();
    }

    public function test_milk_metadata_and_parent_ownership_are_immutable(): void
    {
        $registro = RegistroOrdenio::factory()->create();
        $this->actingAs($registro->ganado->lote->finca->user);
        $this->putJson(route('ordenios.update', $registro), ['litros' => 6.2, 'lote_historico_id' => $registro->lote_historico_id])->assertUnprocessable()->assertJsonValidationErrors('lote_historico_id');
        $this->put(route('ordenios.update', $registro), ['litros' => 6.2, 'observaciones' => 'Corrección'])->assertRedirect();
        $this->assertSame('6.20', $registro->fresh()->litros);
        $lote = $registro->ganado->lote;
        $this->putJson(route('lotes.update', $lote), ['finca_id' => $lote->finca_id, 'nombre' => 'Otro', 'estado' => 'Activo'])->assertUnprocessable()->assertJsonValidationErrors('finca_id');
        $this->deleteJson(route('lotes.destroy', $lote))->assertUnprocessable();
        $this->deleteJson(route('fincas.destroy', $lote->finca))->assertUnprocessable();
    }

    public function test_backdated_milk_requires_an_explicit_owned_historical_lot(): void
    {
        $animal = Ganado::factory()->create();
        $this->actingAs($animal->lote->finca->user);
        $data = ['ganado_id' => $animal->id, 'fecha' => today()->subDay()->toDateString(), 'turno' => 'Mañana', 'litros' => 5];
        $this->postJson(route('ordenios.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('lote_historico_id');
        $this->post(route('ordenios.store'), $data + ['lote_historico_id' => $animal->lote_id])->assertRedirect();
        $this->assertDatabaseCount('registros_ordenio', 1);
    }

    public function test_empty_values_cannot_clear_immutable_fields(): void
    {
        $registro = RegistroOrdenio::factory()->create();
        $this->actingAs($registro->ganado->lote->finca->user);
        foreach (['ganado_id', 'lote_historico_id', 'fecha', 'turno'] as $field) {
            $this->putJson(route('ordenios.update', $registro), ['litros' => 5, $field => null])
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $lote = $registro->ganado->lote;
        $this->putJson(route('lotes.update', $lote), ['nombre' => 'Nuevo nombre', 'estado' => 'Activo', 'finca_id' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('finca_id');
        $vacunacion = Vacunacion::factory()->create(['ganado_id' => $registro->ganado_id]);
        $this->putJson(route('vacunaciones.update', $vacunacion), ['ganado_id' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('ganado_id');
    }

    public function test_same_day_capture_can_identify_the_lot_before_a_transfer(): void
    {
        $animal = Ganado::factory()->create();
        $oldLot = $animal->lote;
        $newLot = Lote::factory()->create(['finca_id' => $oldLot->finca_id]);
        $this->actingAs($oldLot->finca->user);
        $animal->update(['lote_id' => $newLot->id]);
        $this->post(route('ordenios.store'), [
            'ganado_id' => $animal->id, 'fecha' => today()->toDateString(), 'turno' => 'Mañana',
            'litros' => 5, 'lote_historico_id' => $oldLot->id,
        ])->assertRedirect(route('ordenios.index'));
        $this->post(route('ordenios.store'), [
            'ganado_id' => $animal->id, 'fecha' => today()->toDateString(), 'turno' => 'Tarde', 'litros' => 4,
        ])->assertRedirect(route('ordenios.index'));
        $this->assertSame($oldLot->id, RegistroOrdenio::where('turno', 'Mañana')->firstOrFail()->lote_historico_id);
        $this->assertSame($newLot->id, RegistroOrdenio::where('turno', 'Tarde')->firstOrFail()->lote_historico_id);
    }

    public function test_inactive_catalog_entries_cannot_be_used_for_new_applications_but_history_remains(): void
    {
        $vacunacion = Vacunacion::factory()->create();
        $user = $vacunacion->ganado->lote->finca->user;
        $user->puede_gestionar_catalogos = true;
        $user->save();
        $this->actingAs($user);
        $this->delete(route('veterinarios.destroy', $vacunacion->veterinario))->assertRedirect();
        $this->delete(route('vacunas.destroy', $vacunacion->vacuna))->assertRedirect();
        $this->assertModelExists($vacunacion);
        $this->get(route('ganado.show', $vacunacion->ganado))->assertOk();
        $this->postJson(route('vacunaciones.store'), [
            'ganado_id' => $vacunacion->ganado_id, 'veterinario_id' => $vacunacion->veterinario_id,
            'vacuna_id' => $vacunacion->vacuna_id, 'fecha_aplicacion' => today()->toDateString(), 'dosis' => '2 ml',
        ])->assertUnprocessable()->assertJsonValidationErrors(['veterinario_id', 'vacuna_id']);
    }
}
