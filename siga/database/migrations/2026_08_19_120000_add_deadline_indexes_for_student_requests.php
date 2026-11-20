<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Índices para las consultas de PLAZO que introdujo el panel. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_requests', function (Blueprint $table) {
            $table->index(['status', 'estimated_resolution_date'], 'student_requests_status_deadline_index');
            $table->index(['status', 'created_at'], 'student_requests_status_received_index');
            $table->index(['type', 'status', 'estimated_resolution_date'], 'student_requests_summary_index');

            $table->dropIndex('student_requests_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('student_requests', function (Blueprint $table) {
            $table->index('status', 'student_requests_status_index');

            $table->dropIndex('student_requests_status_deadline_index');
            $table->dropIndex('student_requests_status_received_index');
            $table->dropIndex('student_requests_summary_index');
        });
    }
};
