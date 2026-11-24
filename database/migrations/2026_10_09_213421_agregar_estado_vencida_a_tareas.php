
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite no admite MODIFY ni ENUM.
        // En las pruebas, la columna existente se mantiene.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

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

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

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
