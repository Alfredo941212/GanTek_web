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
        Schema::create('ganado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained('lotes')->restrictOnDelete();
            $table->string('arete_siniiga', 50)->unique();
            $table->string('nombre', 100)->nullable();
            $table->enum('sexo', ['Macho', 'Hembra']);
            $table->string('raza', 100)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->date('fecha_ingreso');
            $table->decimal('peso_inicial', 8, 2)->nullable();
            $table->enum('estado', ['Activo', 'Vendido', 'Fallecido', 'Baja'])->default('Activo');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['lote_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ganado');
    }
};
