<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('periodos')->where('estado', 'activo')->count() > 1) {
            throw new RuntimeException('Existe más de un periodo activo. Resuelve sus estados antes de aplicar esta migración.');
        }
        Schema::table('periodos', function (Blueprint $table) {
            $table->unsignedTinyInteger('activo_unico')->nullable()->virtualAs("CASE WHEN estado = 'activo' THEN 1 ELSE NULL END");
            $table->unique('activo_unico', 'periodos_un_solo_activo');
        });
        // Adapta el historial ya existente al ciclo de cierre sin tocar sus PDF.
        $cerrados = fn () => DB::table('periodos')->where('estado', 'cerrado')->select('id');
        DB::table('guias_integradoras')->where('estado', 'publicada')
            ->where(fn ($q) => $q->whereIn('periodo_fin_id', $cerrados())
                ->orWhere(fn ($q) => $q->whereNull('periodo_fin_id')->whereIn('periodo_id', $cerrados())))
            ->update(['estado' => 'cerrada']);
    }

    public function down(): void
    {
        Schema::table('periodos', function (Blueprint $table) {
            $table->dropUnique('periodos_un_solo_activo');
            $table->dropColumn('activo_unico');
        });
    }
};
