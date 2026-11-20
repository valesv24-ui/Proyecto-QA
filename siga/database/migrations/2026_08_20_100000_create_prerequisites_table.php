<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Requisitos entre cursos. */
return new class extends Migration
{
    /** MySQL no revierte DDL. */
    private function createIfMissing(string $table, Closure $definition): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }

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
        $this->createIfMissing('prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')
                ->constrained('study_plans')->cascadeOnDelete();
            $table->foreignId('required_course_id')
                ->comment('Curso que debe aprobarse primero')
                ->constrained('courses')->cascadeOnDelete();
            $table->foreignId('target_course_id')
                ->comment('Curso que exige el requisito')
                ->constrained('courses')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(
                ['study_plan_id', 'required_course_id', 'target_course_id'],
                'prerequisites_plan_pair_unique',
            );
            // Los dos sentidos de la pregunta tienen su índice.
            $table->index('target_course_id', 'prerequisites_target_course_index');
            $table->index('required_course_id', 'prerequisites_required_course_index');
        });

        // SQLite.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Un curso no puede ser requisito de sí mismo.
        $this->addCheckIfMissing(
            'prerequisites',
            'chk_prerequisites_distinct',
            'required_course_id <> target_course_id',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('prerequisites');
    }
};
