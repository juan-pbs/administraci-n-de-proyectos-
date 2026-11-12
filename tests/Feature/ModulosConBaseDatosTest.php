<?php

use App\Correos\ContrasenaInicial;
use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Equipo;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('la carga de alumnos ofrece una plantilla vacía compatible con la vista previa', function () {
    $this->actingAs(direccionAutenticada())
        ->get(route('modulos.show', 'usuarios'))
        ->assertOk()
        ->assertSee('Descargar plantilla de alumnos (.xlsx)')
        ->assertSee(asset('plantillas/plantilla_carga_alumnos.xlsx'), false);

    $ruta = public_path('plantillas/plantilla_carga_alumnos.xlsx');
    $this->assertFileExists($ruta);
    $hoja = \PhpOffice\PhpSpreadsheet\IOFactory::load($ruta)->getSheetByName('Carga alumnos');
    expect($hoja->rangeToArray('A1:C1')[0])->toBe(['matricula', 'nombre_completo', 'correo_electronico']);
    expect($hoja->getCell('A2')->getValue())->toBeNull();
});

test('periodos rechaza años fuera del rango operativo antes de guardar', function () {
    $this->actingAs(direccionAutenticada())
        ->post(route('periodos.guardar'), [
            'nombre' => 'Periodo fuera de rango',
            'fecha_inicio' => '8888-02-08',
            'fecha_fin' => '8888-07-01',
        ])
        ->assertSessionHasErrors(['fecha_inicio', 'fecha_fin']);

    expect(Periodo::query()->where('nombre', 'Periodo fuera de rango')->exists())->toBeFalse();
});

test('apartados rechaza una fecha anterior al rango operativo', function () {
    $direccion = direccionAutenticada();
    $periodo = Periodo::query()->create([
        'nombre' => 'Periodo de validación', 'fecha_inicio' => '2026-09-01',
        'fecha_fin' => '2026-12-20', 'estado' => 'activo',
    ]);
    $carrera = Carrera::query()->create(['nombre' => 'Mecatrónica', 'clave' => 'MEC', 'estado' => 'activa']);
    $asignatura = Asignatura::query()->create(['carrera_id' => $carrera->id, 'nombre' => 'Integradora', 'grado' => 8]);
    $guia = GuiaIntegradora::query()->create([
        'periodo_id' => $periodo->id, 'asignatura_id' => $asignatura->id,
        'creado_por' => $direccion->id, 'nombre' => 'Guía de validación',
        'cuatrimestre' => 8, 'version' => '1.0', 'estado' => 'borrador',
    ]);

    $this->actingAs($direccion)->post(route('guias.apartados.guardar'), [
        'guia_integradora_id' => $guia->id, 'orden' => 1,
        'titulo' => 'Protocolo', 'ponderacion' => 50,
        'fecha_limite' => '1212-02-21 12:00',
    ])->assertSessionHasErrors('fecha_limite');

    expect(ApartadoGuia::query()->where('guia_integradora_id', $guia->id)->exists())->toBeFalse();
});

