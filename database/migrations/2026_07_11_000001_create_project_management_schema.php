<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('nombre_visible');
            $table->text('descripcion')->nullable();
            $this->marcasTiempo($table);
        });

        Schema::table('usuarios', function (Blueprint $table): void {
            if (! Schema::hasColumn('usuarios', 'rol_id')) {
                $table->foreignId('rol_id')->nullable()->after('matricula')->constrained('roles')->nullOnDelete();
            }

            if (! Schema::hasColumn('usuarios', 'estado')) {
                $table->string('estado')->default('activo')->after('contrasena');
            }

            if (! Schema::hasColumn('usuarios', 'debe_cambiar_contrasena')) {
                $table->boolean('debe_cambiar_contrasena')->default(false)->after('estado');
            }

            if (! Schema::hasColumn('usuarios', 'contrasena_actualizada_en')) {
                $table->timestamp('contrasena_actualizada_en')->nullable()->after('debe_cambiar_contrasena');
            }
        });

        Schema::create('periodos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado')->default('activo');
            $this->marcasTiempo($table);
        });

        Schema::create('carreras', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('clave')->unique();
            $table->string('estado')->default('activa');
            $this->marcasTiempo($table);
        });

        Schema::create('grupos_academicos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            $table->foreignId('carrera_id')->constrained('carreras')->cascadeOnDelete();
            $table->string('nombre');
            $table->unsignedTinyInteger('grado');
            $table->string('grupo');
            $this->marcasTiempo($table);

            $table->unique(['periodo_id', 'carrera_id', 'grado', 'grupo'], 'grupo_academico_unico');
        });

        Schema::table('usuarios', function (Blueprint $table): void {
            if (! Schema::hasColumn('usuarios', 'carrera_id')) {
                $table->foreignId('carrera_id')->nullable()->after('rol_id')->constrained('carreras')->nullOnDelete();
            }

            if (! Schema::hasColumn('usuarios', 'grupo_academico_id')) {
                $table->foreignId('grupo_academico_id')->nullable()->after('carrera_id')->constrained('grupos_academicos')->nullOnDelete();
            }
        });

        Schema::create('docentes_carrera', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carreras')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('usuarios')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $this->marcasTiempo($table);

            $table->unique(['carrera_id', 'docente_id']);
        });

        Schema::create('asignaturas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carrera_id')->nullable()->constrained('carreras')->nullOnDelete();
            $table->string('nombre');
            $table->string('clave')->nullable()->unique();
            $table->unsignedTinyInteger('grado')->nullable();
            $table->string('estado')->default('activo');
            $this->marcasTiempo($table);
        });

        Schema::create('docentes_asignatura', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asignatura_id')->constrained('asignaturas')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('usuarios')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $this->marcasTiempo($table);

            $table->unique(['asignatura_id', 'docente_id'], 'asignatura_docente_unica');
        });

        Schema::create('guias_integradoras', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            $table->foreignId('periodo_fin_id')->nullable()->constrained('periodos')->nullOnDelete();
            $table->foreignId('asignatura_id')->nullable()->constrained('asignaturas')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nombre');
            $table->string('cuatrimestre')->nullable();
            $table->text('competencias_evaluar')->nullable();
            $table->text('objetivo_aprendizaje')->nullable();
            $table->string('version')->default('1.0');
            $table->string('estado')->default('borrador');
            $this->marcasTiempo($table);
        });

        Schema::create('apartados_guia', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guia_integradora_id')->constrained('guias_integradoras')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(1);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha_limite')->nullable();
            $table->decimal('ponderacion', 5, 2)->default(0);
            $table->boolean('requiere_documento')->default(true);
            $table->boolean('requiere_codigo')->default(false);
            $this->marcasTiempo($table);
        });

        Schema::create('asignaturas_apartado_guia', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('apartado_guia_id')->constrained('apartados_guia')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->constrained('asignaturas')->cascadeOnDelete();
            $table->string('rol_contribucion')->nullable();
            $table->boolean('requiere_firma')->default(true);
            $this->marcasTiempo($table);

            $table->unique(['apartado_guia_id', 'asignatura_id'], 'apartado_asignatura_unica');
        });

        Schema::create('firmas_apartado_guia', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('apartado_guia_id')->constrained('apartados_guia')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->nullable()->constrained('asignaturas')->nullOnDelete();
            $table->foreignId('docente_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->unsignedInteger('orden')->default(1);
            $table->string('etiqueta');
            $table->boolean('requerida')->default(true);
            $this->marcasTiempo($table);
        });

        Schema::create('equipos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grupo_academico_id')->constrained('grupos_academicos')->cascadeOnDelete();
            $table->foreignId('lider_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nombre');
            $table->string('estado')->default('activo');
            $this->marcasTiempo($table);

            $table->unique(['grupo_academico_id', 'nombre']);
        });

        Schema::create('asesores_equipo', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('asesor_id')->constrained('usuarios')->cascadeOnDelete();
            $table->boolean('principal')->default(false);
            $table->boolean('activo')->default(true);
            $this->marcasTiempo($table);

            $table->unique(['equipo_id', 'asesor_id']);
        });

        Schema::create('integrantes_equipo', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('usuarios')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $this->marcasTiempo($table);

            $table->unique(['equipo_id', 'estudiante_id']);
        });

        Schema::create('proyectos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guia_integradora_id')->constrained('guias_integradoras')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('estado')->default('en_proceso');
            $this->marcasTiempo($table);

            $table->unique(['guia_integradora_id', 'equipo_id']);
        });

        Schema::create('asignaturas_proyecto', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->constrained('asignaturas')->cascadeOnDelete();
            $table->foreignId('docente_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->boolean('participa_evaluacion')->default(true);
            $this->marcasTiempo($table);

            $table->unique(['proyecto_id', 'asignatura_id']);
        });

        Schema::create('docentes_proyecto', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('tipo_participacion')->default('evaluador');
            $table->boolean('activo')->default(true);
            $this->marcasTiempo($table);

            $table->unique(['proyecto_id', 'docente_id']);
        });

        Schema::create('asignaciones_revision', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('apartado_guia_id')->constrained('apartados_guia')->cascadeOnDelete();
            $table->foreignId('revisor_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('tipo_revisor')->default('docente_asesor');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $this->marcasTiempo($table);

            $table->unique(['proyecto_id', 'apartado_guia_id', 'revisor_id'], 'asignaciones_revision_unica');
        });

        Schema::create('entregas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('apartado_guia_id')->constrained('apartados_guia')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('estado')->default('enviada');
            $table->timestamp('entregado_en')->nullable();
            $this->marcasTiempo($table);
        });

        Schema::create('archivos_entrega', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas')->cascadeOnDelete();
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('tipo_archivo')->nullable();
            $table->unsignedBigInteger('tamano')->default(0);
            $this->marcasTiempo($table);
        });

        Schema::create('productos_codigo', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('entrega_id')->nullable()->constrained('entregas')->nullOnDelete();
            $table->string('repositorio_url')->nullable();
            $table->string('archivo_fuente')->nullable();
            $table->string('version')->nullable();
            $this->marcasTiempo($table);
        });

        Schema::create('revisiones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas')->cascadeOnDelete();
            $table->foreignId('asignacion_revision_id')->nullable()->constrained('asignaciones_revision')->nullOnDelete();
            $table->foreignId('revisor_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('resultado')->default('pendiente');
            $table->decimal('calificacion', 5, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('revisado_en')->nullable();
            $this->marcasTiempo($table);
        });

        Schema::create('comentarios_revision', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('revision_id')->constrained('revisiones')->cascadeOnDelete();
            $table->foreignId('autor_id')->constrained('usuarios')->cascadeOnDelete();
            $table->text('comentario');
            $table->boolean('visible_estudiante')->default(true);
            $this->marcasTiempo($table);
        });

        Schema::create('notificaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('mensaje');
            $table->boolean('leido')->default(false);
            $table->timestamp('leido_en')->nullable();
            $this->marcasTiempo($table);
        });

        Schema::create('bitacora_actividades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('modulo');
            $table->string('accion');
            $table->text('descripcion')->nullable();
            $this->marcasTiempo($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_actividades');
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('comentarios_revision');
        Schema::dropIfExists('revisiones');
        Schema::dropIfExists('productos_codigo');
        Schema::dropIfExists('archivos_entrega');
        Schema::dropIfExists('entregas');
        Schema::dropIfExists('asignaciones_revision');
        Schema::dropIfExists('docentes_proyecto');
        Schema::dropIfExists('asignaturas_proyecto');
        Schema::dropIfExists('proyectos');
        Schema::dropIfExists('integrantes_equipo');
        Schema::dropIfExists('asesores_equipo');
        Schema::dropIfExists('equipos');
        Schema::dropIfExists('firmas_apartado_guia');
        Schema::dropIfExists('asignaturas_apartado_guia');
        Schema::dropIfExists('apartados_guia');
        Schema::dropIfExists('guias_integradoras');
        Schema::dropIfExists('docentes_asignatura');
        Schema::dropIfExists('asignaturas');
        Schema::dropIfExists('docentes_carrera');

        Schema::table('usuarios', function (Blueprint $table): void {
            if (Schema::hasColumn('usuarios', 'grupo_academico_id')) {
                $table->dropConstrainedForeignId('grupo_academico_id');
            }

            if (Schema::hasColumn('usuarios', 'carrera_id')) {
                $table->dropConstrainedForeignId('carrera_id');
            }
        });

        Schema::dropIfExists('grupos_academicos');
        Schema::dropIfExists('carreras');
        Schema::dropIfExists('periodos');

        Schema::table('usuarios', function (Blueprint $table): void {
            if (Schema::hasColumn('usuarios', 'rol_id')) {
                $table->dropConstrainedForeignId('rol_id');
            }

            if (Schema::hasColumn('usuarios', 'estado')) {
                $table->dropColumn('estado');
            }

            if (Schema::hasColumn('usuarios', 'debe_cambiar_contrasena')) {
                $table->dropColumn('debe_cambiar_contrasena');
            }

            if (Schema::hasColumn('usuarios', 'contrasena_actualizada_en')) {
                $table->dropColumn('contrasena_actualizada_en');
            }
        });

        Schema::dropIfExists('roles');
    }

    private function marcasTiempo(Blueprint $table): void
    {
        $table->timestamp('creado_en')->nullable();
        $table->timestamp('actualizado_en')->nullable();
    }
};
