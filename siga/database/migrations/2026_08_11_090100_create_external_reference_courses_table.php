<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Catálogo de referencia de cursos de otras instituciones.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_reference_courses', function (Blueprint $table) {
            $table->id();
            $table->string('institution', 150)->comment('Ej.: Universidad de Costa Rica');
            $table->string('program_reference', 150)->comment('Carrera/plan de origen, ej.: Bachillerato en Computación con varios énfasis');
            $table->string('track', 150)->nullable()->comment('Bloque común o énfasis, ej.: Énfasis en Ingeniería de Software');
            $table->string('code', 30)->comment('Código del curso en la institución de origen, ej.: CI0112');
            $table->string('name', 200);
            $table->unsignedTinyInteger('cycle')->nullable()->comment('Ciclo/nivel según el plan de origen');
            $table->unsignedTinyInteger('credits')->nullable();
            $table->timestamps();

            $table->unique(['institution', 'code', 'track'], 'external_reference_courses_institution_code_track_unique');
            $table->index(['institution', 'name'], 'external_reference_courses_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_reference_courses');
    }
};
