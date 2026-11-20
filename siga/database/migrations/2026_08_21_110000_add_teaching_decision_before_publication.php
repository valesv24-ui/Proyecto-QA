<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** El paso que faltaba. */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE student_requests MODIFY COLUMN status ENUM('
                ."'Pendiente de revisión',"
                ."'Verificada por Registro',"
                ."'En revisión',"
                ."'Resuelta por Docencia',"
                ."'Aprobada',"
                ."'Denegada'"
                .") NOT NULL DEFAULT 'Pendiente de revisión'"
            );
        }

        if (! Schema::hasColumn('student_requests', 'teaching_decision')) {
            Schema::table('student_requests', function (Blueprint $table) {
                $table->enum('teaching_decision', ['Aprobada', 'Denegada'])
                    ->nullable()
                    ->after('status')
                    ->comment('Lo que resolvió Docencia; Registro lo publica sin poder cambiarlo');
            });
        }

        // Las solicitudes ya resueltas conservan su estado y reciben.
        DB::table('student_requests')
            ->whereIn('status', ['Aprobada', 'Denegada'])
            ->whereNull('teaching_decision')
            ->update(['teaching_decision' => DB::raw('status')]);
    }

    public function down(): void
    {
        // Primero se devuelven a "En revisión" las que quedaron.
        if (DB::getDriverName() === 'mysql') {
            DB::table('student_requests')
                ->where('status', 'Resuelta por Docencia')
                ->update(['status' => 'En revisión']);

            DB::statement(
                'ALTER TABLE student_requests MODIFY COLUMN status ENUM('
                ."'Pendiente de revisión',"
                ."'Verificada por Registro',"
                ."'En revisión',"
                ."'Aprobada',"
                ."'Denegada'"
                .") NOT NULL DEFAULT 'Pendiente de revisión'"
            );
        }

        if (Schema::hasColumn('student_requests', 'teaching_decision')) {
            Schema::table('student_requests', function (Blueprint $table) {
                $table->dropColumn('teaching_decision');
            });
        }
    }
};
