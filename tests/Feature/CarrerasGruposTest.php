<?php

use App\Models\Carrera;
use App\Models\GrupoAcademico;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('coordinación edita y desactiva carreras y grupos y protege sus dependencias al eliminar', function () {
    $coordinacion = usuarioConRol('coordinacion');
    $periodo = Periodo::create(['nombre' => 'Ciclo de edición', 'fecha_inicio' => today(), 'fecha_fin' => today()->addMonth(), 'estado' => 'activo']);
    $carrera = Carrera::create(['nombre' => 'Tecnologías', 'clave' => 'TEC', 'estado' => 'activa']);
    $grupo = GrupoAcademico::create(['periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'grado' => 5, 'grupo' => 'A', 'nombre' => '5A']);
    $this->actingAs(usuarioConRol('docente_lider'))
        ->delete(route('carreras.eliminar', $carrera), ['confirmar_eliminacion' => 1])->assertForbidden();
    $this->actingAs($coordinacion)->get(route('modulos.carreras-grupos'))
        ->assertOk()->assertSee('Editar carrera')->assertSee('Editar grupo');
    $this->patch(route('carreras.actualizar', $carrera), ['nombre' => 'Tecnología aplicada', 'clave' => 'tec', 'estado' => 'inactiva'])
        ->assertSessionHasNoErrors();
    $this->post(route('grupos-academicos.guardar'), ['periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'grado' => 5, 'grupo' => 'B'])
        ->assertSessionHasErrors('carrera_id');
    $this->delete(route('carreras.eliminar', $carrera), ['confirmar_eliminacion' => 1])->assertSessionHasErrors('eliminar');
    $this->patch(route('grupos-academicos.actualizar', $grupo), [
        'periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'grado' => 6, 'grupo' => 'c', 'estado' => 'inactivo',
    ])->assertSessionHasNoErrors();
    expect($grupo->fresh()->only(['nombre', 'estado']))->toBe(['nombre' => '6C', 'estado' => 'inactivo']);
    $alumno = usuarioConRol('estudiante');
    $alumno->update(['grupo_academico_id' => $grupo->id]);
    $this->delete(route('grupos-academicos.eliminar', $grupo), ['confirmar_eliminacion' => 1])->assertSessionHasErrors('eliminar');
    $alumno->update(['grupo_academico_id' => null]);
    $this->delete(route('grupos-academicos.eliminar', $grupo), ['confirmar_eliminacion' => 1])->assertSessionHasNoErrors();
    $this->delete(route('carreras.eliminar', $carrera))->assertSessionHasErrors('confirmar_eliminacion');
    $this->delete(route('carreras.eliminar', $carrera), ['confirmar_eliminacion' => 1])->assertSessionHasNoErrors();
    $this->assertModelMissing($grupo)->assertModelMissing($carrera);
});

function usuarioConRol(string $rol): User
{
    $rolModelo = Role::query()->firstOrCreate([
        'nombre' => $rol,
    ], [
        'nombre_visible' => str($rol)->replace('_', ' ')->title()->toString(),
        'descripcion' => 'Rol de prueba',
    ]);

    return User::factory()->create([
        'rol_id' => $rolModelo->id,
    ]);
}

test('direccion puede abrir carreras y grupos', function () {
    $usuario = usuarioConRol('coordinacion');

    $this->actingAs($usuario)
        ->get('/modulos/carreras-grupos')
        ->assertOk()
        ->assertSee('Carreras y grupos');
});

test('direccion puede registrar carrera grupo y docente por carrera', function () {
    $direccion = usuarioConRol('coordinacion');
    $docente = usuarioConRol('docente_lider');

    $periodo = Periodo::query()->create([
        'nombre' => 'Mayo - Agosto 2026',
        'fecha_inicio' => '2026-06-24',
        'fecha_fin' => '2026-08-01',
        'estado' => 'activo',
    ]);

    $this->actingAs($direccion)
        ->post(route('carreras.guardar'), [
            'nombre' => 'Tecnologías de la Información',
            'clave' => 'TI',
        ])
        ->assertRedirect(route('modulos.show', 'carreras-grupos'));

    $carrera = Carrera::query()->where('clave', 'TI')->firstOrFail();

    $this->actingAs($direccion)
        ->post(route('grupos-academicos.guardar'), [
            'periodo_id' => $periodo->id,
            'carrera_id' => $carrera->id,
            'grado' => 9,
            'grupo' => 'B',
        ])
        ->assertRedirect(route('modulos.show', 'carreras-grupos'));

    $this->actingAs($direccion)
        ->post(route('docentes-carrera.guardar'), [
            'carrera_id' => $carrera->id,
            'docente_id' => $docente->id,
        ])
        ->assertRedirect(route('modulos.show', 'carreras-grupos'));

    $this->assertDatabaseHas('grupos_academicos', [
        'carrera_id' => $carrera->id,
        'periodo_id' => $periodo->id,
        'nombre' => '9B',
        'grado' => 9,
        'grupo' => 'B',
    ]);

    $this->assertDatabaseHas('docentes_carrera', [
        'carrera_id' => $carrera->id,
        'docente_id' => $docente->id,
        'activo' => true,
    ]);
});

test('el alta de grupos solo acepta una letra de A a M', function () {
    $direccion = usuarioConRol('coordinacion');
    $periodo = Periodo::query()->create([
        'nombre' => 'Septiembre - Diciembre 2026',
        'fecha_inicio' => '2026-09-01',
        'fecha_fin' => '2026-12-20',
        'estado' => 'activo',
    ]);
    $carrera = Carrera::query()->create(['nombre' => 'Mecatrónica', 'clave' => 'MEC', 'estado' => 'activa']);
    $datos = ['periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'grado' => 8];

    $this->actingAs($direccion)->get(route('modulos.show', 'carreras-grupos'))
        ->assertOk()->assertSee('Grupo (A–M)');

    foreach (['dsds', '9', '#', 'N'] as $grupo) {
        $this->post(route('grupos-academicos.guardar'), [...$datos, 'grupo' => $grupo])
            ->assertSessionHasErrors('grupo');
    }

    $this->post(route('grupos-academicos.guardar'), [...$datos, 'grupo' => 'c'])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('grupos_academicos', [...$datos, 'grupo' => 'C', 'nombre' => '8C']);
    $this->post(route('grupos-academicos.guardar'), [...$datos, 'grupo' => 'M'])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('grupos_academicos', [...$datos, 'grupo' => 'M', 'nombre' => '8M']);
    expect(\App\Models\GrupoAcademico::query()->count())->toBe(2);
});

test('la carga de alumnos rechaza grupos fuera de A a M en ambos formatos', function () {
    $direccion = usuarioConRol('coordinacion');
    Role::query()->firstOrCreate(['nombre' => 'estudiante'], ['nombre_visible' => 'Estudiante']);
    $periodo = Periodo::query()->create([
        'nombre' => 'Enero - Abril 2027',
        'fecha_inicio' => '2027-01-01',
        'fecha_fin' => '2027-04-30',
        'estado' => 'activo',
    ]);
    $carrera = Carrera::query()->create(['nombre' => 'Administración', 'clave' => 'ADM', 'estado' => 'activa']);

    $this->actingAs($direccion)->post(route('usuarios.alumnos.previsualizar'), [
        'periodo_id' => $periodo->id,
        'listas' => [[
            'archivo' => UploadedFile::fake()->createWithContent('alumnos.csv', "matricula,nombre_completo,correo_electronico\n2027001,Ana,ana@example.com\n"),
            'carrera_id' => $carrera->id, 'grado' => 4, 'grupo' => 'dsds',
        ]],
    ])->assertSessionHasErrors('listas.0.grupo');

    $this->post(route('usuarios.alumnos.previsualizar'), [
        'periodo_id' => $periodo->id,
        'listas' => [[
            'archivo' => UploadedFile::fake()->createWithContent('alumnos.csv', "matricula,nombre_completo,correo_electronico\n2027001,Ana,ana@example.com\n"),
            'carrera_id' => $carrera->id, 'grado' => 4, 'grupo' => 'L',
        ]],
    ])->assertSessionHasNoErrors();

    $this->post(route('usuarios.alumnos.importar'), [
        'periodo_id' => $periodo->id,
        'archivo' => UploadedFile::fake()->createWithContent('alumnos.csv', "matricula,nombre_completo,correo_electronico,clave_carrera,grado,grupo\n2027001,Ana,ana@example.com,ADM,4,9\n"),
    ])->assertSessionHasErrors('archivo');

    expect(\App\Models\GrupoAcademico::query()->count())->toBe(0);
});
