<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entregas', function (Blueprint $table): void {
            $table->foreignId('entregado_por_id')
                ->nullable()
                ->after('equipo_id')
                ->constrained('usuarios')
                ->nullOnDelete();
        });

        DB::table('entregas')->orderBy('id')->eachById(function ($entrega): void {
            $estudianteId = DB::table('integrantes_equipo')
                ->where('equipo_id', $entrega->equipo_id)
                ->where('activo', true)
                ->orderBy('id')
                ->value('estudiante_id');

            if ($estudianteId) {
                DB::table('entregas')->where('id', $entrega->id)->update(['entregado_por_id' => $estudianteId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('entregas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('entregado_por_id');
        });
    }
};
