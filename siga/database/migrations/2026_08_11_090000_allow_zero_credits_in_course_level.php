<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Corrección de esquema.
return new class extends Migration
{
    public function up(): void
    {
        // SQLite.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE course_level DROP CHECK chk_course_level_credits');
        DB::statement('ALTER TABLE course_level ADD CONSTRAINT chk_course_level_credits CHECK (credits >= 0)');
    }

    public function down(): void
    {
        // SQLite.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE course_level DROP CHECK chk_course_level_credits');
        DB::statement('ALTER TABLE course_level ADD CONSTRAINT chk_course_level_credits CHECK (credits > 0)');
    }
};
