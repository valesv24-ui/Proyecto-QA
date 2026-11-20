<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 7C.2 Convalidaciones históricas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_credit_precedents', function (Blueprint $table) {
            $table->id();
            $table->string('institution', 150);
            $table->string('external_course', 150);
            $table->foreignId('course_id')
                ->comment('Curso interno UTN equivalente')
                ->constrained('courses')->restrictOnDelete();
            $table->enum('outcome', ['Aprobada', 'Denegada']);
            $table->string('resolution_number', 60)->comment('Resolución de referencia del precedente');
            $table->timestamps();

            $table->index(['institution', 'external_course'], 'transfer_credit_precedents_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_credit_precedents');
    }
};
