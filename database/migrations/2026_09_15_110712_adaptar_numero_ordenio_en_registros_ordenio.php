<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('registros_ordenio')
            ->where('turno', 'Mañana')
            ->whereNull('numero_ordenio')
            ->update(['numero_ordenio' => 1]);

        DB::table('registros_ordenio')
            ->where('turno', 'Tarde')
            ->whereNull('numero_ordenio')
            ->update(['numero_ordenio' => 2]);

        // Primero se crea el nuevo índice para que ganado_id
        // siga teniendo un índice válido para su clave foránea.
        Schema::table('registros_ordenio', function (Blueprint $table) {
            $table->unique(
                ['ganado_id', 'fecha', 'numero_ordenio'],
                'registros_ordenio_ganado_fecha_numero_unique'
            );
        });

        // Después ya puede eliminarse el índice UNIQUE anterior.
        Schema::table('registros_ordenio', function (Blueprint $table) {
            $table->dropUnique(
                'registros_ordenio_ganado_id_fecha_turno_unique'
            );
        });
    }

    public function down(): void
    {
        // Restaurar primero el índice anterior.
        Schema::table('registros_ordenio', function (Blueprint $table) {
            $table->unique(
                ['ganado_id', 'fecha', 'turno'],
                'registros_ordenio_ganado_id_fecha_turno_unique'
            );
        });

        // Después retirar el nuevo.
        Schema::table('registros_ordenio', function (Blueprint $table) {
            $table->dropUnique(
                'registros_ordenio_ganado_fecha_numero_unique'
            );
        });
    }
};
