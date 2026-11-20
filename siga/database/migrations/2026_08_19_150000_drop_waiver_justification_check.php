<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Elimina el CHECK que exigía justificación en TODO levantamiento. */
return new class extends Migration
{
    private const CONSTRAINT = 'chk_student_requests_waiver_justification';

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! $this->constraintExists()) {
            return;
        }

        DB::statement('ALTER TABLE student_requests DROP CHECK '.self::CONSTRAINT);
    }

    public function down(): void
    {
        // Deliberadamente vacío.
    }

    private function constraintExists(): bool
    {
        $found = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS '
            .'WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            ['student_requests', self::CONSTRAINT],
        );

        return $found !== null;
    }
};
