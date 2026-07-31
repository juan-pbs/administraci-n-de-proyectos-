<?php

use App\Models\Carrera;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
