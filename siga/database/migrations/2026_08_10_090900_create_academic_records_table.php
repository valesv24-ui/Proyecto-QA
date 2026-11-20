<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Historial académico interno simplificado.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained('students')->cascadeOnDelete();
            $table->foreignId('course_id')
                ->constrained('courses')->restrictOnDelete();
            $table->foreignId('academic_term_id')->nullable()
                ->constrained('academic_terms')->nullOnDelete();
            $table->enum('status', [
                'Aprobado',
                'Reprobado',
                'Acreditado por equiparación',
                'Acreditado por convalidación',
                'Requisito levantado',
            ]);
            $table->decimal('grade', 5, 2)->nullable();
            $table->foreignId('course_equivalence_id')->nullable()
                ->comment('Resolución de referencia de la acreditación')
                ->constrained('course_equivalences')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'course_id'], 'academic_records_student_course_index');
            $table->index(['course_id', 'status'], 'academic_records_course_status_index');
        });

        // SQLite.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE academic_records ADD CONSTRAINT chk_academic_records_grade '
            .'CHECK (grade IS NULL OR (grade >= 0 AND grade <= 100))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_records');
    }
};
