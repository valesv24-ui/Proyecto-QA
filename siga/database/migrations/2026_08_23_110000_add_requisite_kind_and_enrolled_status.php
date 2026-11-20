<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Tipo de requisito y estado Matriculado en el expediente. */
return new class extends Migration
{
    /** Estados del expediente, con Matriculado agregado al final. */
    private const RECORD_STATUSES = [
        'Aprobado',
        'Reprobado',
        'Acreditado por equiparación',
        'Acreditado por convalidación',
        'Requisito levantado',
        'Matriculado',
    ];

    public function up(): void
    {
        // Requisito o correquisito. Las mallas de ISW e ITI titulan la columna
        // "Requisitos" y las de FP "Requisitos y Co-requisitos", pero ninguna
        // marca cuál es cuál: la distinción se deriva de la distancia de
        // niveles (ver CoursePrerequisiteSeeder), no viene del documento.
        if (! Schema::hasColumn('prerequisites', 'kind')) {
            Schema::table('prerequisites', function (Blueprint $table) {
                $table->enum('kind', ['Requisito', 'Correquisito'])
                    ->default('Requisito')
                    ->after('target_course_id')
                    ->comment('Correquisito = el requisito es del nivel inmediato anterior');
            });
        }

        if (DB::getDriverName() !== 'mysql') {
            // SQLite (las pruebas) guarda el ENUM como VARCHAR con un CHECK en
            // línea que no se puede alterar. Se pasa a texto y la lista válida
            // la sostiene el Dominio, igual que en las migraciones anteriores.
            Schema::table('academic_records', function (Blueprint $table) {
                $table->string('status', 40)->change();
            });

            return;
        }

        $quoted = implode(',', array_map(
            static fn (string $value): string => "'".str_replace("'", "''", $value)."'",
            self::RECORD_STATUSES,
        ));

        DB::statement("ALTER TABLE academic_records MODIFY COLUMN status ENUM({$quoted}) NOT NULL");
    }

    public function down(): void
    {
        // Un curso matriculado todavía no se ganó ni se perdió: al quitar el
        // estado no hay a cuál devolverlo sin afirmar algo falso, así que las
        // filas se borran. Son matrículas en curso, no historia académica.
        DB::table('academic_records')->where('status', 'Matriculado')->delete();

        if (DB::getDriverName() === 'mysql') {
            $previous = array_slice(self::RECORD_STATUSES, 0, -1);
            $quoted = implode(',', array_map(
                static fn (string $value): string => "'".str_replace("'", "''", $value)."'",
                $previous,
            ));

            DB::statement("ALTER TABLE academic_records MODIFY COLUMN status ENUM({$quoted}) NOT NULL");
        }

        if (Schema::hasColumn('prerequisites', 'kind')) {
            Schema::table('prerequisites', function (Blueprint $table) {
                $table->dropColumn('kind');
            });
        }
    }
};
