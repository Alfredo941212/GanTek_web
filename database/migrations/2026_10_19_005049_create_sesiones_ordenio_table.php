<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sesiones_ordenio', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('grupo_ordenio_id')
                ->constrained('grupos_ordenio')
                ->restrictOnDelete();

            $table->date('fecha');

            $table->dateTime('hora_inicio')
                ->nullable();

            $table->dateTime('hora_fin')
                ->nullable();

            $table->unsignedInteger('duracion_minutos')
                ->nullable();

            $table->unsignedInteger('cantidad_vacas')
                ->default(0);

            $table->decimal('litros_totales', 10, 2)
                ->nullable();

            $table->enum('estado', [
                'en_espera',
                'en_produccion',
                'finalizado',
            ])->default('en_espera');

            $table->text('observaciones')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['grupo_ordenio_id', 'fecha'],
                'sesiones_ordenio_grupo_fecha_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesiones_ordenio');
    }
};
