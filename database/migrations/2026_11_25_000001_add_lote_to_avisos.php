<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['avisos_asignaciones', 'avisos_revisiones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->uuid('lote_manual')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['avisos_asignaciones', 'avisos_revisiones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->dropIndex(['lote_manual']);
                $table->dropColumn('lote_manual');
            });
        }
    }
};
