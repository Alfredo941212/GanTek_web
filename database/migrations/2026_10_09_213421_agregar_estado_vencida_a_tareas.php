<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE tareas
            MODIFY estado ENUM(
                'pendiente',
                'completada',
                'cancelada',
                'vencida'
            ) NOT NULL DEFAULT 'pendiente'
        ");
    }

    public function down(): void
    {
        DB::table('tareas')
            ->where('estado', 'vencida')
            ->update([
                'estado' => 'pendiente',
            ]);

        DB::statement("
            ALTER TABLE tareas
            MODIFY estado ENUM(
                'pendiente',
                'completada',
                'cancelada'
            ) NOT NULL DEFAULT 'pendiente'
        ");
    }
};