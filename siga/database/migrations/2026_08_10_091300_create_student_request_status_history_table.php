<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 7C.4 Historial de estados.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_request_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_request_id')
                ->constrained('student_requests')->cascadeOnDelete();
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->string('comment', 255)->nullable()
                ->comment('Justificación del cambio; obligatoria en denegaciones (capa app)');
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('notified_at')->nullable()->comment('Momento del correo al estudiante');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['student_request_id', 'created_at'], 'student_request_status_history_request_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_request_status_history');
    }
};
