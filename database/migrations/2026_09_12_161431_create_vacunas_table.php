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
        Schema::create('vacunas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('fabricante', 150)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('dosis_recomendada', 100)->nullable();
            $table->unsignedInteger('intervalo_dias')->nullable();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps();

            $table->index(['estado', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacunas');
    }
};
