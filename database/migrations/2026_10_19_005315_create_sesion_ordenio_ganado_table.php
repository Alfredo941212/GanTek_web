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
        Schema::create('sesion_ordenio_ganado', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sesion_ordenio_id')
                ->constrained('sesiones_ordenio')
                ->cascadeOnDelete();

            $table->foreignId('ganado_id')
                ->constrained('ganado')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(
                ['sesion_ordenio_id', 'ganado_id'],
                'sesion_ordenio_ganado_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesion_ordenio_ganado');
    }
};
