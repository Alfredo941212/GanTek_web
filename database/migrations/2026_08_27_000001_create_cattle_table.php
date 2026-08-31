<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cattle', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->enum('sex', ['Macho', 'Hembra']);
            $table->string('breed')->nullable();
            $table->date('entry_date');
            $table->decimal('initial_weight', 8, 2)->nullable();
            $table->string('lot')->nullable();
            $table->string('corral')->nullable();
            $table->enum('status', ['Disponible', 'Vendido'])->default('Disponible');
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cattle');
    }
};
