<?php

use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Equipo;
use App\Models\FirmaApartadoGuia;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function escenarioFlujosRoles(): array
{
    $roles = collect(['docente_lider', 'docente_materia', 'estudiante'])->mapWithKeys(function ($name) {
        $role = Role::query()->firstOrCreate(['nombre' => $name], ['nombre_visible' => str($name)->replace('_', ' ')->title(), 'descripcion' => 'Prueba']);
        return [$name => $role];
    });
    $period = Periodo::query()->create(['nombre' => 'Periodo de prueba', 'fecha_inicio' => now()->subMonth(), 'fecha_fin' => now()->addMonths(2), 'estado' => 'activo']);
    $career = Carrera::query()->create(['nombre' => 'Tecnologías de la Información', 'clave' => 'TI-PR', 'estado' => 'activa']);
    $subject = Asignatura::query()->create(['carrera_id' => $career->id, 'nombre' => 'Integradora', 'clave' => 'TI-INT-5', 'grado' => 5, 'estado' => 'activo']);
    $leader = User::factory()->create(['rol_id' => $roles['docente_lider']->id, 'carrera_id' => $career->id]);
    $matter = User::factory()->create(['rol_id' => $roles['docente_materia']->id, 'carrera_id' => $career->id]);
    $studentA = User::factory()->create(['rol_id' => $roles['estudiante']->id, 'carrera_id' => $career->id]);
    $studentB = User::factory()->create(['rol_id' => $roles['estudiante']->id, 'carrera_id' => $career->id]);
    $matter->asignaturasComoDocente()->attach($subject->id, ['periodo_id' => $period->id, 'activo' => true]);
    $group = GrupoAcademico::query()->create([
        'periodo_id' => $period->id, 'carrera_id' => $career->id, 'lider_proyecto_id' => $leader->id,
        'docente_materia_lider_id' => $matter->id,
        'asignatura_lider_id' => $subject->id, 'nombre' => '5A', 'grado' => 5, 'grupo' => 'A',
    ]);
    $studentA->update(['grupo_academico_id' => $group->id]);
    $studentB->update(['grupo_academico_id' => $group->id]);
    $team = Equipo::query()->create(['grupo_academico_id' => $group->id, 'numero' => 1, 'nombre' => 'Equipo 1', 'estado' => 'activo']);
    $team->integrantes()->attach([$studentA->id => ['activo' => true], $studentB->id => ['activo' => true]]);
    $guide = GuiaIntegradora::query()->create([
        'periodo_id' => $period->id, 'asignatura_id' => $subject->id, 'creado_por' => $leader->id,
        'nombre' => 'Guía de prueba', 'cuatrimestre' => 5, 'version' => '1.0', 'estado' => 'publicada',
    ]);
    $section = ApartadoGuia::query()->create([
        'guia_integradora_id' => $guide->id, 'orden' => 1, 'titulo' => 'Documento',
        'fecha_limite' => now()->addWeek(), 'ponderacion' => 50, 'requiere_documento' => true, 'requiere_codigo' => false,
    ]);
    $codeSection = ApartadoGuia::query()->create([
        'guia_integradora_id' => $guide->id, 'orden' => 2, 'titulo' => 'Código',
        'fecha_limite' => now()->addWeek(), 'ponderacion' => 50, 'requiere_documento' => false, 'requiere_codigo' => true,
    ]);
    $project = Proyecto::query()->create(['guia_integradora_id' => $guide->id, 'equipo_id' => $team->id, 'titulo' => 'Proyecto', 'estado' => 'en_proceso']);
    $project->docentes()->attach($matter->id, ['tipo_participacion' => 'evaluador', 'activo' => true]);
    FirmaApartadoGuia::query()->create(['apartado_guia_id' => $section->id, 'docente_id' => $matter->id, 'orden' => 1, 'etiqueta' => 'Evaluador', 'requerida' => true]);
    FirmaApartadoGuia::query()->create(['apartado_guia_id' => $codeSection->id, 'docente_id' => $matter->id, 'orden' => 1, 'etiqueta' => 'Materia líder', 'requerida' => true]);

    return compact('leader', 'matter', 'studentA', 'studentB', 'team', 'project', 'section', 'codeSection');
}

test('integrantes del equipo comparten entregas y conservan autoria por version', function () {
    Storage::fake('local');
    $data = escenarioFlujosRoles();

    $this->actingAs($data['studentA'])->post(route('estudiante.entregas.guardar', $data['section']), [
        'archivos' => [UploadedFile::fake()->create('reporte.pdf', 50), UploadedFile::fake()->create('anexo.zip', 80)],
    ])->assertRedirect();
    $this->actingAs($data['studentB'])->post(route('estudiante.entregas.guardar', $data['section']), [
        'archivos' => [UploadedFile::fake()->create('reporte-v2.pdf', 60)],
    ])->assertRedirect();

    $this->assertDatabaseHas('entregas', ['proyecto_id' => $data['project']->id, 'version' => 1, 'entregado_por_id' => $data['studentA']->id]);
    $this->assertDatabaseHas('entregas', ['proyecto_id' => $data['project']->id, 'version' => 2, 'entregado_por_id' => $data['studentB']->id]);
    expect(\App\Models\ArchivoEntrega::query()->count())->toBe(3);
});

test('codigo pertenece al docente de la materia lider y acepta repositorio y comprimido', function () {
    Storage::fake('local');
    $data = escenarioFlujosRoles();

    $this->actingAs($data['studentA'])->post(route('estudiante.codigo.guardar'), [
        'repositorio_url' => 'https://github.com/ejemplo/proyecto',
        'version' => 'v1.0.0',
        'archivos' => [UploadedFile::fake()->create('fuentes.zip', 100)],
    ])->assertRedirect();

    $this->actingAs($data['matter'])->get(route('docente-materia.principal'))
        ->assertOk()->assertSee('https://github.com/ejemplo/proyecto')->assertSee('fuentes.zip');
    $this->actingAs($data['leader'])->get(route('docente-materia.principal'))->assertForbidden();
});

test('docente de materia revisa solo el apartado y proyecto asignados', function () {
    Storage::fake('local');
    $data = escenarioFlujosRoles();
    $this->actingAs($data['studentA'])->post(route('estudiante.entregas.guardar', $data['section']), [
        'archivos' => [UploadedFile::fake()->create('avance.pdf', 40)],
    ])->assertRedirect();
    $delivery = \App\Models\Entrega::query()->firstOrFail();

    $this->actingAs($data['matter'])->put(route('docente-materia.revisiones.guardar', $delivery), [
        'resultado' => 'correccion', 'calificacion' => 7.5, 'observaciones' => 'Agregar evidencia.',
    ])->assertRedirect();
    $this->actingAs($data['matter'])->post(route('docente-materia.comentarios.guardar', $delivery), [
        'comentario' => 'Revisar el comentario técnico.',
    ])->assertRedirect();

    $this->assertDatabaseHas('revisiones', ['entrega_id' => $delivery->id, 'revisor_id' => $data['matter']->id, 'resultado' => 'correccion']);
    $this->assertDatabaseHas('comentarios_revision', ['comentario' => 'Revisar el comentario técnico.']);
});
