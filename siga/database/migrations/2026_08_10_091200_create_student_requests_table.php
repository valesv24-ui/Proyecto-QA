<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 7C.3 Solicitudes estudiantiles.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained('students')->cascadeOnDelete();
            $table->enum('type', ['Levantamiento de requisito', 'Convalidación']);
            $table->foreignId('course_id')
                ->comment('Curso a matricular / curso interno al que aspira')
                ->constrained('courses')->restrictOnDelete();
            // Sin nullOnDelete().
            $table->foreignId('prerequisite_course_id')->nullable()
                ->comment('Requisito no cumplido')
                ->constrained('courses')->restrictOnDelete();
            $table->string('source_institution', 150)->nullable()
                ->comment('Solo en solicitudes de Convalidación');
            $table->string('external_course', 150)->nullable()
                ->comment('Solo en solicitudes de Convalidación');
            $table->foreignId('transfer_credit_precedent_id')->nullable()
                ->comment('Precedente encontrado, si existe')
                ->constrained('transfer_credit_precedents')->nullOnDelete();
            $table->enum('engine_result', ['Procede automáticamente', 'No procede', 'Requiere revisión manual'])
                ->nullable()->comment('Primer resultado concluyente del motor de reglas');
            $table->foreignId('failed_rule_id')->nullable()
                ->comment('Regla que produjo el resultado No procede')
                ->constrained('waiver_rules')->nullOnDelete();
            $table->enum('status', ['Pendiente de revisión', 'En revisión', 'Aprobada', 'Denegada'])
                ->default('Pendiente de revisión');
            $table->date('estimated_resolution_date')->nullable()
                ->comment('Si no se ingresa en 24h la app asigna 5 días hábiles');
            $table->foreignId('reviewer_id')->nullable()
                ->comment('Usuario revisor (Docencia/Comisión)')
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'status', 'created_at'], 'student_requests_inbox_index');
            $table->index(['student_id', 'status'], 'student_requests_student_index');
        });

        // Corrección: cada tipo de solicitud debe llevar sus propios campos.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE student_requests ADD CONSTRAINT chk_student_requests_transfer_fields '
            ."CHECK (type <> 'Convalidación' "
            .'OR (source_institution IS NOT NULL AND external_course IS NOT NULL))'
        );
        DB::statement(
            'ALTER TABLE student_requests ADD CONSTRAINT chk_student_requests_waiver_fields '
            ."CHECK (type <> 'Levantamiento de requisito' OR prerequisite_course_id IS NOT NULL)"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('student_requests');
    }
};
