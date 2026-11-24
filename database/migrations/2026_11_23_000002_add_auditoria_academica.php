<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_academica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('modulo', 40);
            $table->unsignedBigInteger('registro_id');
            $table->string('accion', 40);
            $table->text('motivo')->nullable();
            $table->json('antes');
            $table->json('despues');
            $table->timestamp('creado_en');
            $table->index(['modulo', 'registro_id']);
        });
        Schema::table('comentarios_revision', fn (Blueprint $table) => $table->softDeletes('eliminado_en'));
        Schema::table('guias_integradoras', function (Blueprint $table) {
            $table->foreignId('guia_origen_id')->nullable()->constrained('guias_integradoras')->restrictOnDelete();
            $table->unique(['guia_origen_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::table('guias_integradoras', function (Blueprint $table) {
            $table->dropUnique(['guia_origen_id', 'version']);
            $table->dropConstrainedForeignId('guia_origen_id');
        });
        Schema::table('comentarios_revision', fn (Blueprint $table) => $table->dropSoftDeletes('eliminado_en'));
        Schema::dropIfExists('auditoria_academica');
    }
};
