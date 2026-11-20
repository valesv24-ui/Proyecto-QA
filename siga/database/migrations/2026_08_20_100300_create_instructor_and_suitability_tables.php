<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Docentes, atestados y catálogos de atinencia. */
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
        $this->createIfMissing('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180)->unique();
            $table->timestamps();
        });

        $this->createIfMissing('suitability_catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('agreement', 120)
                ->comment('Acuerdo del Consejo Universitario (obligatorio)');
            $table->string('gazette_number', 60)
                ->comment('Número de La Gaceta (obligatorio)');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'version'], 'suitability_catalogs_course_version_unique');
            $table->index(['course_id', 'valid_from'], 'suitability_catalogs_course_validity_index');
        });

        $this->createIfMissing('suitability_catalog_specialty', function (Blueprint $table) {
            $table->foreignId('suitability_catalog_id')
                ->constrained('suitability_catalogs')->cascadeOnDelete();
            $table->foreignId('specialty_id')
                ->constrained('specialties')->restrictOnDelete();

            $table->primary(['suitability_catalog_id', 'specialty_id']);
            $table->index('specialty_id', 'suit_cat_spec_specialty_id_index');
        });

        $this->createIfMissing('job_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->timestamps();
        });

        $this->createIfMissing('instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()
                ->comment('Cuenta de acceso opcional')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('job_position_id')
                ->constrained('job_positions')->restrictOnDelete();
            $table->string('national_id', 12)->unique();
            $table->string('first_name', 60);
            $table->string('last_name', 60);
            $table->string('second_last_name', 60)->nullable();
            $table->decimal('estimated_workload', 3, 2)->nullable()
                ->comment('Indicativa; sujeta a confirmación de RRHH');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['last_name', 'second_last_name'], 'instructors_last_names_index');
        });

        $this->createIfMissing('credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')
                ->constrained('instructors')->cascadeOnDelete();
            $table->foreignId('specialty_id')
                ->constrained('specialties')->restrictOnDelete();
            $table->enum('degree_level', [
                'Diplomado',
                'Bachillerato',
                'Licenciatura',
                'Maestría',
                'Doctorado',
            ]);
            $table->string('institution', 150);
            $table->year('year_awarded');
            $table->timestamps();

            $table->unique(
                ['instructor_id', 'specialty_id', 'degree_level'],
                'credentials_instructor_specialty_degree_unique',
            );
        });

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->addCheckIfMissing(
            'suitability_catalogs',
            'chk_suitability_catalogs_validity',
            'valid_until IS NULL OR valid_until >= valid_from',
        );

        // La jornada es una fracción.
        $this->addCheckIfMissing(
            'instructors',
            'chk_instructors_estimated_workload',
            'estimated_workload IS NULL OR (estimated_workload > 0 AND estimated_workload <= 1)',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('credentials');
        Schema::dropIfExists('instructors');
        Schema::dropIfExists('job_positions');
        Schema::dropIfExists('suitability_catalog_specialty');
        Schema::dropIfExists('suitability_catalogs');
        Schema::dropIfExists('specialties');
    }
};
