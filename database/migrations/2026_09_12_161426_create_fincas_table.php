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
        Schema::create('fincas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('nombre', 150);
            $table->string('municipio', 100);
            $table->string('localidad', 150)->nullable();
            $table->string('estado', 100);
            $table->decimal('superficie', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fincas');
    }
};
