<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UbicacionesTest extends TestCase
{
    /** @return list<string> */
    protected function migrationPaths(): array
    {
        return [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_09_07_053103_add_social_fields_to_users_table.php',
            'database/migrations/2026_09_12_161426_create_fincas_table.php',
            'database/migrations/2026_09_12_161456_add_catalog_management_permission_to_users_table.php',
            'database/migrations/2026_10_20_121413_create_ubicaciones_tables.php',
        ];
    }

    private function catalogo(): void
    {
        Finca::factory()->conUbicacionCatalogada()->make();
        DB::table('estados')->insert(['id' => 99, 'clave_inegi' => '02', 'nombre' => 'Otro estado de prueba']);
        DB::table('municipios')->insert(['id' => 99, 'estado_id' => 99, 'clave_inegi' => '02001', 'nombre' => 'Otro municipio']);
        DB::table('localidades')->insert(['municipio_id' => 99, 'clave_inegi' => '020010001', 'nombre' => 'Otra localidad']);
    }

    public function test_catalogs_filter_by_parent_and_paginate_search_results(): void
    {
        $this->catalogo();
        DB::table('localidades')->insert(['municipio_id' => DB::table('municipios')->where('clave_inegi', '01001')->value('id'), 'clave_inegi' => '010010002', 'nombre' => 'Localidad adicional']);

        $this->getJson('/api/ubicaciones/estados')->assertOk()->assertJsonPath('data.0.clave_inegi', '01')->assertJsonCount(2, 'data');
        $this->getJson('/api/ubicaciones/estados/01/municipios')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.clave_inegi', '01001');
        $this->getJson('/api/ubicaciones/municipios/01001/localidades?buscar=Localidad&por_pagina=1')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('total', 2)->assertJsonPath('data.0.clave_inegi', '010010002');
        $this->getJson('/api/ubicaciones/municipios/01001/localidades?seleccionada=020010001')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/ubicaciones/municipios/01001/localidades?buscar=adicional')->assertJsonPath('total', 1);
    }

    public function test_unknown_or_malformed_parents_return_404_and_invalid_queries_return_422(): void
    {
        $this->catalogo();

        $this->getJson('/api/ubicaciones/estados/99/municipios')->assertNotFound();
        $this->getJson('/api/ubicaciones/estados/1/municipios')->assertNotFound();
        $this->getJson('/api/ubicaciones/municipios/01999/localidades')->assertNotFound();
        $this->getJson('/api/ubicaciones/municipios/01001/localidades?por_pagina=101&page=0&buscar[]=x')->assertUnprocessable()->assertJsonValidationErrors(['por_pagina', 'page', 'buscar']);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function ubicacionesInvalidas(): array
    {
        return [
            'estado inexistente' => [['estado_clave' => '99'], 'estado_clave'],
            'municipio ajeno' => [['municipio_clave' => '02001'], 'municipio_clave'],
            'localidad ajena' => [['localidad_clave' => '020010001'], 'localidad_clave'],
            'clave sin ceros' => [['estado_clave' => '1'], 'estado_clave'],
            'estado manipulado' => [['estado_clave' => ['01']], 'estado_clave'],
            'municipio inexistente' => [['municipio_clave' => '01999'], 'municipio_clave'],
            'localidad inexistente' => [['localidad_clave' => '010019999'], 'localidad_clave'],
        ];
    }

    #[DataProvider('ubicacionesInvalidas')]
    public function test_invalid_geography_returns_422_without_creating_or_updating_a_farm(array $cambio, string $error): void
    {
        $this->catalogo();
        $finca = Finca::factory()->create();
        $this->actingAs($finca->user);
        $datos = array_replace(['nombre' => 'Intento inválido', 'estado_clave' => '01', 'municipio_clave' => '01001', 'localidad_clave' => '010010001'], $cambio);

        $this->postJson('/api/fincas', $datos)->assertUnprocessable()->assertJsonValidationErrors($error);
        $this->putJson('/api/fincas/'.$finca->id, $datos)->assertUnprocessable()->assertJsonValidationErrors($error);

        $this->assertDatabaseCount('fincas', 1);
        $this->assertDatabaseHas('fincas', ['id' => $finca->id, 'nombre' => $finca->nombre, 'estado_id' => null]);
    }

    public function test_valid_geography_creates_and_updates_farm_using_catalog_names(): void
    {
        $this->catalogo();
        $user = User::factory()->create();
        $this->actingAs($user);

        $respuesta = $this->postJson('/api/fincas', ['nombre' => 'Nueva finca', 'estado_clave' => '01', 'municipio_clave' => '01001', 'localidad_clave' => '010010001', 'estado' => 'Nombre manipulado', 'estado_id' => 99]);
        $respuesta->assertCreated()->assertJsonPath('data.estado', 'Estado de prueba')->assertJsonPath('data.localidad', 'Localidad de prueba');
        $id = $respuesta->json('data.id');
        $this->putJson('/api/fincas/'.$id, ['nombre' => 'Editada', 'estado_clave' => '02', 'municipio_clave' => '02001'])
            ->assertOk()->assertJsonPath('data.localidad', null)->assertJsonPath('data.estado', 'Otro estado de prueba');

        $this->assertDatabaseHas('fincas', ['id' => $id, 'user_id' => $user->id, 'nombre' => 'Editada', 'estado_id' => 99, 'municipio_id' => 99, 'localidad_id' => null]);
    }

    public function test_blade_errors_preserve_other_fields_and_optional_locality_can_be_omitted(): void
    {
        $this->catalogo();
        $this->actingAs(User::factory()->create());

        $this->from(route('fincas.create'))->post(route('fincas.store'), ['nombre' => 'Conservar nombre', 'superficie' => '12.50', 'estado_clave' => '01', 'municipio_clave' => '02001'])
            ->assertRedirect(route('fincas.create'))->assertSessionHasErrors(['municipio_clave' => 'El municipio seleccionado no pertenece al estado indicado.'])->assertSessionHasInput('nombre', 'Conservar nombre')->assertSessionHasInput('superficie', '12.50');
        $this->post(route('fincas.store'), ['nombre' => 'Sin localidad', 'estado_clave' => '01', 'municipio_clave' => '01001'])->assertRedirect(route('fincas.index'));
        $this->assertDatabaseHas('fincas', ['nombre' => 'Sin localidad', 'localidad_id' => null, 'localidad' => null]);
    }

    public function test_additive_migration_preserves_legacy_text_without_guessing_keys(): void
    {
        $migration = require database_path('migrations/2026_10_20_121413_create_ubicaciones_tables.php');
        $migration->down();
        $finca = Finca::factory()->create(['estado' => 'Texto histórico', 'municipio' => 'Nombre anterior', 'localidad' => 'Ubicación anterior']);

        $migration->up();

        $this->assertDatabaseHas('fincas', ['id' => $finca->id, 'estado' => 'Texto histórico', 'municipio' => 'Nombre anterior', 'localidad' => 'Ubicación anterior', 'estado_id' => null]);
        $this->actingAs($finca->user)->get(route('fincas.edit', $finca))->assertSee('Texto histórico')->assertSee('Confirma la ubicación');
    }

    /** @return array<string, mixed> */
    private function respuestasInegi(): array
    {
        return [
            'https://gaia.inegi.org.mx/wscatgeo/v2/mgee/' => Http::response(['datos' => array_map(fn (int $numero): array => ['cvegeo' => sprintf('%02d', $numero), 'nomgeo' => 'Estado de prueba '.$numero], range(1, 32)), 'numReg' => 32]),
            'https://gaia.inegi.org.mx/wscatgeo/v2/mgem/01' => Http::response(['datos' => [['cvegeo' => '01001', 'cve_ent' => '01', 'cve_mun' => '001', 'nomgeo' => 'Municipio de prueba']], 'numReg' => 1]),
            'https://gaia.inegi.org.mx/wscatgeo/v2/localidades/01001' => Http::response(['datos' => [['cvegeo' => '010010001', 'cve_ent' => '01', 'cve_mun' => '001', 'cve_loc' => '0001', 'nomgeo' => 'Localidad de prueba']], 'numReg' => 1]),
        ];
    }

    public function test_sync_is_idempotent_and_preserves_existing_farm_and_catalog_records(): void
    {
        $this->catalogo();
        $finca = Finca::factory()->create();
        Http::preventStrayRequests();
        Http::fake($this->respuestasInegi());

        $this->artisan('ubicaciones:sincronizar', ['--estado' => ['01']])->assertSuccessful();
        $this->artisan('ubicaciones:sincronizar', ['--estado' => ['01']])->assertSuccessful();

        $this->assertDatabaseCount('estados', 2);
        $this->assertDatabaseCount('municipios', 2);
        $this->assertDatabaseCount('localidades', 2);
        $this->assertDatabaseHas('estados', ['clave_inegi' => '01', 'nombre' => 'Estado de prueba 1']);
        $this->assertDatabaseHas('fincas', ['id' => $finca->id, 'estado' => $finca->estado, 'estado_id' => null]);
        Http::assertSentCount(6);
    }

    public function test_unavailable_inegi_preserves_all_local_data(): void
    {
        $this->catalogo();
        Http::preventStrayRequests();
        Http::fake(['https://gaia.inegi.org.mx/wscatgeo/v2/mgee/' => Http::response([], 503)]);

        $this->artisan('ubicaciones:sincronizar', ['--estado' => ['01']])->assertFailed();

        $this->assertDatabaseHas('estados', ['clave_inegi' => '01', 'nombre' => 'Estado de prueba']);
        $this->assertDatabaseCount('localidades', 2);
        Http::assertSentCount(3);
    }

    public function test_invalid_locality_response_does_not_partially_update_state(): void
    {
        $this->catalogo();
        Http::preventStrayRequests();
        Http::fake(array_replace($this->respuestasInegi(), [
            'https://gaia.inegi.org.mx/wscatgeo/v2/localidades/01001' => Http::response(['datos' => [['cvegeo' => '020010001', 'nomgeo' => 'Localidad ajena']], 'numReg' => 1]),
        ]));

        $this->artisan('ubicaciones:sincronizar', ['--estado' => ['01']])->assertFailed();

        $this->assertDatabaseHas('estados', ['clave_inegi' => '01', 'nombre' => 'Estado de prueba']);
        $this->assertDatabaseCount('localidades', 2);
        Http::assertSentCount(3);
    }

    public function test_catalog_rate_limit_returns_429(): void
    {
        for ($solicitud = 0; $solicitud < 60; $solicitud++) {
            $this->getJson('/api/ubicaciones/estados')->assertOk();
        }

        $this->getJson('/api/ubicaciones/estados')->assertTooManyRequests();
    }
}
