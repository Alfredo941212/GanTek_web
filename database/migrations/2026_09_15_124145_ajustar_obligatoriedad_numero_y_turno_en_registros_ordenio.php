<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registros_ordenio', function (Blueprint $table) {
            $table->unsignedInteger('numero_ordenio')
                ->nullable(false)
                ->change();

            $table->enum('turno', ['Mañana', 'Tarde'])
                ->nullable()
                ->change();
        });

        /*
         * SQLite reconstruye la tabla al usar change(),
         * por lo que los triggers personalizados que validan
         * litros > 0 pueden desaparecer.
         */
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('
                CREATE TRIGGER IF NOT EXISTS registros_ordenio_litros_insert
                BEFORE INSERT ON registros_ordenio
                FOR EACH ROW
                WHEN NEW.litros <= 0
                BEGIN
                    SELECT RAISE(ABORT, \'Los litros deben ser mayores que cero\');
                END
            ');

            DB::unprepared('
                CREATE TRIGGER IF NOT EXISTS registros_ordenio_litros_update
                BEFORE UPDATE ON registros_ordenio
                FOR EACH ROW
                WHEN NEW.litros <= 0
                BEGIN
                    SELECT RAISE(ABORT, \'Los litros deben ser mayores que cero\');
                END
            ');
        }
    }

    public function down(): void
    {
        Schema::table('registros_ordenio', function (Blueprint $table) {
            $table->unsignedInteger('numero_ordenio')
                ->nullable()
                ->change();

            $table->enum('turno', ['Mañana', 'Tarde'])
                ->nullable(false)
                ->change();
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('
                CREATE TRIGGER IF NOT EXISTS registros_ordenio_litros_insert
                BEFORE INSERT ON registros_ordenio
                FOR EACH ROW
                WHEN NEW.litros <= 0
                BEGIN
                    SELECT RAISE(ABORT, \'Los litros deben ser mayores que cero\');
                END
            ');

            DB::unprepared('
                CREATE TRIGGER IF NOT EXISTS registros_ordenio_litros_update
                BEFORE UPDATE ON registros_ordenio
                FOR EACH ROW
                WHEN NEW.litros <= 0
                BEGIN
                    SELECT RAISE(ABORT, \'Los litros deben ser mayores que cero\');
                END
            ');
        }
    }
};