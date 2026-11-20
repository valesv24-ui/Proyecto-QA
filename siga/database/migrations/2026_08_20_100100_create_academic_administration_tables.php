<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Unidades ejecutoras, metas presupuestarias y modalidades de impartición. */
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
        $this->createIfMissing('executing_units', function (Blueprint $table) {
            $table->id();
            $table->char('code', 10)->unique();
            $table->string('name', 150);
            $table->timestamps();
        });

        $this->createIfMissing('budget_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('executing_unit_id')
                ->constrained('executing_units')->restrictOnDelete();
            $table->char('code', 6);
            $table->string('name', 100);
            $table->timestamps();

            $table->unique(['executing_unit_id', 'code'], 'budget_targets_unit_code_unique');
        });

        $this->createIfMissing('delivery_modes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->unique();
            $table->boolean('requires_resolution')->default(false);
            $table->timestamps();
        });

        $this->createIfMissing('delivery_mode_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained('courses')->cascadeOnDelete();
            $table->foreignId('delivery_mode_id')
                ->constrained('delivery_modes')->restrictOnDelete();
            $table->string('resolution_number', 60)
                ->comment('Ej.: Resolución/acuerdo de vicerrectoría');
            $table->string('approving_body', 120);
            $table->date('valid_from');
            $table->date('valid_until')->nullable()->comment('NULL = vigencia indefinida');
            $table->timestamps();

            $table->unique(
                ['course_id', 'delivery_mode_id', 'resolution_number'],
                'delivery_mode_resolutions_course_mode_number_unique',
            );
            $table->index(
                ['course_id', 'valid_from', 'valid_until'],
                'delivery_mode_resolutions_course_validity_index',
            );
        });

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->addCheckIfMissing(
            'delivery_mode_resolutions',
            'chk_delivery_mode_resolutions_validity',
            'valid_until IS NULL OR valid_until >= valid_from',
        );
    }

    public function down(): void
    {
        // Orden inverso al de creación.
        Schema::dropIfExists('delivery_mode_resolutions');
        Schema::dropIfExists('delivery_modes');
        Schema::dropIfExists('budget_targets');
        Schema::dropIfExists('executing_units');
    }
};
