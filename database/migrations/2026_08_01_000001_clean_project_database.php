<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bitacora_actividades');
        Schema::dropIfExists('notificaciones');

        Schema::table('usuarios', function (Blueprint $table): void {
            $table->index(['rol_id', 'grupo_academico_id', 'estado'], 'usuarios_rol_grupo_estado_index');
        });

        Schema::table('guias_integradoras', function (Blueprint $table): void {
            $table->index(['periodo_id', 'asignatura_id', 'estado'], 'guias_periodo_asignatura_estado_index');
        });

        Schema::table('apartados_guia', function (Blueprint $table): void {
            $table->index(['guia_integradora_id', 'orden'], 'apartados_guia_orden_index');
        });

        Schema::table('firmas_apartado_guia', function (Blueprint $table): void {
            $table->index(['docente_id', 'apartado_guia_id'], 'firmas_docente_apartado_index');
        });

        Schema::table('entregas', function (Blueprint $table): void {
            $table->index(['equipo_id', 'apartado_guia_id', 'version'], 'entregas_equipo_apartado_version_index');
            $table->index(['proyecto_id', 'apartado_guia_id', 'estado'], 'entregas_proyecto_apartado_estado_index');
        });

        Schema::table('revisiones', function (Blueprint $table): void {
            $table->index(['revisor_id', 'resultado'], 'revisiones_revisor_resultado_index');
            $table->index(['entrega_id', 'revisor_id'], 'revisiones_entrega_revisor_index');
        });
    }

    public function down(): void
    {
        Schema::table('revisiones', function (Blueprint $table): void {
            $table->dropIndex('revisiones_entrega_revisor_index');
            $table->dropIndex('revisiones_revisor_resultado_index');
        });

        Schema::table('entregas', function (Blueprint $table): void {
            $table->dropIndex('entregas_proyecto_apartado_estado_index');
            $table->dropIndex('entregas_equipo_apartado_version_index');
        });

        Schema::table('firmas_apartado_guia', function (Blueprint $table): void {
            $table->dropIndex('firmas_docente_apartado_index');
        });

        Schema::table('apartados_guia', function (Blueprint $table): void {
            $table->dropIndex('apartados_guia_orden_index');
        });

        Schema::table('guias_integradoras', function (Blueprint $table): void {
            $table->dropIndex('guias_periodo_asignatura_estado_index');
        });

        Schema::table('usuarios', function (Blueprint $table): void {
            $table->dropIndex('usuarios_rol_grupo_estado_index');
        });

        Schema::create('notificaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('mensaje');
            $table->boolean('leido')->default(false);
            $table->timestamp('leido_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('bitacora_actividades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('modulo');
            $table->string('accion');
            $table->text('descripcion')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }
};
