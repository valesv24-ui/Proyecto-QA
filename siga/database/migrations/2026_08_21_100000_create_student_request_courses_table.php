<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Los cursos de una convalidación, uno por fila. */
return new class extends Migration
{
    /** MySQL no revierte DDL. */
    private function createIfMissing(string $table, Closure $definition): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }

    public function up(): void
    {
        $this->createIfMissing('student_request_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_request_id')
                ->constrained('student_requests')->cascadeOnDelete();
            $table->foreignId('course_id')
                ->comment('Curso de la UTN que se pide reconocer')
                ->constrained('courses')->restrictOnDelete();

            $table->string('external_institution', 150)
                ->comment('Universidad de procedencia de ESTE curso');
            $table->string('external_course_name', 150);
            $table->string('external_course_code', 30)->nullable()
                ->comment('Lo transcribe Registro de los documentos del estudiante');
            $table->unsignedTinyInteger('external_credits')->nullable()
                ->comment('Lo transcribe Registro de los documentos del estudiante');

            // "Pendiente" es un estado real y no una ausencia.
            $table->enum('outcome', ['Pendiente', 'Reconocido', 'No reconocido'])
                ->default('Pendiente');
            $table->string('denial_reason', 500)->nullable()
                ->comment('Obligatorio en el documento cuando el curso no se reconoce');

            $table->timestamps();

            // Un mismo curso de la UTN no se pide dos.
            $table->unique(['student_request_id', 'course_id'], 'student_request_courses_unique');
            $table->index(['student_request_id', 'outcome'], 'student_request_courses_outcome_index');
        });

        // Datos que el RSREC-001 imprime y que hoy no existían.
        if (! Schema::hasColumn('student_requests', 'resolution_number')) {
            Schema::table('student_requests', function (Blueprint $table) {
                $table->string('resolution_number', 60)->nullable()
                    ->after('reviewer_id')->comment('Resolución No. del RSREC-001');
                $table->string('session_number', 30)->nullable()
                    ->after('resolution_number')->comment('Sesión No. de la comisión');
                $table->string('minutes_number', 30)->nullable()
                    ->after('session_number')->comment('Acta No.');
                $table->date('session_date')->nullable()
                    ->after('minutes_number')->comment('Fecha de la sesión que resolvió');
            });
        }

        $this->backfillExistingTransfers();
    }

    public function down(): void
    {
        Schema::table('student_requests', function (Blueprint $table) {
            $table->dropColumn(['resolution_number', 'session_number', 'minutes_number', 'session_date']);
        });

        Schema::dropIfExists('student_request_courses');
    }

    /** Las convalidaciones que ya existen pasan a tener su fila. */
    private function backfillExistingTransfers(): void
    {
        $now = now();

        DB::table('student_requests')
            ->where('type', 'Convalidación')
            // Solo las que todavía no tienen fila.
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('student_request_courses')
                    ->whereColumn('student_request_courses.student_request_id', 'student_requests.id');
            })
            ->orderBy('id')
            ->chunkById(200, function ($requests) use ($now): void {
                $rows = [];

                foreach ($requests as $request) {
                    $rows[] = [
                        'student_request_id' => $request->id,
                        'course_id' => $request->course_id,
                        'external_institution' => $request->source_institution ?? 'No consta',
                        'external_course_name' => $request->external_course ?? 'No consta',
                        'external_course_code' => null,
                        'external_credits' => null,
                        'outcome' => match ($request->status) {
                            'Aprobada' => 'Reconocido',
                            'Denegada' => 'No reconocido',
                            default => 'Pendiente',
                        },
                        'denial_reason' => null,
                        'created_at' => $request->created_at ?? $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('student_request_courses')->insert($rows);
                }
            });
    }
};
