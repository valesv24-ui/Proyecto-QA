<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 4C.3 Pivote curso <-> nivel: créditos por plan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_level', function (Blueprint $table) {
            $table->foreignId('level_id')
                ->constrained('levels')->cascadeOnDelete();
            $table->foreignId('course_id')
                ->constrained('courses')->restrictOnDelete();
            $table->unsignedTinyInteger('credits');
            $table->timestamp('created_at')->nullable();

            $table->primary(['level_id', 'course_id']);
        });

        // SQLite.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE course_level ADD CONSTRAINT chk_course_level_credits CHECK (credits > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('course_level');
    }
};
