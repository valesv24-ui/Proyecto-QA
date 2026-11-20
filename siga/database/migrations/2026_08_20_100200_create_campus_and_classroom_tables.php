<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Recintos, aulas, equipamiento y reservas. */
return new class extends Migration
{
    /** MySQL no revierte DDL. */
    private function createIfMissing(string $table, Closure $definition): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }

    private function addCheckIfMissing(string $table, string $name, string $expression): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->exists();

        if (! $exists) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function up(): void
    {
        $this->createIfMissing('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->boolean('is_owned')->default(true)
                ->comment('0 = alquilado/convenio (UNED, Santa Fe)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->createIfMissing('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->nullable()
                ->constrained('campuses')->nullOnDelete();
            $table->string('name', 30);
            $table->string('floor', 10)->nullable();
            $table->enum('type', [
                'Aula regular',
                'Laboratorio de cómputo',
                'Laboratorio de ciencias',
                'Laboratorio de idiomas',
                'Auditorio',
                'Otro',
            ])->default('Aula regular');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->date('unavailable_from')->nullable()
                ->comment('No disponible a partir de esta fecha');
            $table->timestamps();

            // El nombre del aula es único POR RECINTO, no globalmente.
            $table->unique(['campus_id', 'name'], 'classrooms_campus_name_unique');
            $table->index(['type', 'capacity'], 'classrooms_type_capacity_index');
        });

        $this->createIfMissing('equipment_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->timestamps();
        });

        $this->createIfMissing('classroom_equipment_item', function (Blueprint $table) {
            $table->foreignId('classroom_id')
                ->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('equipment_item_id')
                ->constrained('equipment_items')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamp('created_at')->nullable();

            $table->primary(['classroom_id', 'equipment_item_id']);
            $table->index('equipment_item_id', 'classroom_equipment_item_equipment_index');
        });

        $this->createIfMissing('classroom_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')
                ->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()
                ->comment('Usuario que registró la reserva')
                ->constrained('users')->nullOnDelete();
            $table->enum('type', ['Reserva', 'Bloqueo administrativo'])->default('Reserva');
            $table->string('requester', 120)->nullable()
                ->comment('NULL en bloqueos administrativos');
            $table->string('purpose', 255);
            $table->date('start_date');
            $table->date('end_date')->nullable()->comment('NULL = reserva de un solo día');
            // El original usa SET(...).
            $table->string('days_of_week', 120)->nullable()
                ->comment('Días que aplica dentro del rango; NULL = todos / día único');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['Solicitada', 'Aprobada', 'Rechazada', 'Cancelada'])
                ->default('Solicitada');
            $table->timestamps();

            $table->index(
                ['classroom_id', 'start_date', 'end_date'],
                'classroom_reservations_classroom_dates_index',
            );
            $table->index('status', 'classroom_reservations_status_index');
        });

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // MODIFY COLUMN es idempotente.
        DB::statement(
            'ALTER TABLE classroom_reservations MODIFY COLUMN days_of_week '
            ."SET('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') NULL "
            ."COMMENT 'Días que aplica dentro del rango; NULL = todos / día único'"
        );

        $this->addCheckIfMissing('classroom_equipment_item', 'chk_classroom_equipment_quantity', 'quantity > 0');

        $this->addCheckIfMissing('classroom_reservations', 'chk_classroom_reservations_time_range', 'end_time > start_time');

        $this->addCheckIfMissing(
            'classroom_reservations',
            'chk_classroom_reservations_date_range',
            'end_date IS NULL OR end_date >= start_date',
        );

        // Una 'Reserva' siempre tiene solicitante.
        $this->addCheckIfMissing(
            'classroom_reservations',
            'chk_classroom_reservations_requester',
            "(type = 'Reserva' AND requester IS NOT NULL) "
            ."OR (type = 'Bloqueo administrativo' AND requester IS NULL)",
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_reservations');
        Schema::dropIfExists('classroom_equipment_item');
        Schema::dropIfExists('equipment_items');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('campuses');
    }
};
