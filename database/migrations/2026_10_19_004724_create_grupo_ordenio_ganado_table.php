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
        Schema::create('grupo_ordenio_ganado', function (Blueprint $table) {
            $table->id();

            $table->foreignId('grupo_ordenio_id')
                ->constrained('grupos_ordenio')
                ->cascadeOnDelete();

            $table->foreignId('ganado_id')
                ->constrained('ganado')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['grupo_ordenio_id', 'ganado_id'],
                'grupo_ordenio_ganado_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupo_ordenio_ganado');
    }
};
