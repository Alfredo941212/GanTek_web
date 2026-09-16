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

class GanaderiaAccessTest extends TestCase
{
    public function test_guests_cannot_access_the_domain(): void
    {
        foreach (['dashboard', 'fincas', 'lotes', 'ganado', 'veterinarios', 'vacunas', 'vacunaciones', 'ordenios', 'produccion', 'reportes'] as $path) {
            $this->get('/'.$path)->assertRedirect(route('login'));
            if (! in_array($path, ['dashboard', 'produccion', 'reportes'])) {
                $this->post('/'.$path, [])->assertRedirect(route('login'));
            }
        }
    }

    public function test_direct_access_cannot_read_update_or_delete_another_owners_records(): void
    {
        $animal = Ganado::factory()->create();
        $vacunacion = Vacunacion::factory()->create(['ganado_id' => $animal->id]);
        $ordenio = RegistroOrdenio::factory()->create(['ganado_id' => $animal->id]);
        $this->actingAs(User::factory()->create());

        foreach (['fincas' => $animal->lote->finca, 'lotes' => $animal->lote, 'ganado' => $animal, 'vacunaciones' => $vacunacion, 'ordenios' => $ordenio] as $resource => $registro) {
            $this->get(route($resource.'.edit', $registro))->assertForbidden();
            $this->putJson(route($resource.'.update', $registro), [])->assertForbidden();
            $this->deleteJson(route($resource.'.destroy', $registro))->assertForbidden();
            $this->assertModelExists($registro);
        }
        $this->get(route('ganado.show', $animal))->assertForbidden();
    }

    public function test_lists_and_dashboard_are_scoped_even_for_catalog_managers(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@gantek.test')->firstOrFail();
        $other = User::where('email', 'ganadero@gantek.test')->firstOrFail();
        $this->actingAs($user);
        foreach (['fincas' => Finca::class, 'lotes' => Lote::class, 'ganado' => Ganado::class, 'vacunaciones' => Vacunacion::class, 'ordenios' => RegistroOrdenio::class] as $resource => $model) {
            $expected = $model::forUser($user)->pluck('id');
            $this->get(route($resource.'.index'))->assertOk()->assertViewHas($resource, fn ($rows): bool => $rows->getCollection()->every(fn ($row): bool => $expected->contains($row->id)));
        }
        $foreign = Ganado::forUser($other)->firstOrFail();
        $this->get(route('dashboard'))->assertOk()->assertDontSee($foreign->arete_siniiga);
        $this->get(route('produccion.index'))->assertOk()->assertDontSee($foreign->arete_siniiga);
        $this->get(route('ganado.show', $foreign))->assertForbidden();
        $this->get(route('ganado.index', ['search' => $foreign->arete_siniiga]))->assertOk()->assertViewHas('ganado', fn ($rows): bool => $rows->isEmpty());
    }

    public function test_foreign_keys_and_report_filters_cannot_escape_ownership(): void
    {
        $mine = Ganado::factory()->create();
        $other = Ganado::factory()->create();
        $this->actingAs($mine->lote->finca->user);
        $this->postJson(route('fincas.store'), ['user_id' => $other->lote->finca->user_id])->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->postJson(route('lotes.store'), ['finca_id' => $other->lote->finca_id, 'nombre' => 'Intruso', 'estado' => 'Activo'])->assertUnprocessable()->assertJsonValidationErrors('finca_id');
        $payload = $mine->only(['lote_id', 'arete_siniiga', 'nombre', 'sexo', 'raza', 'peso_inicial', 'estado']);
        $payload['fecha_ingreso'] = $mine->fecha_ingreso->toDateString();
        $payload['lote_id'] = $other->lote_id;
        $this->putJson(route('ganado.update', $mine), $payload)->assertUnprocessable()->assertJsonValidationErrors('lote_id');
        $this->postJson(route('vacunaciones.store'), ['ganado_id' => $other->id])->assertUnprocessable()->assertJsonValidationErrors('ganado_id');
        $this->postJson(route('ordenios.store'), ['ganado_id' => $other->id, 'fecha' => today()->toDateString()])->assertUnprocessable()->assertJsonValidationErrors('ganado_id');
        $this->postJson(route('ordenios.store'), [
            'ganado_id' => $mine->id, 'fecha' => today()->subDay()->toDateString(), 'turno' => 'Mañana',
            'litros' => 5, 'lote_historico_id' => $other->lote_id,
        ])->assertUnprocessable()->assertJsonValidationErrors('lote_historico_id');
        foreach (['finca_id' => $other->lote->finca_id, 'lote_id' => $other->lote_id, 'ganado_id' => $other->id] as $filter => $id) {
            $this->getJson(route('produccion.index', [$filter => $id]))->assertUnprocessable()->assertJsonValidationErrors($filter);
        }
    }

    public function test_shared_catalogs_require_explicit_management_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach (['vacunas' => Vacuna::factory()->create(), 'veterinarios' => Veterinario::factory()->create()] as $resource => $registro) {
            $this->get(route($resource.'.index'))->assertOk()->assertSee($registro->nombre);
            $this->get(route($resource.'.create'))->assertForbidden();
            $this->postJson(route($resource.'.store'), [])->assertForbidden();
            $this->putJson(route($resource.'.update', $registro), [])->assertForbidden();
            $this->deleteJson(route($resource.'.destroy', $registro))->assertForbidden();
        }
        $user->fill(['puede_gestionar_catalogos' => true]);
        $this->assertFalse($user->puede_gestionar_catalogos);
    }
}
