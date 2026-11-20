<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Bitácora de descargas y auditoría polimórfica. */
return new class extends Migration
{
    /** MySQL no revierte DDL. */
    private function createIfMissing(string $table, Closure $definition): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }

    public function up(): void
    {
        $this->createIfMissing('attachment_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attachment_id')
                ->constrained('attachments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['attachment_id', 'created_at'], 'attachment_downloads_attachment_date_index');
        });

        $this->createIfMissing('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('auditable_type', 120)
                ->comment('Clase del modelo (App\\Models\\Credential, ...)');
            $table->unsignedBigInteger('auditable_id');
            $table->enum('action', ['Creación', 'Modificación', 'Eliminación']);
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // El orden de las columnas del índice no es casual.
            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'audits_auditable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
        Schema::dropIfExists('attachment_downloads');
    }
};
