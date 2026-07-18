<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'encargado_proyectos' => ['Encargado de proyectos', 'Administra proyectos por carrera, periodo y cuatrimestre.'],
            'lider_proyecto' => ['Líder de proyecto', 'Docente de la materia líder que organiza alumnos, equipos y contexto.'],
            'docente_materia' => ['Docente de materia', 'Revisa y califica la parte asignada a su materia.'],
        ] as $nombre => [$visible, $descripcion]) {
            DB::table('roles')->updateOrInsert(['nombre' => $nombre], [
                'nombre_visible' => $visible,
                'descripcion' => $descripcion,
                'actualizado_en' => now(),
                'creado_en' => now(),
            ]);
        }

        Schema::create('encargos_proyecto', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('encargado_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            $table->foreignId('carrera_id')->constrained('carreras')->cascadeOnDelete();
            $table->unsignedTinyInteger('cuatrimestre');
            $table->boolean('activo')->default(true);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->unique(['encargado_id', 'periodo_id', 'carrera_id', 'cuatrimestre'], 'encargo_proyecto_unico');
        });

        Schema::table('grupos_academicos', function (Blueprint $table): void {
            $table->foreignId('lider_proyecto_id')->nullable()->after('carrera_id')->constrained('usuarios')->nullOnDelete();
            $table->foreignId('asignatura_lider_id')->nullable()->after('lider_proyecto_id')->constrained('asignaturas')->nullOnDelete();
        });

        Schema::table('equipos', function (Blueprint $table): void {
            $table->unsignedInteger('numero')->nullable()->after('lider_id');
            $table->text('contexto_proyecto')->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table): void {
            $table->dropColumn(['numero', 'contexto_proyecto']);
        });
        Schema::table('grupos_academicos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('asignatura_lider_id');
            $table->dropConstrainedForeignId('lider_proyecto_id');
        });
        Schema::dropIfExists('encargos_proyecto');
    }
};
