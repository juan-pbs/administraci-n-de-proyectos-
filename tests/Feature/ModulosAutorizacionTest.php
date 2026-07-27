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

test('estudiante no puede abrir modulos administrativos por url directa', function () {
    $estudiante = usuarioModuloAutorizacion('estudiante');

    $this->actingAs($estudiante)
        ->get('/modulos/usuarios')
        ->assertForbidden();
});

test('encargado no puede modificar proyectos fuera de sus encargos', function () {
    $encargado = usuarioModuloAutorizacion('encargado_proyectos');
    $docente = usuarioModuloAutorizacion('docente_asesor');

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

test('alumno queda activo solo en un equipo del mismo grupo', function () {
    $direccion = usuarioModuloAutorizacion('direccion_coordinacion');
    $estudiante = usuarioModuloAutorizacion('estudiante');

    $periodo = Periodo::query()->create([
        'nombre' => 'Mayo - Agosto 2026',
        'fecha_inicio' => '2026-06-24',
        'fecha_fin' => '2026-08-01',
        'estado' => 'activo',
    ]);
    $carrera = Carrera::query()->create(['nombre' => 'Tecnologias de la Informacion', 'clave' => 'TI', 'estado' => 'activa']);
    $grupo = GrupoAcademico::query()->create([
        'periodo_id' => $periodo->id,
        'carrera_id' => $carrera->id,
        'nombre' => '9B',
        'grado' => 9,
        'grupo' => 'B',
    ]);
    $equipoUno = Equipo::query()->create(['grupo_academico_id' => $grupo->id, 'nombre' => 'Equipo 1', 'estado' => 'activo']);
    $equipoDos = Equipo::query()->create(['grupo_academico_id' => $grupo->id, 'nombre' => 'Equipo 2', 'estado' => 'activo']);

    $this->actingAs($direccion)->post(route('equipos.alumnos.guardar'), [
        'equipo_id' => $equipoUno->id,
        'estudiante_id' => $estudiante->id,
    ])->assertRedirect(route('modulos.show', 'equipos'));

    $this->actingAs($direccion)->post(route('equipos.alumnos.guardar'), [
        'equipo_id' => $equipoDos->id,
        'estudiante_id' => $estudiante->id,
    ])->assertRedirect(route('modulos.show', 'equipos'));

    $this->assertDatabaseHas('integrantes_equipo', [
        'equipo_id' => $equipoUno->id,
        'estudiante_id' => $estudiante->id,
        'activo' => false,
    ]);
    $this->assertDatabaseHas('integrantes_equipo', [
        'equipo_id' => $equipoDos->id,
        'estudiante_id' => $estudiante->id,
        'activo' => true,
    ]);
});
