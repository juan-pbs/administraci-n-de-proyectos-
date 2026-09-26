<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firmas_docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->unique()->constrained('usuarios')->cascadeOnDelete();
            $table->longText('imagen'); // Cifrada por el modelo; nunca se sirve como archivo público.
            $table->string('sha256', 64);
            $table->timestamps();
        });
        Schema::table('revisiones', function (Blueprint $table) {
            $table->longText('firma_imagen')->nullable();
            $table->string('firma_sha256', 64)->nullable();
            $table->timestamp('firmado_en')->nullable();
        });
        Schema::table('productos_codigo', function (Blueprint $table) {
            $table->string('demostracion_url', 1000)->nullable();
        });
        Schema::table('archivos_entrega', function (Blueprint $table) {
            $table->boolean('es_aplicacion')->default(false);
        });
        Schema::create('documentos_finales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('generado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('huella', 64);
            $table->string('sha256', 64);
            $table->longText('pdf'); // Copia inmutable cifrada.
            $table->timestamps();
            $table->unique(['proyecto_id', 'huella']);
        });
        Schema::create('avisos_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('apartado_guia_id')->constrained('apartados_guia')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('clave', 64)->unique();
            $table->string('huella', 64);
            $table->string('tipo', 40);
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('programado_en');
            $table->timestamp('despachado_en')->nullable();
            $table->timestamp('enviado_en')->nullable();
            $table->timestamps();
            $table->index(['estado', 'programado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_asignaciones');
        Schema::dropIfExists('documentos_finales');
        Schema::table('archivos_entrega', fn (Blueprint $table) => $table->dropColumn('es_aplicacion'));
        Schema::table('productos_codigo', fn (Blueprint $table) => $table->dropColumn('demostracion_url'));
        Schema::table('revisiones', fn (Blueprint $table) => $table->dropColumn(['firma_imagen', 'firma_sha256', 'firmado_en']));
        Schema::dropIfExists('firmas_docentes');
    }
};
