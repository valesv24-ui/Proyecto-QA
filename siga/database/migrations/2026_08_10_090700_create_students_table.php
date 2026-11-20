<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 4D. Estudiantes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()
                ->comment('Cuenta de acceso al portal')
                ->constrained('users')->nullOnDelete();
            $table->string('national_id', 12)->unique();
            $table->string('first_name', 60);
            $table->string('last_name', 60);
            $table->string('second_last_name', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['last_name', 'second_last_name'], 'students_last_names_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
