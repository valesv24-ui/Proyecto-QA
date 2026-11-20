<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** La nota con que el curso se aprobó. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('student_request_courses', 'external_grade')) {
            Schema::table('student_request_courses', function (Blueprint $table) {
                $table->decimal('external_grade', 5, 2)->nullable()
                    ->after('external_credits')
                    ->comment('Nota con que se aprobó en la institución de origen');
            });
        }

        // Mismo rango y misma forma que chk_academic_records_grade.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('CONSTRAINT_NAME', 'chk_student_request_courses_grade')
            ->exists();

        if (! $exists) {
            DB::statement(
                'ALTER TABLE student_request_courses ADD CONSTRAINT chk_student_request_courses_grade '
                .'CHECK (external_grade IS NULL OR (external_grade >= 0 AND external_grade <= 100))'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE student_request_courses DROP CONSTRAINT IF EXISTS chk_student_request_courses_grade');
        }

        if (Schema::hasColumn('student_request_courses', 'external_grade')) {
            Schema::table('student_request_courses', function (Blueprint $table) {
                $table->dropColumn('external_grade');
            });
        }
    }
};
