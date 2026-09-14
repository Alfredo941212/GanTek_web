<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $version = (string) DB::getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
            if (str_contains($version, 'MariaDB') || version_compare($version, '8.0.16', '<')) {
                throw new RuntimeException('GanTek requiere MySQL 8.0.16 o superior para garantizar CHECK (litros > 0).');
            }
        }

        Schema::create('registros_ordenio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ganado_id')->constrained('ganado')->restrictOnDelete();
            $table->foreignId('lote_historico_id')->constrained('lotes')->restrictOnDelete();
            $table->date('fecha');
            $table->enum('turno', ['Mañana', 'Tarde']);
            $table->decimal('litros', 8, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['ganado_id', 'fecha', 'turno']);
            $table->index(['lote_historico_id', 'fecha']);
            $table->index('fecha');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE registros_ordenio ADD CONSTRAINT registros_ordenio_litros_positive CHECK (litros > 0)');
        } elseif (DB::getDriverName() === 'sqlite') {
            foreach (['INSERT', 'UPDATE'] as $operation) {
                $trigger = 'registros_ordenio_litros_'.strtolower($operation);
                DB::unprepared("CREATE TRIGGER {$trigger} BEFORE {$operation} ON registros_ordenio FOR EACH ROW WHEN NEW.litros <= 0 BEGIN SELECT RAISE(ABORT, 'Los litros deben ser mayores que cero'); END");
            }
        } else {
            throw new RuntimeException('Motor de base de datos no soportado por GanTek.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_ordenio');
    }
};
