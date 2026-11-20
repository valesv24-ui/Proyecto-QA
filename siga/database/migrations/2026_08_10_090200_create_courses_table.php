<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 4.9 Cursos.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('degree_program_id')->nullable()
                ->comment('NULL cuando es un curso de servicio transversal')
                ->constrained('degree_programs')->restrictOnDelete();
            $table->string('code', 30)->unique()->comment('Ej.: ITI-224, ITIEL-13');
            $table->string('name', 150);
            $table->boolean('is_service_course')->default(false)
                ->comment('1 = curso transversal administrado por Docencia');
            $table->boolean('is_bottleneck')->default(false)
                ->comment('1 = curso pinned: prioridad de horario y aula');
            $table->boolean('requires_lab')->default(false);
            $table->enum('lab_type', [
                'Laboratorio de cómputo',
                'Laboratorio de ciencias',
                'Laboratorio de idiomas',
            ])->nullable()->comment('Tipo de laboratorio requerido');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // SQLite.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE courses ADD CONSTRAINT chk_courses_service_program '
            .'CHECK (is_service_course = 1 OR degree_program_id IS NOT NULL)'
        );
        // Corrección: un curso que requiere laboratorio debe indicar qué tipo.
        DB::statement(
            'ALTER TABLE courses ADD CONSTRAINT chk_courses_lab_type '
            .'CHECK ((requires_lab = 1 AND lab_type IS NOT NULL) '
            .'OR (requires_lab = 0 AND lab_type IS NULL))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
