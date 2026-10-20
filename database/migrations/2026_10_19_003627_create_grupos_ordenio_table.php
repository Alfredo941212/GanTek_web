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
        Schema::create('grupos_ordenio', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('nombre');

            $table->unsignedInteger('orden');

            $table->enum('estado', [
                'Activo',
                'Inactivo',
            ])->default('Activo');

            $table->timestamps();

            $table->unique(
                ['user_id', 'nombre'],
                'grupos_ordenio_user_nombre_unique'
            );

            $table->unique(
                ['user_id', 'orden'],
                'grupos_ordenio_user_orden_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos_ordenio');
    }
};
