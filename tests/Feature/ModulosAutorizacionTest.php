<?php

use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\EncargoProyecto;
use App\Models\Equipo;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function usuarioModuloAutorizacion(string $rol): User
{
    $rolModelo = Role::query()->firstOrCreate(
        ['nombre' => $rol],
        [
            'nombre_visible' => str($rol)->replace('_', ' ')->title()->toString(),
            'descripcion' => 'Rol de prueba',
        ],
    );

    return User::factory()->create(['rol_id' => $rolModelo->id]);
}

function habilitarMateriaLiderPrueba(User $docente, string $sufijo = 'BASE'): GrupoAcademico
{
    $periodo = Periodo::query()->create(['nombre' => "Periodo {$sufijo}", 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-04-30', 'estado' => 'activo']);
    $carrera = Carrera::query()->create(['nombre' => "Carrera {$sufijo}", 'clave' => "C{$sufijo}", 'estado' => 'activa']);
    $asignatura = Asignatura::query()->create(['carrera_id' => $carrera->id, 'nombre' => 'Integradora', 'clave' => "INT-{$sufijo}", 'grado' => 9, 'estado' => 'activo']);
    $docente->asignaturasComoDocente()->attach($asignatura->id, ['periodo_id' => $periodo->id, 'activo' => true]);

    return GrupoAcademico::query()->create([
        'periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'lider_proyecto_id' => $docente->id,
        'asignatura_lider_id' => $asignatura->id, 'nombre' => '9B', 'grado' => 9, 'grupo' => 'B',
    ]);
}

test('estudiante no puede abrir modulos administrativos por url directa', function () {
    $estudiante = usuarioModuloAutorizacion('estudiante');

    $this->actingAs($estudiante)
        ->get('/modulos/usuarios')
        ->assertForbidden();
});

test('reportes y respaldos ya no existen', function () {
    $coordinacion = usuarioModuloAutorizacion('coordinacion');

    $this->actingAs($coordinacion)
        ->get('/modulos/reportes')
        ->assertNotFound();

    $this->actingAs($coordinacion)
        ->get('/modulos/respaldos')
        ->assertNotFound();
});

test('equipos y proyectos pertenecen al docente lider', function () {
    $coordinacion = usuarioModuloAutorizacion('coordinacion');
    $docenteLider = usuarioModuloAutorizacion('docente_lider');
    habilitarMateriaLiderPrueba($docenteLider, 'VISTAS');

    $this->actingAs($coordinacion)->get('/modulos/equipos')->assertForbidden();
    $this->actingAs($coordinacion)->get('/modulos/proyectos')->assertForbidden();
    $this->actingAs($docenteLider)->get('/modulos/equipos')->assertOk();
    $this->actingAs($docenteLider)->get('/modulos/proyectos')->assertOk();
});

test('docente lider solo consulta alumnos y no puede registrarlos ni importarlos', function () {
    $docenteLider = usuarioModuloAutorizacion('docente_lider');
    habilitarMateriaLiderPrueba($docenteLider, 'ALUMNOS');

    $this->actingAs($docenteLider)
        ->get('/modulos/usuarios')
        ->assertOk()
        ->assertSee('Lista de alumnos')
        ->assertDontSee('Generar vista previa');

    $this->actingAs($docenteLider)->post(route('usuarios.guardar'))->assertForbidden();
    $this->actingAs($docenteLider)->post(route('usuarios.alumnos.previsualizar'))->assertForbidden();
    $this->actingAs($docenteLider)->post(route('usuarios.alumnos.confirmar'))->assertForbidden();
    $this->actingAs($docenteLider)->post(route('usuarios.alumnos.importar'))->assertForbidden();
});

test('coordinacion no puede administrar proyectos de equipos', function () {
    $encargado = usuarioModuloAutorizacion('coordinacion');
    $docente = usuarioModuloAutorizacion('docente_lider');

    $periodo = Periodo::query()->create([
        'nombre' => 'Mayo - Agosto 2026',
        'fecha_inicio' => '2026-06-24',
        'fecha_fin' => '2026-08-01',
        'estado' => 'activo',
    ]);

    $carreraPermitida = Carrera::query()->create(['nombre' => 'Tecnologias de la Informacion', 'clave' => 'TI', 'estado' => 'activa']);
    $carreraAjena = Carrera::query()->create(['nombre' => 'Mecatronica', 'clave' => 'MECA', 'estado' => 'activa']);

    EncargoProyecto::query()->create([
        'encargado_id' => $encargado->id,
        'periodo_id' => $periodo->id,
        'carrera_id' => $carreraPermitida->id,
        'cuatrimestre' => 9,
        'activo' => true,
    ]);

    $grupoAjeno = GrupoAcademico::query()->create([
        'periodo_id' => $periodo->id,
        'carrera_id' => $carreraAjena->id,
        'nombre' => '9B',
        'grado' => 9,
        'grupo' => 'B',
    ]);

    $equipoAjeno = Equipo::query()->create([
        'grupo_academico_id' => $grupoAjeno->id,
        'nombre' => 'Equipo ajeno',
        'estado' => 'activo',
    ]);

    $asignaturaAjena = Asignatura::query()->create([
        'carrera_id' => $carreraAjena->id,
        'nombre' => 'Integradora',
        'clave' => 'MECA-INT-09',
        'grado' => 9,
        'estado' => 'activo',
    ]);

    $guiaAjena = GuiaIntegradora::query()->create([
        'periodo_id' => $periodo->id,
        'asignatura_id' => $asignaturaAjena->id,
        'nombre' => 'Guia ajena',
        'version' => '1.0',
        'estado' => 'publicada',
    ]);

    $proyectoAjeno = Proyecto::query()->create([
        'guia_integradora_id' => $guiaAjena->id,
        'equipo_id' => $equipoAjeno->id,
        'titulo' => 'Proyecto fuera de encargo',
        'estado' => 'en_proceso',
    ]);

    $this->actingAs($encargado)
        ->post(route('proyectos.docentes.guardar'), [
            'proyecto_id' => $proyectoAjeno->id,
            'docente_id' => $docente->id,
            'tipo_participacion' => 'evaluador',
        ])
        ->assertForbidden();
});

test('alumno solo puede asignarse a un equipo de su propio grupo', function () {
    $docenteLider = usuarioModuloAutorizacion('docente_lider');
    $estudiante = usuarioModuloAutorizacion('estudiante');

    $periodo = Periodo::query()->create([
        'nombre' => 'Mayo - Agosto 2026',
        'fecha_inicio' => '2026-06-24',
        'fecha_fin' => '2026-08-01',
        'estado' => 'activo',
    ]);
    $carrera = Carrera::query()->create(['nombre' => 'Tecnologias de la Informacion', 'clave' => 'TI', 'estado' => 'activa']);
    $asignaturaLider = Asignatura::query()->create(['carrera_id' => $carrera->id, 'nombre' => 'Integradora', 'clave' => 'TI-INT-09', 'grado' => 9, 'estado' => 'activo']);
    $docenteLider->asignaturasComoDocente()->attach($asignaturaLider->id, ['periodo_id' => $periodo->id, 'activo' => true]);
    $grupo = GrupoAcademico::query()->create([
        'periodo_id' => $periodo->id,
        'carrera_id' => $carrera->id,
        'lider_proyecto_id' => $docenteLider->id,
        'asignatura_lider_id' => $asignaturaLider->id,
        'nombre' => '9B',
        'grado' => 9,
        'grupo' => 'B',
    ]);
    $estudiante->update(['carrera_id' => $carrera->id, 'grupo_academico_id' => $grupo->id]);
    $equipoUno = Equipo::query()->create(['grupo_academico_id' => $grupo->id, 'nombre' => 'Equipo 1', 'estado' => 'activo']);
    $equipoDos = Equipo::query()->create(['grupo_academico_id' => $grupo->id, 'nombre' => 'Equipo 2', 'estado' => 'activo']);

    $this->actingAs($docenteLider)->post(route('equipos.alumnos.guardar'), [
        'equipo_id' => $equipoUno->id,
        'estudiante_id' => $estudiante->id,
    ])->assertRedirect(route('modulos.show', 'equipos'));

    $this->actingAs($docenteLider)->post(route('equipos.alumnos.guardar'), [
        'equipo_id' => $equipoDos->id,
        'estudiante_id' => $estudiante->id,
    ])->assertStatus(422);

    $this->assertDatabaseHas('integrantes_equipo', [
        'equipo_id' => $equipoUno->id,
        'estudiante_id' => $estudiante->id,
        'activo' => true,
    ]);
    $this->assertDatabaseMissing('integrantes_equipo', [
        'equipo_id' => $equipoDos->id,
        'estudiante_id' => $estudiante->id,
    ]);

    $otroGrupo = GrupoAcademico::query()->create([
        'periodo_id' => $periodo->id,
        'carrera_id' => $carrera->id,
        'lider_proyecto_id' => $docenteLider->id,
        'asignatura_lider_id' => $asignaturaLider->id,
        'nombre' => '9A',
        'grado' => 9,
        'grupo' => 'A',
    ]);
    $alumnoOtroGrupo = usuarioModuloAutorizacion('estudiante');
    $alumnoOtroGrupo->update(['carrera_id' => $carrera->id, 'grupo_academico_id' => $otroGrupo->id]);

    $this->actingAs($docenteLider)->post(route('equipos.alumnos.guardar'), [
        'equipo_id' => $equipoUno->id,
        'estudiante_id' => $alumnoOtroGrupo->id,
    ])->assertStatus(422);
});
