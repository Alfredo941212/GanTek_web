<?php

namespace Tests\Feature;

use App\Models\Ganado;
use App\Models\Lote;
use App\Models\RegistroOrdenio;
use App\Models\Vacunacion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GanaderiaIntegrityTest extends TestCase
{
    #[DataProvider('invalidAmounts')]
    public function test_database_rejects_nonpositive_milk_without_form_validation(float $litros, bool $update): void
    {
        $registro = RegistroOrdenio::factory()->create();
        $this->expectException(QueryException::class);
        if ($update) {
            DB::table('registros_ordenio')->where('id', $registro->id)->update(['litros' => $litros]);
        } else {
            DB::table('registros_ordenio')->insert([
                'ganado_id' => $registro->ganado_id,
                'lote_historico_id' => $registro->lote_historico_id,
                'fecha' => today()->toDateString(),
                'numero_ordenio' => 2,
                'turno' => 'Tarde',
                'litros' => $litros,
            ]);
        }
    }

    /** @return array<string, array{float, bool}> */
    public static function invalidAmounts(): array
    {
        return ['insert zero' => [0.0, false], 'insert negative' => [-1.0, false], 'update zero' => [0.0, true], 'update negative' => [-1.0, true]];
    }

    public function test_database_rejects_duplicate_animal_date_and_milking_number(): void
    {
        $registro = RegistroOrdenio::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('registros_ordenio')->insert([
            'ganado_id' => $registro->ganado_id,
            'lote_historico_id' => $registro->lote_historico_id,
            'fecha' => $registro->getRawOriginal('fecha'),
            'numero_ordenio' => $registro->numero_ordenio,
            'turno' => 'Tarde',
            'litros' => 5,
        ]);
    }

    public function test_database_rejects_duplicate_siniiga(): void
    {
        $animal = Ganado::factory()->create();
        $this->expectException(QueryException::class);
        Ganado::factory()->create(['arete_siniiga' => $animal->arete_siniiga]);
    }

    public function test_database_protects_veterinarian_history(): void
    {
        $registro = Vacunacion::factory()->create();
        $this->expectException(QueryException::class);
        $registro->veterinario->delete();
    }

    public function test_database_protects_historical_lot_even_after_transfer(): void
    {
        $registro = RegistroOrdenio::factory()->create();
        $oldLot = $registro->loteHistorico;
        $newLot = Lote::factory()->create(['finca_id' => $oldLot->finca_id]);
        $registro->ganado->update(['lote_id' => $newLot->id]);
        $this->expectException(QueryException::class);
        $oldLot->delete();
    }

    public function test_form_rejects_invalid_amounts_duplicates_males_and_future_dates(): void
    {
        $registro = RegistroOrdenio::factory()->create();
        $this->actingAs($registro->ganado->lote->finca->user);
        $data = ['ganado_id' => $registro->ganado_id, 'fecha' => today()->toDateString(), 'numero_ordenio' => 2, 'turno' => 'Tarde', 'litros' => 5];
        foreach ([0, -1, 0.001, 1000000] as $amount) {
            $this->postJson(route('ordenios.store'), array_replace($data, ['litros' => $amount]))->assertUnprocessable()->assertJsonValidationErrors('litros');
        }
        $this->postJson(
            route('ordenios.store'),
            array_replace($data, ['numero_ordenio' => $registro->numero_ordenio])
        )->assertUnprocessable()->assertJsonValidationErrors('numero_ordenio');
        $this->postJson(route('ordenios.store'), array_replace($data, ['fecha' => today()->addDay()->toDateString()]))->assertUnprocessable()->assertJsonValidationErrors('fecha');
        $male = Ganado::factory()->create(['lote_id' => $registro->ganado->lote_id, 'sexo' => 'Macho']);
        $this->postJson(route('ordenios.store'), array_replace($data, ['ganado_id' => $male->id]))->assertUnprocessable()->assertJsonValidationErrors('ganado_id');
        $this->postJson(route('ordenios.store'), array_replace($data, ['turno' => 'Noche']))->assertUnprocessable()->assertJsonValidationErrors('turno');
    }
}
