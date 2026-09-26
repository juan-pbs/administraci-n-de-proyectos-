<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_proyectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->unique()->constrained('proyectos')->cascadeOnDelete();
            $table->string('estado')->default('pendiente');
            $table->unsignedInteger('ronda')->default(1);
            $table->json('pendientes');
            $table->timestamp('detectado_en');
            $table->foreignId('decidido_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('decidido_en')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamps();
        });
        Schema::create('prorrogas_apartados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cierre_proyecto_id')->constrained('cierres_proyectos')->cascadeOnDelete();
            $table->foreignId('apartado_guia_id')->constrained('apartados_guia')->cascadeOnDelete();
            $table->unsignedInteger('ronda');
            $table->foreignId('docente_id')->constrained('usuarios');
            $table->timestamp('fecha_limite');
            $table->timestamps();
            $table->unique(['cierre_proyecto_id', 'apartado_guia_id', 'ronda'], 'prorroga_apartado_ronda_unica');
        });
        Schema::create('decisiones_cierre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cierre_proyecto_id')->constrained('cierres_proyectos')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('usuarios');
            $table->unsignedInteger('ronda');
            $table->string('decision');
            $table->text('motivo');
            $table->json('pendientes');
            $table->json('apartados');
            $table->timestamp('fecha_limite')->nullable();
            $table->timestamps();
        });
        Schema::create('avisos_cierres', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 64)->unique();
            $table->foreignId('cierre_proyecto_id')->constrained('cierres_proyectos')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('usuarios')->cascadeOnDelete();
            $table->unsignedInteger('ronda');
            $table->string('estado')->default('pendiente');
            $table->timestamp('despachado_en')->nullable();
            $table->timestamp('enviado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_cierres');
        Schema::dropIfExists('decisiones_cierre');
        Schema::dropIfExists('prorrogas_apartados');
        Schema::dropIfExists('cierres_proyectos');
    }
};
