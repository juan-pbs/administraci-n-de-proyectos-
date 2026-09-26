<?php

use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\DocumentoFinal;
use App\Models\FirmaApartadoGuia;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function escenarioCiclo(): array
{
    $coordinacion = User::factory()->create(['rol_id' => Role::firstOrCreate(['nombre' => 'coordinacion'], ['nombre_visible' => 'Coordinación'])->id]);
    $docente = User::factory()->create(['rol_id' => Role::firstOrCreate(['nombre' => 'docente_lider'], ['nombre_visible' => 'Líder'])->id]);
    $periodo = Periodo::create(['nombre' => 'Ciclo de prueba', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-04-30', 'estado' => 'borrador']);
    $carrera = Carrera::create(['nombre' => 'Tecnologías', 'clave' => 'TI', 'estado' => 'activa']);
    $materia = Asignatura::create(['nombre' => 'Integradora', 'carrera_id' => $carrera->id, 'grado' => 5]);
    $guia = GuiaIntegradora::create(['nombre' => 'Guía de prueba', 'periodo_id' => $periodo->id, 'asignatura_id' => $materia->id, 'cuatrimestre' => 5, 'version' => '1', 'estado' => 'borrador']);
    $apartado = ApartadoGuia::create(['guia_integradora_id' => $guia->id, 'orden' => 1, 'titulo' => 'Protocolo', 'ponderacion' => 100, 'requiere_documento' => true]);
    FirmaApartadoGuia::create(['apartado_guia_id' => $apartado->id, 'docente_id' => $docente->id, 'orden' => 1, 'etiqueta' => 'Evaluador', 'requerida' => true]);

    return compact('coordinacion', 'docente', 'periodo', 'guia', 'apartado', 'materia');
}

test('altas siempre empiezan en borrador y no permiten sobrescribir un periodo existente', function () {
    $d = escenarioCiclo();
    $this->actingAs($d['coordinacion']);
    $datos = ['nombre' => 'Nuevo ciclo', 'fecha_inicio' => '2026-05-01', 'fecha_fin' => '2026-08-31'];
    foreach (['activo', 'cerrado'] as $estado) {
        $this->post(route('periodos.guardar'), [...$datos, 'estado' => $estado])->assertSessionHasErrors('estado');
    }
    $this->post(route('periodos.guardar'), $datos)->assertSessionHasNoErrors();
    $periodo = Periodo::where('nombre', 'Nuevo ciclo')->firstOrFail();
    expect($periodo->estado)->toBe('borrador');
    $this->post(route('periodos.guardar'), $datos)->assertSessionHasErrors('nombre');
    $guia = ['periodo_id' => $periodo->id, 'asignatura_id' => $d['materia']->id, 'nombre' => 'Nueva guía', 'cuatrimestre' => 5, 'version' => '1'];
    foreach (['publicada', 'cerrada'] as $estado) {
        $this->post(route('guias.guardar'), [...$guia, 'estado' => $estado])->assertSessionHasErrors('estado');
    }
    $this->post(route('guias.guardar'), $guia)->assertSessionHasNoErrors();
    expect(GuiaIntegradora::where('nombre', 'Nueva guía')->firstOrFail()->estado)->toBe('borrador');
});

test('activar exige borrador sin otro activo y cierre requiere revisar pendientes', function () {
    $d = escenarioCiclo();
    $siguiente = Periodo::create(['nombre' => 'Siguiente', 'fecha_inicio' => '2026-05-01', 'fecha_fin' => '2026-08-31', 'estado' => 'borrador']);
    $this->actingAs($d['coordinacion'])->patch(route('periodos.activar', $d['periodo']))->assertSessionHasNoErrors();
    $this->patch(route('periodos.activar', $siguiente))->assertSessionHasErrors('ciclo');
    expect($siguiente->fresh()->estado)->toBe('borrador');
    $this->patch(route('periodos.cerrar', $d['periodo']))->assertSessionHasErrors('confirmar_cierre');
    expect($d['periodo']->fresh()->estado)->toBe('activo');
    $this->patch(route('periodos.cerrar', $d['periodo']), ['confirmar_cierre' => 1])->assertSessionHasNoErrors();
    $this->patch(route('periodos.activar', $d['periodo']))->assertSessionHasErrors('ciclo');
    $this->patch(route('periodos.activar', $siguiente))->assertSessionHasNoErrors();
    expect($siguiente->fresh()->estado)->toBe('activo')->and(Periodo::where('estado', 'activo')->count())->toBe(1);
    $this->actingAs($d['docente'])->patch(route('periodos.cerrar', $siguiente), ['confirmar_cierre' => 1])->assertForbidden();
});

test('la base de datos impide dos periodos activos incluso fuera del controlador', function () {
    $d = escenarioCiclo();
    $d['periodo']->update(['estado' => 'activo']);
    expect(fn () => Periodo::create(['nombre' => 'Segundo activo', 'fecha_inicio' => '2026-05-01', 'fecha_fin' => '2026-08-31', 'estado' => 'activo']))->toThrow(QueryException::class);
});

test('publicar valida apartados ponderaciones evidencia y evaluadores activos', function () {
    $d = escenarioCiclo();
    $this->actingAs($d['coordinacion']);
    $d['apartado']->update(['ponderacion' => 50]);
    $this->patch(route('guias.publicar', $d['guia']))->assertSessionHasErrors('ciclo');
    $d['apartado']->update(['ponderacion' => 100, 'requiere_documento' => false]);
    $this->patch(route('guias.publicar', $d['guia']))->assertSessionHasErrors('ciclo');
    $d['apartado']->update(['requiere_documento' => true]);
    $d['docente']->update(['estado' => 'inactivo']);
    $this->patch(route('guias.publicar', $d['guia']))->assertSessionHasErrors('ciclo');
    $d['docente']->update(['estado' => 'activo']);
    $this->patch(route('guias.publicar', $d['guia']))->assertSessionHasNoErrors();
    expect($d['guia']->fresh()->estado)->toBe('publicada');
    $this->patch(route('guias.publicar', $d['guia']))->assertSessionHasErrors('ciclo');
    $this->actingAs($d['docente'])->patch(route('guias.publicar', $d['guia']))->assertForbidden();
});

test('el cierre respeta el periodo final de guías de varios ciclos y conserva borradores', function () {
    $d = escenarioCiclo();
    $d['periodo']->update(['estado' => 'activo']);
    $siguiente = Periodo::create(['nombre' => 'Siguiente', 'fecha_inicio' => '2026-05-01', 'fecha_fin' => '2026-08-31', 'estado' => 'borrador']);
    $d['guia']->update(['estado' => 'publicada']);
    $larga = GuiaIntegradora::create(['nombre' => 'Dos ciclos', 'periodo_id' => $d['periodo']->id, 'periodo_fin_id' => $siguiente->id, 'estado' => 'publicada']);
    $pendiente = GuiaIntegradora::create(['nombre' => 'Sin publicar', 'periodo_id' => $d['periodo']->id, 'estado' => 'borrador']);
    $this->actingAs($d['coordinacion'])->patch(route('periodos.cerrar', $d['periodo']), ['confirmar_cierre' => 1])->assertSessionHasNoErrors();
    expect($d['guia']->fresh()->estado)->toBe('cerrada')->and($larga->fresh()->estado)->toBe('publicada')->and($pendiente->fresh()->estado)->toBe('borrador')->and(DocumentoFinal::count())->toBe(0);
    $this->patch(route('guias.publicar', $pendiente))->assertSessionHasErrors('ciclo');
    $this->post(route('guias.apartados.guardar'), ['guia_integradora_id' => $d['guia']->id, 'orden' => 1, 'titulo' => 'Intento de edición', 'ponderacion' => 100])->assertSessionHasErrors('ciclo');
    expect($d['apartado']->fresh()->titulo)->toBe('Protocolo');
    $this->get(route('modulos.guias', ['periodo_guias' => $d['periodo']->id]))->assertOk()->assertSee('Guía disponible para consulta');
    $this->patch(route('periodos.activar', $siguiente))->assertSessionHasNoErrors();
    $this->patch(route('periodos.cerrar', $siguiente), ['confirmar_cierre' => 1])->assertSessionHasNoErrors();
    expect($larga->fresh()->estado)->toBe('cerrada');
});

test('los borradores no se asignan a proyectos y los periodos cerrados bloquean entregas y revisiones', function () {
    $d = escenarioFirmas();
    $entrega = entregaFirmaPrueba($d);
    $d['guia']->update(['estado' => 'borrador']);
    $this->actingAs($d['lider'])->post(route('proyectos.guardar'), ['guia_integradora_id' => $d['guia']->id, 'equipo_id' => $d['equipo']->id, 'titulo' => 'Intento'])->assertSessionHasErrors('ciclo');
    $d['guia']->update(['estado' => 'publicada']);
    $d['guia']->periodo->update(['estado' => 'cerrado']);
    $this->actingAs($d['docente'])->put(route('docente-materia.revisiones.guardar', $entrega), ['resultado' => 'correccion', 'calificacion' => 7])->assertSessionHasErrors('ciclo');
    $this->actingAs($d['alumno'])->post(route('estudiante.entregas.guardar', $d['apartado']))->assertSessionHasErrors('ciclo');
    $this->get(route('estudiante.entregas'))->assertOk();
});
