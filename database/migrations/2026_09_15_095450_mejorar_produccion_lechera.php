<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->decimal('produccion_minima_por_vaca', 8, 2)
                ->default(4.00)
                ->after('descripcion');
        });

        Schema::table('ganado', function (Blueprint $table) {
            $table->enum('estado_productivo', [
                'En producción',
                'Seca',
                'Gestante',
                'No aplica',
            ])
                ->default('En producción')
                ->after('peso_inicial');

            $table->decimal('produccion_minima_diaria', 8, 2)
                ->default(4.00)
                ->after('estado_productivo');
        });

        Schema::table('registros_ordenio', function (Blueprint $table) {
            $table->unsignedInteger('numero_ordenio')
                ->nullable()
                ->after('fecha');
        });
    }

    public function down(): void
    {
        Schema::table('registros_ordenio', function (Blueprint $table) {
            $table->dropColumn('numero_ordenio');
        });

        Schema::table('ganado', function (Blueprint $table) {
            $table->dropColumn([
                'estado_productivo',
                'produccion_minima_diaria',
            ]);
        });

        Schema::table('lotes', function (Blueprint $table) {
            $table->dropColumn('produccion_minima_por_vaca');
        });
    }
};