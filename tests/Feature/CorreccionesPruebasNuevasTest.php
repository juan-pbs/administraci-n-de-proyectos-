<?php

use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function datosCorreccionesPruebas(): array
{
    $coordinacion = Role::query()->firstOrCreate(['nombre' => 'coordinacion'], ['nombre_visible' => 'Coordinación']);
    $docente = Role::query()->firstOrCreate(['nombre' => 'docente_materia'], ['nombre_visible' => 'Docente de materia']);
    $carrera = Carrera::query()->create(['nombre' => 'Mecatrónica', 'clave' => 'MEC', 'estado' => 'activa']);
    $periodo = Periodo::query()->create(['nombre' => 'Noviembre - Diciembre 2026', 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-12-31', 'estado' => 'activo']);
    $asignatura = Asignatura::query()->create(['carrera_id' => $carrera->id, 'nombre' => 'Integradora', 'clave' => 'MEC-INT', 'grado' => 4, 'estado' => 'activo']);
    $usuario = User::factory()->create(['rol_id' => $coordinacion->id]);
    $profesor = User::factory()->create(['rol_id' => $docente->id, 'carrera_id' => $carrera->id, 'correo' => 'anterior@example.invalid']);

    return compact('carrera', 'periodo', 'asignatura', 'usuario', 'profesor');
}

test('coordinacion puede corregir datos de un docente existente', function () {
    $d = datosCorreccionesPruebas();
    $this->actingAs($d['usuario']);

    $this->get(route('modulos.show', ['modulo' => 'usuarios', 'seccion' => 'docentes', 'carrera_docentes' => $d['carrera']->id]))
        ->assertOk()->assertSee('Editar docente');

    $this->patch(route('usuarios.docentes.actualizar', $d['profesor']), [
        'docente_editando' => $d['profesor']->id,
        'nombre' => 'Docente corregido', 'matricula' => 'DOC-MEC-99',
        'correo' => 'docente@utvm.edu.mx', 'carrera_id' => $d['carrera']->id,
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('usuarios', [
        'id' => $d['profesor']->id, 'nombre' => 'Docente corregido',
        'matricula' => 'DOC-MEC-99', 'correo' => 'docente@utvm.edu.mx',
    ]);

    $this->patch(route('usuarios.docentes.actualizar', $d['profesor']), [
        'nombre' => 'Docente corregido', 'matricula' => 'DOC-MEC-99',
        'correo' => 'docente@utvm.edu.mx', 'carrera_id' => $d['carrera']->id,
        'estado' => 'inactivo',
    ])->assertSessionHasNoErrors()->assertRedirect(route('modulos.show', [
        'modulo' => 'usuarios', 'seccion' => 'docentes', 'carrera_docentes' => $d['carrera']->id,
    ]));
    $this->assertDatabaseHas('usuarios', ['id' => $d['profesor']->id, 'estado' => 'inactivo']);
    $this->assertDatabaseHas('auditoria_usuarios', ['actor_id' => $d['usuario']->id, 'usuario_id' => $d['profesor']->id, 'accion' => 'cambiar_estado_docente']);

    $this->patch(route('usuarios.docentes.actualizar', $d['profesor']), [
        'nombre' => 'Docente corregido', 'matricula' => 'DOC-MEC-99',
        'correo' => 'docente@example.com', 'carrera_id' => $d['carrera']->id,
    ])->assertSessionHasErrors('correo');

    $d['profesor']->update(['correo' => 'historico@example.invalid']);
    $this->patch(route('usuarios.docentes.actualizar', $d['profesor']), [
        'nombre' => 'Docente corregido', 'matricula' => 'DOC-MEC-99',
        'correo' => 'historico@example.invalid', 'carrera_id' => $d['carrera']->id,
        'estado' => 'activo',
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('usuarios', ['id' => $d['profesor']->id, 'estado' => 'activo']);
});

test('la guia exige una version numerica y muestra la nueva guia con sus filtros', function () {
    $d = datosCorreccionesPruebas();
    $this->actingAs($d['usuario']);
    $datos = [
        'periodo_id' => $d['periodo']->id, 'asignatura_id' => $d['asignatura']->id,
        'nombre' => 'Guía corregida', 'cuatrimestre' => 4,
    ];

    $this->post(route('guias.guardar'), [...$datos, 'version' => '*****1*1'])
        ->assertSessionHasErrors('version');
    expect(GuiaIntegradora::query()->where('nombre', 'Guía corregida')->exists())->toBeFalse();

    $respuesta = $this->post(route('guias.guardar'), [...$datos, 'version' => '1.0'])
        ->assertRedirect(route('modulos.show', [
            'modulo' => 'guias', 'periodo_guias' => $d['periodo']->id,
            'carrera_guias' => $d['carrera']->id,
        ]));
    $this->get($respuesta->headers->get('Location'))->assertOk()->assertSee('Guía corregida');
});

test('un apartado no acepta fecha vencida aunque pertenezca al periodo de la guia', function () {
    Carbon::setTestNow('2026-11-13 12:00:00');
    try {
        $d = datosCorreccionesPruebas();
        $guia = GuiaIntegradora::query()->create([
            'periodo_id' => $d['periodo']->id, 'asignatura_id' => $d['asignatura']->id,
            'creado_por' => $d['usuario']->id, 'nombre' => 'Guía', 'cuatrimestre' => 4,
            'version' => '1.0', 'estado' => 'borrador',
        ]);
        $datos = ['guia_integradora_id' => $guia->id, 'orden' => 1, 'titulo' => 'Protocolo', 'ponderacion' => 100];
        $this->actingAs($d['usuario']);

        $this->post(route('guias.apartados.guardar'), [...$datos, 'fecha_limite' => '2026-11-12 13:00'])
            ->assertSessionHasErrors('fecha_limite');
        expect(ApartadoGuia::query()->where('guia_integradora_id', $guia->id)->exists())->toBeFalse();

        $this->post(route('guias.apartados.guardar'), [...$datos, 'fecha_limite' => '2026-11-14 13:00'])
            ->assertSessionHasNoErrors();
    } finally {
        Carbon::setTestNow();
    }
});

test('crear una asignatura abre la asignacion de docente y conserva su seleccion', function () {
    $d = datosCorreccionesPruebas();
    $this->actingAs($d['usuario']);

    $this->post(route('asignaturas.guardar'), [
        'periodo_asignaturas' => $d['periodo']->id, 'carrera_id' => $d['carrera']->id,
        'nombre' => 'Nueva materia', 'clave' => 'MEC-NUEVA', 'grado' => 5,
    ])->assertSessionHasNoErrors()->assertRedirectContains('asignar_docente=1');

    $nueva = Asignatura::query()->where('clave', 'MEC-NUEVA')->firstOrFail();
    $this->get(route('modulos.show', [
        'modulo' => 'asignaturas', 'periodo_asignaturas' => $d['periodo']->id,
        'carrera_asignaturas' => $d['carrera']->id, 'grado_asignaturas' => 5,
        'asignar_docente' => 1, 'asignatura_id' => $nueva->id,
    ]))->assertOk()->assertSee('Asignar docente')->assertSee('MEC-NUEVA');
});

test('usuarios y jerarquia ofrecen regreso a carreras y grupos cuando ese fue el origen', function () {
    $d = datosCorreccionesPruebas();
    $this->actingAs($d['usuario']);
    $regreso = route('modulos.show', [
        'modulo' => 'carreras-grupos', 'periodo_grupos' => $d['periodo']->id,
        'carrera_grupos' => $d['carrera']->id,
    ]);

    $this->get(route('modulos.show', [
        'modulo' => 'usuarios', 'seccion' => 'alumnos',
        'periodo_alumnos' => $d['periodo']->id, 'carrera_alumnos' => $d['carrera']->id,
        'origen' => 'carreras-grupos',
    ]))->assertOk()->assertSee($regreso)->assertSee('Volver a Carreras y grupos');

    $this->get(route('modulos.jerarquia', [
        'periodo_id' => $d['periodo']->id, 'carrera_id' => $d['carrera']->id,
        'origen' => 'carreras-grupos',
    ]))->assertOk()->assertSee($regreso)->assertSee('Volver a Carreras y grupos');
});