function direccionAutenticada(): User
{
    $rol = Role::query()->firstOrCreate([
        'nombre' => 'coordinacion',
    ], [
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
    $rolDocenteLider = Role::query()->where('nombre', 'docente_lider')->firstOrFail();
    $docenteLider = User::factory()->create(['rol_id' => $rolDocenteLider->id]);

    $rolEstudiante = Role::query()->create([
        'nombre' => 'estudiante',
        'nombre_visible' => 'Estudiante / Equipo',
        'descripcion' => 'Rol de prueba',
    ]);

    $this->actingAs($direccion)
        ->post(route('periodos.guardar'), [
            'nombre' => 'Periodo de prueba',
            'fecha_inicio' => now()->startOfMonth()->toDateString(),
            'fecha_fin' => now()->addMonths(2)->endOfMonth()->toDateString(),
            'estado' => 'borrador',
        ])
        ->assertRedirect(route('modulos.show', 'periodos'));

    $periodo = Periodo::query()->where('nombre', 'Periodo de prueba')->firstOrFail();
    $this->patch(route('periodos.activar', $periodo))->assertSessionHasNoErrors();
    $carrera = Carrera::query()->create(['nombre' => 'Tecnologías de la Información', 'clave' => 'TI', 'estado' => 'activa']);
    $grupo = GrupoAcademico::query()->create([
        'periodo_id' => $periodo->id,
        'carrera_id' => $carrera->id,
        'lider_proyecto_id' => $docenteLider->id,
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
    expect($estudiante->debe_cambiar_contrasena)->toBeFalse();

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
    $docenteLider->asignaturasComoDocente()->attach($asignatura->id, ['periodo_id' => $periodo->id, 'activo' => true]);
    $grupo->update(['asignatura_lider_id' => $asignatura->id]);
    $guia = GuiaIntegradora::query()->create([
        'periodo_id' => $periodo->id,
        'asignatura_id' => $asignatura->id,
        'creado_por' => $direccion->id,
        'nombre' => 'Guía de prueba',
        'cuatrimestre' => '9',
        'version' => '1.0',
        'estado' => 'publicada',
    ]);

    $this->actingAs($docenteLider)
        ->post(route('equipos.guardar'), [
            'grupo_academico_id' => $grupo->id,
            'nombre' => 'Equipo Prueba',
            'lider_id' => $estudiante->id,
        ])
        ->assertRedirect(route('modulos.show', 'equipos'));

    $equipo = Equipo::query()->where('nombre', 'Equipo Prueba')->firstOrFail();

    $this->actingAs($docenteLider)
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

test('coordinacion registra varios docentes en una sola operacion', function () {
    Mail::fake();
    $direccion = direccionAutenticada();
    $carrera = Carrera::query()->create([
        'nombre' => 'Tecnologías de la Información',
        'clave' => 'TI-LOTE',
        'estado' => 'activa',
    ]);

    $this->actingAs($direccion)->post(route('usuarios.docentes.guardar'), [
        'docentes' => [
            [
                'nombre' => 'Docente Líder',
                'matricula' => 'DOC-LOTE-1',
                'correo' => 'lider-lote@utvm.edu.mx',
                'rol' => 'docente_lider',
                'carrera_id' => $carrera->id,
            ],
            [
                'nombre' => 'Docente de Materia',
                'matricula' => 'DOC-LOTE-2',
                'correo' => 'materia-lote@utvm.edu.mx',
                'rol' => 'docente_materia',
                'carrera_id' => $carrera->id,
            ],
        ],
    ])->assertRedirect(route('modulos.show', ['modulo' => 'usuarios', 'seccion' => 'docentes']));

    $this->assertDatabaseHas('usuarios', ['matricula' => 'DOC-LOTE-1', 'carrera_id' => $carrera->id]);
    $this->assertDatabaseHas('usuarios', ['matricula' => 'DOC-LOTE-2', 'carrera_id' => $carrera->id]);
    Mail::assertSent(ContrasenaInicial::class, 2);
});

test('coordinacion consulta docentes lideres por carrera en la jerarquia', function () {
    $coordinacion = direccionAutenticada();
    $periodo = Periodo::query()->create([
        'nombre' => 'Enero - Abril 2027',
        'fecha_inicio' => '2027-01-08',
        'fecha_fin' => '2027-04-20',
        'estado' => 'activo',
    ]);
    $carrera = Carrera::query()->create([
        'nombre' => 'Administración',
        'clave' => 'ADM-JER',
        'estado' => 'activa',
    ]);
    $rolLider = Role::query()->where('nombre', 'docente_lider')->firstOrFail();
    $lider = User::factory()->create([
        'rol_id' => $rolLider->id,
        'carrera_id' => $carrera->id,
        'nombre' => 'Docente Líder de Administración',
    ]);
    $lider->carrerasComoDocente()->attach($carrera->id, ['activo' => true]);
    $asignaturaLider = Asignatura::query()->create([
        'carrera_id' => $carrera->id,
        'nombre' => 'Integradora',
        'clave' => 'ADM-INT-04',
        'grado' => 4,
        'estado' => 'activo',
    ]);
    $lider->asignaturasComoDocente()->attach($asignaturaLider->id, ['periodo_id' => $periodo->id, 'activo' => true]);
    GrupoAcademico::query()->create([
        'periodo_id' => $periodo->id,
        'carrera_id' => $carrera->id,
        'nombre' => '4A',
        'grado' => 4,
        'grupo' => 'A',
    ]);

    $this->actingAs($coordinacion)
        ->get(route('modulos.jerarquia', [
            'periodo_id' => $periodo->id,
            'carrera_id' => $carrera->id,
        ]))
        ->assertOk()
        ->assertSee('Docente Líder de Administración');
});

test('coordinacion asigna docentes que califican cada apartado de la guia', function () {
    $coordinacion = direccionAutenticada();
    $periodo = Periodo::query()->create([
        'nombre' => 'Mayo - Agosto 2027',
        'fecha_inicio' => '2027-05-01',
        'fecha_fin' => '2027-08-20',
        'estado' => 'activo',
    ]);
    $carrera = Carrera::query()->create(['nombre' => 'Mecatrónica', 'clave' => 'MECA-CAL', 'estado' => 'activa']);
    $asignatura = Asignatura::query()->create(['carrera_id' => $carrera->id, 'nombre' => 'Integradora', 'clave' => 'MECA-CAL-4', 'grado' => 4, 'estado' => 'activo']);
    $guia = GuiaIntegradora::query()->create([
        'periodo_id' => $periodo->id,
        'asignatura_id' => $asignatura->id,
        'creado_por' => $coordinacion->id,
        'nombre' => 'Guía calificable',
        'cuatrimestre' => 4,
        'version' => '1.0',
        'estado' => 'publicada',
    ]);
    $apartado = ApartadoGuia::query()->create([
        'guia_integradora_id' => $guia->id,
        'orden' => 1,
        'titulo' => 'Planteamiento',
        'ponderacion' => 20,
    ]);
    $rolDocente = Role::query()->where('nombre', 'docente_materia')->firstOrFail();
    $docente = User::factory()->create(['rol_id' => $rolDocente->id, 'carrera_id' => $carrera->id]);

    $this->actingAs($coordinacion)
        ->post(route('guias.apartados.calificadores.guardar'), [
            'apartado_guia_id' => $apartado->id,
            'docente_id' => $docente->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('firmas_apartado_guia', [
        'apartado_guia_id' => $apartado->id,
        'docente_id' => $docente->id,
        'requerida' => true,
    ]);
});
