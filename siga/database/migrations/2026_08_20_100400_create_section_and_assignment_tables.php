<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Oferta académica: grupos, horarios. */
return new class extends Migration
{
    /** MySQL no revierte DDL. */
    private function createIfMissing(string $table, Closure $definition): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }

    /** Los CHECK también sobreviven a un fallo posterior. */
    private function addCheckIfMissing(string $table, string $name, string $expression): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->exists();

        if (! $exists) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function up(): void
    {
        $this->createIfMissing('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')->restrictOnDelete();
            $table->foreignId('academic_term_id')
                ->constrained('academic_terms')->restrictOnDelete();
            $table->foreignId('budget_target_id')
                ->constrained('budget_targets')->restrictOnDelete();
            $table->foreignId('classroom_id')->nullable()
                ->constrained('classrooms')->nullOnDelete();
            $table->foreignId('delivery_mode_id')
                ->constrained('delivery_modes')->restrictOnDelete();
            $table->unsignedTinyInteger('number')->comment('Número de grupo (1, 2, ...)');
            $table->unsignedSmallInteger('capacity');
            $table->unsignedSmallInteger('estimated_enrollment')->nullable()
                ->comment('Demanda esperada pre-matrícula');
            $table->unsignedSmallInteger('actual_enrollment')->nullable()
                ->comment('Se llena tras la matrícula; NULL = aún sin datos');
            $table->enum('status', [
                'Necesidad solicitada',
                'Borrador',
                'Enviado al CONTA',
                'Consolidado',
                'Enviado a RRHH',
                'Confirmado por RRHH',
                'Cerrado',
            ])->default('Borrador');
            $table->timestamps();

            $table->unique(['course_id', 'academic_term_id', 'number'], 'sections_course_term_number_unique');
            $table->index(['academic_term_id', 'status'], 'sections_term_status_index');
        });

        // Solo para grupos de servicio compartidos entre carreras.
        $this->createIfMissing('degree_program_section', function (Blueprint $table) {
            $table->foreignId('section_id')
                ->constrained('sections')->cascadeOnDelete();
            $table->foreignId('degree_program_id')
                ->constrained('degree_programs')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['section_id', 'degree_program_id']);
            $table->index('degree_program_id', 'degree_program_section_program_id_index');
        });

        $this->createIfMissing('section_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')
                ->constrained('sections')->cascadeOnDelete();
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['section_id', 'created_at'], 'section_status_history_section_date_index');
        });

        $this->createIfMissing('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')
                ->constrained('sections')->cascadeOnDelete();
            $table->enum('day_of_week', [
                'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo',
            ]);
            $table->time('start_time');
            $table->time('end_time');

            $table->unique(['section_id', 'day_of_week', 'start_time'], 'schedules_section_day_start_unique');
            $table->index(['day_of_week', 'start_time', 'end_time'], 'schedules_day_times_index');
        });

        $this->createIfMissing('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')
                ->constrained('sections')->cascadeOnDelete();
            $table->foreignId('instructor_id')
                ->constrained('instructors')->restrictOnDelete();
            $table->decimal('workload', 3, 2)->comment('Fracción de jornada, ej. 0.25');
            $table->enum('appointment_type', ['Interino', 'Propiedad'])->default('Interino');
            $table->string('pay_period', 20)->nullable();
            $table->string('personnel_action_number', 30)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->enum('status', ['Propuesta', 'Confirmada', 'Rechazada'])->default('Propuesta');
            $table->timestamps();

            $table->unique(['section_id', 'instructor_id'], 'teaching_assignments_section_instructor_unique');
            // Detección de docente duplicado entre carreras en el mismo período.
            $table->index(['instructor_id', 'status'], 'teaching_assignments_instructor_status_index');
            $table->index('status', 'teaching_assignments_status_index');
        });

        $this->createIfMissing('assignment_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')
                ->constrained('teaching_assignments')->cascadeOnDelete();
            $table->enum('change_type', ['Docente', 'Horario', 'Aula', 'Jornada', 'Estado', 'Otro']);
            $table->foreignId('previous_instructor_id')->nullable()
                ->constrained('instructors')->nullOnDelete();
            $table->foreignId('new_instructor_id')->nullable()
                ->constrained('instructors')->nullOnDelete();
            $table->string('memo_number', 30)->nullable()
                ->comment('Oficio/acuerdo que respalda el cambio');
            $table->string('description', 255);
            $table->foreignId('user_id')->nullable()
                ->comment('Usuario que registró el cambio')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['teaching_assignment_id', 'created_at'], 'assignment_changes_assignment_date_index');
            $table->index('memo_number', 'assignment_changes_memo_index');
        });

        $this->createIfMissing('suitability_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')
                ->constrained('teaching_assignments')->cascadeOnDelete();
            $table->foreignId('suitability_catalog_id')->nullable()
                ->comment('Versión de catálogo aplicada; NULL si el resultado es Sin catálogo')
                ->constrained('suitability_catalogs')->nullOnDelete();
            $table->foreignId('user_id')->nullable()
                ->comment('Usuario que ejecutó/aprobó la verificación')
                ->constrained('users')->nullOnDelete();
            $table->enum('result', ['Atinente', 'No Atinente', 'Nota técnica', 'Sin catálogo']);
            $table->boolean('is_provisional')->default(false)
                ->comment('1 = provisional por vigencia futura del catálogo');
            $table->text('justification')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['teaching_assignment_id', 'created_at'], 'suitability_checks_assignment_date_index');
            $table->index('result', 'suitability_checks_result_index');
        });

        $this->createIfMissing('technical_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')->unique()
                ->constrained('teaching_assignments')->cascadeOnDelete();
            $table->foreignId('attachment_id')
                ->comment('PDF del criterio técnico firmado (obligatorio)')
                ->constrained('attachments')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()
                ->comment('Coordinadora que registró la nota')
                ->constrained('users')->nullOnDelete();
            $table->date('ratification_deadline')->comment('SLA de ratificación');
            $table->enum('status', [
                'Ratificación pendiente',
                'Ratificada',
                'Vencida',
                'Rechazada',
            ])->default('Ratificación pendiente');
            $table->timestamp('ratified_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'ratification_deadline'], 'technical_notes_sla_index');
        });

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->addCheckIfMissing('sections', 'chk_sections_capacity', 'capacity > 0');

        $this->addCheckIfMissing('schedules', 'chk_schedules_time_range', 'end_time > start_time');

        $this->addCheckIfMissing(
            'teaching_assignments',
            'chk_teaching_assignments_workload',
            'workload > 0 AND workload <= 1',
        );

        // El esquema original lleva acá un CHECK que ata `result`.

        $this->addCheckIfMissing(
            'technical_notes',
            'chk_technical_notes_ratified_at',
            "(status = 'Ratificada' AND ratified_at IS NOT NULL) "
            ."OR (status <> 'Ratificada' AND ratified_at IS NULL)",
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_notes');
        Schema::dropIfExists('suitability_checks');
        Schema::dropIfExists('assignment_changes');
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('section_status_history');
        Schema::dropIfExists('degree_program_section');
        Schema::dropIfExists('sections');
    }
};
