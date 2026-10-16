<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('ganado_id')
                ->nullable()
                ->constrained('ganado')
                ->nullOnDelete();

            $table->foreignId('lote_id')
                ->nullable()
                ->constrained('lotes')
                ->nullOnDelete();

            $table->string('titulo');

            $table->text('descripcion')
                ->nullable();

            $table->enum('tipo', [
                'ordenio',
                'vacunacion',
                'veterinaria',
                'produccion',
                'general'
            ])->default('general');

            $table->date('fecha');

            $table->time('hora')
                ->nullable();

            $table->enum('prioridad', [
                'baja',
                'media',
                'alta'
            ])->default('media');

            $table->enum('estado', [
                'pendiente',
                'completada',
                'cancelada'
            ])->default('pendiente');

            $table->boolean('recordatorio')
                ->default(true);

            $table->boolean('creada_por_ia')
                ->default(false);

            $table->timestamps();
        });
    }
};
