<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados', function (Blueprint $table): void {
            $table->id();
            $table->string('clave_inegi', 2)->unique();
            $table->string('nombre', 150)->index();
        });
        Schema::create('municipios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('estado_id')->constrained()->restrictOnDelete();
            $table->string('clave_inegi', 5)->unique();
            $table->string('nombre', 150);
            $table->index(['estado_id', 'nombre']);
        });
        Schema::create('localidades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('municipio_id')->constrained()->restrictOnDelete();
            $table->string('clave_inegi', 9)->unique();
            $table->string('nombre', 150);
            $table->index(['municipio_id', 'nombre']);
        });
        Schema::table('fincas', function (Blueprint $table): void {
            $table->foreignId('estado_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('municipio_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('localidad_id')->nullable()->constrained('localidades')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fincas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('localidad_id');
            $table->dropConstrainedForeignId('municipio_id');
            $table->dropConstrainedForeignId('estado_id');
        });
        Schema::dropIfExists('localidades');
        Schema::dropIfExists('municipios');
        Schema::dropIfExists('estados');
    }
};
