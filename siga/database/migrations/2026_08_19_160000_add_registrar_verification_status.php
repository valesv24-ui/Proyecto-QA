<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Agrega el estado "Verificada por Registro" al circuito de ES-03. */
return new class extends Migration
{
    /** @var array<int, string> */
    private const NEW_STATUSES = [
        'Pendiente de revisión',
        'Verificada por Registro',
        'En revisión',
        'Aprobada',
        'Denegada',
    ];

    /** @var array<int, string> */
    private const OLD_STATUSES = [
        'Pendiente de revisión',
        'En revisión',
        'Aprobada',
        'Denegada',
    ];

    public function up(): void
    {
        $this->redefineStatusColumn(self::NEW_STATUSES);
    }

    public function down(): void
    {
        // Las solicitudes que estén EN el estado nuevo tienen.
        DB::table('student_requests')
            ->where('status', 'Verificada por Registro')
            ->update(['status' => 'Pendiente de revisión']);

        $this->redefineStatusColumn(self::OLD_STATUSES);
    }

    /**
     * @param  array<int, string>  $values
     */
    private function redefineStatusColumn(array $values): void
    {
        if (DB::getDriverName() !== 'mysql') {
            // SQLite (las pruebas) representa el ENUM como VARCHAR.
            Schema::table('student_requests', function (Blueprint $table) {
                $table->string('status', 40)->change();
            });

            return;
        }

        $quoted = implode(',', array_map(
            static fn (string $value): string => "'".str_replace("'", "''", $value)."'",
            $values,
        ));

        DB::statement(
            "ALTER TABLE student_requests MODIFY COLUMN status ENUM({$quoted}) "
            ."NOT NULL DEFAULT 'Pendiente de revisión'"
        );
    }
};
