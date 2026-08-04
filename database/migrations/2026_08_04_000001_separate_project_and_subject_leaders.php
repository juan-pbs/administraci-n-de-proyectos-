<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupos_academicos', function (Blueprint $table): void {
            $table->foreignId('docente_materia_lider_id')
                ->nullable()
                ->after('lider_proyecto_id')
                ->constrained('usuarios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('grupos_academicos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('docente_materia_lider_id');
        });
    }
};
