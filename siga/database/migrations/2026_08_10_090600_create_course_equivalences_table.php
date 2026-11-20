<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 4C.5 Equiparaciones entre planes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_equivalences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_course_id')
                ->comment('Curso del plan anterior')
                ->constrained('courses')->restrictOnDelete();
            $table->foreignId('target_course_id')
                ->comment('Curso equivalente del plan nuevo')
                ->constrained('courses')->restrictOnDelete();
            $table->enum('direction', ['Anterior a nuevo', 'Nuevo a anterior', 'Bidireccional']);
            $table->string('resolution_number', 60);
            $table->enum('status', ['Vigente', 'Sustituida'])->default('Vigente');
            // Sin nullOnDelete().
            $table->foreignId('superseded_by_id')->nullable()
                ->comment('Equiparación que prevalece (RC-02)')
                ->constrained('course_equivalences')->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['source_course_id', 'target_course_id', 'resolution_number'],
                'course_equivalences_pair_resolution_unique'
            );
            $table->index('status', 'course_equivalences_status_index');
        });

        // SQLite.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE course_equivalences ADD CONSTRAINT chk_course_equivalences_distinct '
            .'CHECK (source_course_id <> target_course_id)'
        );
        // Corrección: una equiparación 'Sustituida' debe referenciar.
        DB::statement(
            'ALTER TABLE course_equivalences ADD CONSTRAINT chk_course_equivalences_superseded '
            ."CHECK ((status = 'Sustituida' AND superseded_by_id IS NOT NULL) "
            ."OR (status = 'Vigente' AND superseded_by_id IS NULL))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('course_equivalences');
    }
};
