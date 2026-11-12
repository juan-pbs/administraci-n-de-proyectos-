<?php

use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Equipo;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function datosIncidencias(): array
{
    $roles = collect(['coordinacion', 'docente_lider', 'estudiante'])->mapWithKeys(fn ($nombre) => [
        $nombre => Role::query()->firstOrCreate(['nombre' => $nombre], ['nombre_visible' => $nombre]),
    ]);
    $carrera = Carrera::query()->create(['nombre' => 'Tecnologías de la Información', 'clave' => 'TI-INC', 'estado' => 'activa']);
    $anterior = Periodo::query()->create(['nombre' => 'Mayo - Agosto 2026', 'fecha_inicio' => '2026-05-01', 'fecha_fin' => '2026-08-31', 'estado' => 'cerrado']);
    $actual = Periodo::query()->create(['nombre' => 'Enero - Abril 2027', 'fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-04-30', 'estado' => 'activo']);
    $materia = Asignatura::query()->create(['carrera_id' => $carrera->id, 'nombre' => 'Integradora', 'clave' => 'TI-INT', 'grado' => 1, 'estado' => 'activo']);
    $lider = User::factory()->create(['rol_id' => $roles['docente_lider']->id, 'carrera_id' => $carrera->id, 'estado' => 'activo']);
    $coordinacion = User::factory()->create(['rol_id' => $roles['coordinacion']->id]);
    $alumno = User::factory()->create(['rol_id' => $roles['estudiante']->id, 'carrera_id' => $carrera->id, 'estado' => 'activo']);
    $grupoAnterior = GrupoAcademico::query()->create(['periodo_id' => $anterior->id, 'carrera_id' => $carrera->id, 'nombre' => '1A', 'grado' => 1, 'grupo' => 'A']);
    $grupoActual = GrupoAcademico::query()->create(['periodo_id' => $actual->id, 'carrera_id' => $carrera->id, 'nombre' => '1A', 'grado' => 1, 'grupo' => 'A']);
    $alumno->update(['grupo_academico_id' => $grupoActual->id]);

    return compact('carrera', 'anterior', 'actual', 'materia', 'lider', 'coordinacion', 'alumno', 'grupoAnterior', 'grupoActual');
}

test('un docente de la carrera puede ser asignado como líder sin materia previamente vinculada', function () {
    $d = datosIncidencias();
    $this->actingAs($d['coordinacion'])
        ->get(route('modulos.jerarquia', ['periodo_id' => $d['actual']->id, 'carrera_id' => $d['carrera']->id]))
        ->assertOk()->assertSee($d['lider']->matricula);
    $this->post(route('jerarquia.lideres.guardar'), [
        'grupo_academico_id' => $d['grupoActual']->id,
        'lider_proyecto_id' => $d['lider']->id,
        'asignatura_lider_id' => $d['materia']->id,
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('grupos_academicos', ['id' => $d['grupoActual']->id, 'lider_proyecto_id' => $d['lider']->id]);
    $this->assertDatabaseHas('docentes_asignatura', ['periodo_id' => $d['actual']->id, 'asignatura_id' => $d['materia']->id, 'docente_id' => $d['lider']->id, 'activo' => true]);
});

test('una membresía de un ciclo cerrado no ocupa al alumno en su grupo actual', function () {
    $d = datosIncidencias();
    $d['lider']->asignaturasComoDocente()->attach($d['materia']->id, ['periodo_id' => $d['actual']->id, 'activo' => true]);
    $d['grupoActual']->update(['lider_proyecto_id' => $d['lider']->id, 'asignatura_lider_id' => $d['materia']->id]);
    $equipoAnterior = Equipo::query()->create(['grupo_academico_id' => $d['grupoAnterior']->id, 'numero' => 1, 'nombre' => 'Equipo anterior', 'estado' => 'activo']);
    $equipoAnterior->integrantes()->attach($d['alumno']->id, ['activo' => true]);

    $this->actingAs($d['lider'])->get(route('modulos.show', 'equipos'))
        ->assertOk()->assertSee('1 alumnos libres');
    $this->post(route('equipos.guardar'), ['grupo_academico_id' => $d['grupoActual']->id, 'nombre' => 'Equipo actual'])
        ->assertSessionHasNoErrors();
    $equipoActual = Equipo::query()->where('grupo_academico_id', $d['grupoActual']->id)->firstOrFail();
    $this->post(route('equipos.alumnos.guardar'), ['equipo_id' => $equipoActual->id, 'estudiante_id' => $d['alumno']->id])
        ->assertSessionHasNoErrors();
    expect(DB::table('integrantes_equipo')->where('equipo_id', $equipoAnterior->id)->where('activo', true)->exists())->toBeTrue();
});

test('la vista inicial del alumno muestra el proyecto del grupo y periodo actuales', function () {
    $d = datosIncidencias();
    $equipoAnterior = Equipo::query()->create(['grupo_academico_id' => $d['grupoAnterior']->id, 'numero' => 1, 'nombre' => 'Equipo anterior', 'estado' => 'activo']);
    $equipoActual = Equipo::query()->create(['grupo_academico_id' => $d['grupoActual']->id, 'numero' => 1, 'nombre' => 'Equipo actual', 'estado' => 'activo']);
    $equipoAnterior->integrantes()->attach($d['alumno']->id, ['activo' => true]);
    $equipoActual->integrantes()->attach($d['alumno']->id, ['activo' => true]);
    foreach ([$d['actual'], $d['anterior']] as $periodo) {
        $guia = GuiaIntegradora::query()->create(['periodo_id' => $periodo->id, 'asignatura_id' => $d['materia']->id, 'nombre' => 'Guía '.$periodo->nombre, 'cuatrimestre' => 1, 'version' => '1.0', 'estado' => 'publicada']);
        ApartadoGuia::query()->create(['guia_integradora_id' => $guia->id, 'orden' => 1, 'titulo' => 'Apartado '.$periodo->nombre, 'ponderacion' => 100, 'requiere_documento' => true]);
        Proyecto::query()->create(['guia_integradora_id' => $guia->id, 'equipo_id' => $periodo->id === $d['actual']->id ? $equipoActual->id : $equipoAnterior->id, 'titulo' => 'Proyecto '.$periodo->nombre, 'estado' => 'en_proceso']);
    }
    $this->actingAs($d['alumno'])->get(route('estudiante.entregas'))
        ->assertOk()->assertSee('Apartado Enero - Abril 2027')->assertDontSee('Apartado Mayo - Agosto 2026');
});

test('el registro de docentes rechaza correos ajenos al dominio institucional', function () {
    Mail::fake();
    $d = datosIncidencias();
    $datos = ['docentes' => [[
        'nombre' => 'Docente nuevo', 'matricula' => 'DOC-PRUEBA-1',
        'correo' => 'docente@example.com', 'rol' => 'docente_lider', 'carrera_id' => $d['carrera']->id,
    ]]];
    $this->actingAs($d['coordinacion'])->post(route('usuarios.docentes.guardar'), $datos)
        ->assertSessionHasErrors('docentes.0.correo');
    $this->post(route('usuarios.guardar'), [
        'nombre' => 'Docente nuevo', 'matricula' => 'DOC-PRUEBA-2',
        'correo' => 'docente@example.com', 'rol_id' => $d['lider']->rol_id,
        'carrera_id' => $d['carrera']->id,
    ])->assertSessionHasErrors('correo');
    $this->assertDatabaseMissing('usuarios', ['matricula' => 'DOC-PRUEBA-1']);
    $this->assertDatabaseMissing('usuarios', ['matricula' => 'DOC-PRUEBA-2']);
});
