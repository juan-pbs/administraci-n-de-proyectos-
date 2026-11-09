<?php

use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function coordinacionCapturaAcademica(): User
{
    $rol = Role::query()->firstOrCreate(['nombre' => 'coordinacion'], ['nombre_visible' => 'Coordinación']);

    return User::factory()->create(['rol_id' => $rol->id]);
}

test('un periodo nuevo empieza como minimo en el mes actual', function () {
    $this->actingAs(coordinacionCapturaAcademica());
    $inicioMinimo = now()->startOfMonth()->toDateString();

    $this->post(route('periodos.guardar'), [
        'nombre' => 'Periodo anterior',
        'fecha_inicio' => now()->subMonth()->startOfMonth()->toDateString(),
        'fecha_fin' => now()->addMonth()->endOfMonth()->toDateString(),
    ])->assertSessionHasErrors('fecha_inicio');

    $this->post(route('periodos.guardar'), [
        'nombre' => 'Periodo actual',
        'fecha_inicio' => $inicioMinimo,
        'fecha_fin' => now()->addMonth()->endOfMonth()->toDateString(),
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('periodos', ['nombre' => 'Periodo actual', 'fecha_inicio' => $inicioMinimo]);
});

test('la vista previa de alumnos solo acepta el periodo activo', function () {
    $this->actingAs(coordinacionCapturaAcademica());
    $carrera = Carrera::query()->create(['nombre' => 'Mecatrónica', 'clave' => 'MEC', 'estado' => 'activa']);
    $borrador = Periodo::query()->create(['nombre' => 'Borrador', 'fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-04-30', 'estado' => 'borrador']);
    $activo = Periodo::query()->create(['nombre' => 'Activo', 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-12-20', 'estado' => 'activo']);
    $lista = fn () => [[
        'archivo' => UploadedFile::fake()->createWithContent('alumnos.csv', "matricula,nombre_completo,correo_electronico\n2027001,Ana,ana@example.com\n"),
        'carrera_id' => $carrera->id,
        'grado' => 4,
        'grupo' => 'A',
    ]];

    $this->post(route('usuarios.alumnos.previsualizar'), ['periodo_id' => $borrador->id, 'listas' => $lista()])
        ->assertSessionHasErrors('periodo_id');
    $this->post(route('usuarios.alumnos.previsualizar'), ['periodo_id' => $activo->id, 'listas' => $lista()])
        ->assertSessionHasNoErrors();

    $this->get(route('modulos.show', 'usuarios'))
        ->assertOk()
        ->assertSee('Cuatrimestre')
        ->assertSee('Seleccionar');
});

test('la fecha limite de un apartado respeta los periodos que abarca la guia', function () {
    $coordinacion = coordinacionCapturaAcademica();
    $this->actingAs($coordinacion);
    $carrera = Carrera::query()->create(['nombre' => 'Mecatrónica', 'clave' => 'MEC', 'estado' => 'activa']);
    $asignatura = Asignatura::query()->create(['carrera_id' => $carrera->id, 'nombre' => 'Integradora', 'grado' => 4, 'estado' => 'activo']);
    $inicio = Periodo::query()->create(['nombre' => 'Enero - Abril 2027', 'fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-04-30', 'estado' => 'activo']);
    $fin = Periodo::query()->create(['nombre' => 'Mayo - Agosto 2027', 'fecha_inicio' => '2027-05-01', 'fecha_fin' => '2027-08-31', 'estado' => 'borrador']);
    $guia = GuiaIntegradora::query()->create([
        'periodo_id' => $inicio->id, 'periodo_fin_id' => $fin->id, 'asignatura_id' => $asignatura->id,
        'creado_por' => $coordinacion->id, 'nombre' => 'Guía de prueba', 'cuatrimestre' => 4,
        'version' => '1.0', 'estado' => 'borrador',
    ]);
    $datos = ['guia_integradora_id' => $guia->id, 'orden' => 1, 'titulo' => 'Protocolo', 'ponderacion' => 100];

    $this->post(route('guias.apartados.guardar'), [...$datos, 'fecha_limite' => '2026-12-31 23:59'])
        ->assertSessionHasErrors('fecha_limite');
    $this->post(route('guias.apartados.guardar'), [...$datos, 'fecha_limite' => '2027-08-31 23:59'])
        ->assertSessionHasNoErrors();
    $this->post(route('guias.apartados.guardar'), [...$datos, 'fecha_limite' => '2027-09-01 00:00'])
        ->assertSessionHasErrors('fecha_limite');

    $this->assertDatabaseHas('apartados_guia', ['guia_integradora_id' => $guia->id, 'orden' => 1]);
});

test('asignaturas y equipos rechazan emojis en sus nombres', function () {
    $coordinacion = coordinacionCapturaAcademica();
    $carrera = Carrera::query()->create(['nombre' => 'Mecatrónica', 'clave' => 'MEC', 'estado' => 'activa']);
    $this->actingAs($coordinacion);

    $this->post(route('asignaturas.guardar'), [
        'carrera_id' => $carrera->id, 'nombre' => 'Programación 😀', 'clave' => 'MEC-PROG', 'grado' => 4,
    ])->assertSessionHasErrors('nombre');
    $this->post(route('asignaturas.guardar'), [
        'carrera_id' => $carrera->id, 'nombre' => 'Programación C++', 'clave' => 'MEC-PROG', 'grado' => 4,
    ])->assertSessionHasNoErrors();

    $rolLider = Role::query()->firstOrCreate(['nombre' => 'docente_lider'], ['nombre_visible' => 'Docente líder']);
    $lider = User::factory()->create(['rol_id' => $rolLider->id]);
    $this->actingAs($lider)->post(route('equipos.guardar'), [
        'grupo_academico_id' => 1, 'nombre' => 'Equipo 😀',
    ])->assertSessionHasErrors('nombre');
});
