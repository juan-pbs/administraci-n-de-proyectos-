<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('docentes_asignatura', function (Blueprint $table): void {
            $table->index('asignatura_id', 'docentes_asignatura_asignatura_fk_index');
            $table->dropUnique('asignatura_docente_unica');
            $table->foreignId('periodo_id')->nullable()->after('docente_id')->constrained('periodos')->cascadeOnDelete();
            $table->unique(['periodo_id', 'asignatura_id', 'docente_id'], 'periodo_asignatura_docente_unica');
        });

        $periodoId = DB::table('periodos')->where('estado', 'activo')->orderByDesc('fecha_inicio')->value('id')
            ?: DB::table('periodos')->orderByDesc('fecha_inicio')->value('id');

        if ($periodoId) {
            DB::table('docentes_asignatura')->whereNull('periodo_id')->update(['periodo_id' => $periodoId]);
        }
    }

    public function down(): void
    {
        Schema::table('docentes_asignatura', function (Blueprint $table): void {
            $table->dropUnique('periodo_asignatura_docente_unica');
            $table->dropConstrainedForeignId('periodo_id');
            $table->unique(['asignatura_id', 'docente_id'], 'asignatura_docente_unica');
            $table->dropIndex('docentes_asignatura_asignatura_fk_index');
        });
    }
};
