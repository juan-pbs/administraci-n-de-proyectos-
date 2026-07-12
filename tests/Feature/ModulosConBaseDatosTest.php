<?php

use App\Correos\ContrasenaInicial;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Equipo;
use App\Models\GuiaIntegradora;
use App\Models\GrupoAcademico;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function direccionAutenticada(): User
{
    $rol = Role::query()->create([
        'nombre' => 'direccion_coordinacion',
        'nombre_visible' => 'Dirección / Coordinación',
        'descripcion' => 'Rol de prueba',
    ]);

    return User::factory()->create([
        'rol_id' => $rol->id,
    ]);
}

test('modulos base guardan informacion en base de datos', function () {
    Mail::fake();

    $direccion = direccionAutenticada();

    $rolEstudiante = Role::query()->create([
        'nombre' => 'estudiante',
        'nombre_visible' => 'Estudiante / Equipo',
        'descripcion' => 'Rol de prueba',
    ]);

    $this->actingAs($direccion)
        ->post(route('periodos.guardar'), [
            'nombre' => 'Septiembre - Noviembre 2026',
            'fecha_inicio' => '2026-09-10',
            'fecha_fin' => '2026-11-20',
            'estado' => 'activo',
        ])
        ->assertRedirect(route('modulos.show', 'periodos'));

    $periodo = Periodo::query()->where('nombre', 'Septiembre - Noviembre 2026')->firstOrFail();
    $carrera = Carrera::query()->create(['nombre' => 'Tecnologías de la Información', 'clave' => 'TI', 'estado' => 'activa']);
    $grupo = GrupoAcademico::query()->create([
        'periodo_id' => $periodo->id,
        'carrera_id' => $carrera->id,
        'nombre' => '9B',
        'grado' => 9,
        'grupo' => 'B',
    ]);

    $this->actingAs($direccion)
        ->post(route('usuarios.guardar'), [
            'nombre' => 'Alumno de Prueba',
            'matricula' => '20269999',
            'correo' => 'alumno.prueba@utvm.edu.mx',
            'rol_id' => $rolEstudiante->id,
            'carrera_id' => $carrera->id,
            'grupo_academico_id' => $grupo->id,
        ])
        ->assertRedirect(route('modulos.show', 'usuarios'));

    $estudiante = User::query()->where('matricula', '20269999')->firstOrFail();
    expect($estudiante->debe_cambiar_contrasena)->toBeTrue();

    Mail::assertSent(ContrasenaInicial::class, fn (ContrasenaInicial $correo) => $correo->hasTo('alumno.prueba@utvm.edu.mx'));

    $this->actingAs($direccion)
        ->post(route('asignaturas.guardar'), [
            'carrera_id' => $carrera->id,
            'nombre' => 'Integradora',
            'clave' => 'TI-INT-09',
            'grado' => 9,
        ])
        ->assertRedirect(route('modulos.show', 'asignaturas'));

    $asignatura = Asignatura::query()->where('clave', 'TI-INT-09')->firstOrFail();
    $guia = GuiaIntegradora::query()->create([
        'periodo_id' => $periodo->id,
        'asignatura_id' => $asignatura->id,
        'creado_por' => $direccion->id,
        'nombre' => 'Guía de prueba',
        'version' => '1.0',
        'estado' => 'publicada',
    ]);

    $this->actingAs($direccion)
        ->post(route('equipos.guardar'), [
            'grupo_academico_id' => $grupo->id,
            'nombre' => 'Equipo Prueba',
            'lider_id' => $estudiante->id,
        ])
        ->assertRedirect(route('modulos.show', 'equipos'));

    $equipo = Equipo::query()->where('nombre', 'Equipo Prueba')->firstOrFail();

    $this->actingAs($direccion)
        ->post(route('proyectos.guardar'), [
            'guia_integradora_id' => $guia->id,
            'equipo_id' => $equipo->id,
            'titulo' => 'Proyecto de Prueba',
            'descripcion' => 'Proyecto conectado a base de datos.',
        ])
        ->assertRedirect(route('modulos.show', 'proyectos'));

    $this->assertDatabaseHas('proyectos', [
        'equipo_id' => $equipo->id,
        'titulo' => 'Proyecto de Prueba',
    ]);
});
