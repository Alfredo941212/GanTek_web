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
        Schema::create('veterinarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('cedula_profesional', 50)->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 254)->nullable();
            $table->string('especialidad', 150)->nullable();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['estado', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veterinarios');
    }
};
