<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Índices para las columnas por las que la bandeja ORDENA. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_requests', function (Blueprint $table) {
            $table->index('created_at', 'student_requests_created_at_index');
            $table->index('status', 'student_requests_status_index');
            $table->index('estimated_resolution_date', 'student_requests_estimated_date_index');
        });

        Schema::table('academic_records', function (Blueprint $table) {
            // Lo que consulta approvedCourseIds(): los cursos aprobados de un estudiante.
            $table->index(['student_id', 'status'], 'academic_records_student_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('student_requests', function (Blueprint $table) {
            $table->dropIndex('student_requests_created_at_index');
            $table->dropIndex('student_requests_status_index');
            $table->dropIndex('student_requests_estimated_date_index');
        });

        Schema::table('academic_records', function (Blueprint $table) {
            $table->dropIndex('academic_records_student_status_index');
        });
    }
};
