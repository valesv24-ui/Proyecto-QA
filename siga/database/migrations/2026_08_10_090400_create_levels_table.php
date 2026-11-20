<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 4C.2 Niveles del plan (nivel 1, 2, 3... por cuatrimestre).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')
                ->constrained('study_plans')->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->timestamps();

            $table->unique(['study_plan_id', 'number'], 'levels_plan_number_unique');
        });

        // SQLite.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE levels ADD CONSTRAINT chk_levels_number CHECK (number > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
