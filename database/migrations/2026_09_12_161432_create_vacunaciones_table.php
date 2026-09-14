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
        Schema::create('vacunaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ganado_id')->constrained('ganado')->restrictOnDelete();
            $table->foreignId('veterinario_id')->constrained('veterinarios')->restrictOnDelete();
            $table->foreignId('vacuna_id')->constrained('vacunas')->restrictOnDelete();
            $table->date('fecha_aplicacion');
            $table->date('proxima_aplicacion')->nullable();
            $table->string('dosis', 100);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['ganado_id', 'vacuna_id', 'fecha_aplicacion', 'id'], 'vacunaciones_historial_index');
            $table->index('proxima_aplicacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacunaciones');
    }
};
