<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** ES-01 alineado con la boleta oficial SLR-002 V.3 de Registro. */
return new class extends Migration
{
    /**
     * Las cinco justificaciones de la boleta, en su redacción literal.
     *
     * @var array<int, string>
     */
    private const JUSTIFICATIONS = [
        'El curso es el único pendiente para avanzar hacia el siguiente nivel del plan de estudios.',
        'El estudiante matricula su último nivel del pregrado o grado académico, siempre y cuando curse el requisito en forma paralela.',
        'El curso se ofertará dos o tres cuatrimestres después y constituye un requisito importante para el avance en el plan de estudios, lo que significa para el estudiante un atraso de dos cuatrimestres, un año o más en su carrera.',
        'Situación especial de carga académica mínima para cursar un cuatrimestre.',
        'El estudiante manifiesta formalmente que tiene conocimiento previo del requisito y va a optar por la suficiencia del mismo en este cuatrimestre.',
    ];

    private const SPECIAL_CASE = 'Solicitud de revisión manual por caso especial';

    /** TODOS los pasos comprueban antes de actuar. */
    public function up(): void
    {
        // Configuración sin equivalencia en el vocabulario nuevo.
        DB::table('waiver_rules')->delete();

        $this->dropMySqlCheck('waiver_rules', 'chk_waiver_rules_parameters');
        $this->dropMySqlCheck('waiver_rules', 'chk_waiver_rules_distinct_course');

        // La FK va aparte de la columna y ANTES.
        $this->dropForeignKeyIfExists('waiver_rules', 'prerequisite_course_id');
        $this->dropColumnsIfExist('waiver_rules', [
            'prerequisite_course_id',
            'minimum_grade',
            'minimum_accumulated',
        ]);

        $this->redefineTypeColumn([...self::JUSTIFICATIONS, self::SPECIAL_CASE]);

        Schema::table('student_requests', function (Blueprint $table) {
            // Nullable porque las Convalidaciones no llevan justificación.
            if (! Schema::hasColumn('student_requests', 'justification')) {
                $table->string('justification', 255)->nullable()
                    ->after('prerequisite_course_id')
                    ->comment('Justificación de la boleta SLR-002 elegida por el estudiante');
            }

            // Aceptación de las "Notas importantes" de la boleta.
            if (! Schema::hasColumn('student_requests', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable()
                    ->after('justification')
                    ->comment('Momento en que el estudiante aceptó las notas de la boleta SLR-002');
            }
        });
    }

    public function down(): void
    {
        // Mismo criterio de reversibilidad que up().
        $this->dropColumnsIfExist('student_requests', ['justification', 'terms_accepted_at']);

        DB::table('waiver_rules')->delete();

        $this->redefineTypeColumn([
            'Requisito aprobado con nota mínima',
            'Créditos o cursos acumulados',
            'Pertenencia a plan terminal',
            'Siempre revisión manual',
        ]);

        Schema::table('waiver_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('waiver_rules', 'prerequisite_course_id')) {
                $table->foreignId('prerequisite_course_id')->nullable()->after('type')
                    ->constrained('courses')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('waiver_rules', 'minimum_grade')) {
                $table->decimal('minimum_grade', 5, 2)->nullable()->after('prerequisite_course_id');
            }
            if (! Schema::hasColumn('waiver_rules', 'minimum_accumulated')) {
                $table->unsignedSmallInteger('minimum_accumulated')->nullable()->after('minimum_grade');
            }
        });
    }

    /**
     * @param  array<int, string>  $values
     */
    private function redefineTypeColumn(array $values): void
    {
        if (DB::getDriverName() !== 'mysql') {
            // SQLite (las pruebas) no tiene ENUM.
            Schema::table('waiver_rules', function (Blueprint $table) {
                $table->string('type', 255)->change();
            });

            return;
        }

        $quoted = implode(',', array_map(
            static fn (string $value): string => "'".str_replace("'", "''", $value)."'",
            $values,
        ));

        DB::statement("ALTER TABLE waiver_rules MODIFY COLUMN type ENUM({$quoted}) NOT NULL");
    }

    private function dropMySqlCheck(string $table, string $constraint): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS '
            .'WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $constraint],
        );

        if ($exists === null) {
            return;
        }

        DB::statement("ALTER TABLE {$table} DROP CHECK {$constraint}");
    }

    /** Elimina la clave foránea de una columna sin depender. */
    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        if (DB::getDriverName() !== 'mysql') {
            // SQLite (las pruebas) no tiene.
            if (Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                    $blueprint->dropForeign([$column]);
                });
            }

            return;
        }

        $constraint = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE '
            .'WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? '
            .'AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, $column],
        );

        if ($constraint === null) {
            return;
        }

        DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function dropColumnsIfExist(string $table, array $columns): void
    {
        $present = array_values(array_filter(
            $columns,
            static fn (string $column): bool => Schema::hasColumn($table, $column),
        ));

        if ($present === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($present): void {
            $blueprint->dropColumn($present);
        });
    }
};
