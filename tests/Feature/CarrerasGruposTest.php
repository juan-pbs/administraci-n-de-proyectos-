<?php

use App\Models\Carrera;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

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

test('el alta de grupos solo acepta una letra de A a L', function () {
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
        ->assertOk()->assertSee('Grupo (A–L)');

    foreach (['dsds', '9', '#', 'M'] as $grupo) {
        $this->post(route('grupos-academicos.guardar'), [...$datos, 'grupo' => $grupo])
            ->assertSessionHasErrors('grupo');
    }

    $this->post(route('grupos-academicos.guardar'), [...$datos, 'grupo' => 'c'])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('grupos_academicos', [...$datos, 'grupo' => 'C', 'nombre' => '8C']);
    $this->post(route('grupos-academicos.guardar'), [...$datos, 'grupo' => 'L'])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('grupos_academicos', [...$datos, 'grupo' => 'L', 'nombre' => '8L']);
    expect(\App\Models\GrupoAcademico::query()->count())->toBe(2);
});

test('la carga de alumnos rechaza grupos fuera de A a L en ambos formatos', function () {
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
