<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 4C.1 Planes de estudio (Vigente/Terminal).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('degree_program_id')
                ->constrained('degree_programs')->restrictOnDelete();
            $table->string('name', 120)->comment('Ej.: Plan 2023, Plan 2025');
            $table->year('implementation_year');
            $table->enum('classification', ['Vigente', 'Terminal'])->default('Vigente');
            $table->date('enrollment_closing_date')->nullable()
                ->comment('Obligatoria solo para planes Terminal');
            $table->timestamps();

            $table->unique(['degree_program_id', 'name'], 'study_plans_program_name_unique');
            $table->index('classification', 'study_plans_classification_index');
        });

        // Corrección: un plan Terminal exige fecha de cierre de matrícula.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE study_plans ADD CONSTRAINT chk_study_plans_terminal_date '
            ."CHECK (classification = 'Vigente' OR enrollment_closing_date IS NOT NULL)"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plans');
    }
};
