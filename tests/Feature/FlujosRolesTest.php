<?php

use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\ComentarioRevision;
use App\Models\Entrega;
use App\Models\Equipo;
use App\Models\FirmaApartadoGuia;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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
    $leader->asignaturasComoDocente()->attach($subject->id, ['periodo_id' => $period->id, 'activo' => true]);
    $group = GrupoAcademico::query()->create([
        'periodo_id' => $period->id, 'carrera_id' => $career->id, 'lider_proyecto_id' => $leader->id,
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

test('el autor retira solo la última versión no revisada y los próximos envíos conservan la secuencia', function () {
    Storage::fake('local');
    $d = escenarioFlujosRoles();
    $enviar = route('estudiante.entregas.guardar', $d['section']);
    $this->actingAs($d['studentA'])->post($enviar, ['archivos' => [UploadedFile::fake()->create('v1.pdf')]])->assertSessionHasNoErrors();
    $this->post($enviar, ['archivos' => [UploadedFile::fake()->create('v2.pdf')]])->assertSessionHasNoErrors();
    $primera = Entrega::where('version', 1)->firstOrFail();
    $ultima = Entrega::where('version', 2)->firstOrFail();
    $this->get(route('estudiante.entregas'))->assertOk()->assertSee('Retirar última versión');
    $this->delete(route('estudiante.entregas.retirar', $primera), ['confirmar_eliminacion' => 1])->assertSessionHasErrors('entrega');
    $this->actingAs($d['studentB'])->delete(route('estudiante.entregas.retirar', $ultima), ['confirmar_eliminacion' => 1])->assertForbidden();
    $this->actingAs($d['studentA'])->delete(route('estudiante.entregas.retirar', $ultima), ['confirmar_eliminacion' => 1])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('entregas', ['id' => $ultima->id, 'estado' => 'retirada']);
    $this->assertDatabaseHas('auditoria_academica', ['registro_id' => $ultima->id, 'modulo' => 'entregas', 'accion' => 'retirar']);
    $this->get(route('estudiante.entregas'))->assertOk()->assertSee('Retirada');
    $this->actingAs($d['matter'])->get(route('docente-materia.revisiones'))->assertOk()->assertSee('Versión 1')->assertDontSee('Versión 2');
    $this->put(route('docente-materia.revisiones.guardar', $primera), ['resultado' => 'correccion', 'calificacion' => 7])
        ->assertSessionHasNoErrors();
    $this->actingAs($d['studentA'])->delete(route('estudiante.entregas.retirar', $primera), ['confirmar_eliminacion' => 1])->assertSessionHasErrors('entrega');
    $this->post($enviar, ['archivos' => [UploadedFile::fake()->create('v3.pdf')]])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('entregas', ['version' => 3, 'estado' => 'enviada']);
});

test('el autor edita y oculta un comentario de seguimiento en los dos módulos de revisión', function () {
    Storage::fake('local');
    $d = escenarioFlujosRoles();
    $this->actingAs($d['studentA'])->post(route('estudiante.entregas.guardar', $d['section']),
        ['archivos' => [UploadedFile::fake()->create('avance.pdf')]])->assertSessionHasNoErrors();
    $this->post(route('estudiante.codigo.guardar'), ['repositorio_url' => 'https://example.com/proyecto', 'version' => '1.0'])->assertSessionHasNoErrors();
    foreach ([
        [$d['matter'], $d['section'], 'docente-materia.revisiones.guardar', 'docente-materia.comentarios.modificar'],
        [$d['leader'], $d['codeSection'], 'docente-lider.codigo.revisar', 'docente-lider.codigo.comentarios.modificar'],
    ] as [$docente, $apartado, $rutaRevision, $rutaComentario]) {
        $entrega = Entrega::where('apartado_guia_id', $apartado->id)->firstOrFail();
        $this->actingAs($docente)->put(route($rutaRevision, $entrega), ['resultado' => 'correccion', 'calificacion' => 7])->assertSessionHasNoErrors();
        $revision = Revision::where('entrega_id', $entrega->id)->firstOrFail();
        $ajeno = ComentarioRevision::create(['revision_id' => $revision->id, 'autor_id' => $d['studentB']->id, 'comentario' => 'Ajeno', 'visible_estudiante' => true]);
        $this->patch(route($rutaComentario, [$entrega, $ajeno]), ['comentario' => 'Intento'])->assertForbidden();
        $nota = ComentarioRevision::create(['revision_id' => $revision->id, 'autor_id' => $docente->id, 'comentario' => 'Original', 'visible_estudiante' => true]);
        $ruta = route($rutaComentario, [$entrega, $nota]);
        $this->patch($ruta, ['comentario' => 'Texto corregido'])->assertSessionHasNoErrors();
        $this->delete($ruta, ['confirmar_eliminacion' => 1])->assertSessionHasNoErrors();
        $this->assertSoftDeleted($nota);
        $this->assertDatabaseHas('auditoria_academica', ['actor_id' => $docente->id, 'registro_id' => $nota->id, 'accion' => 'editar']);
        expect(json_decode(DB::table('auditoria_academica')->where('modulo', 'comentarios')->where('registro_id', $nota->id)
            ->where('accion', 'editar')->value('antes'), true)['comentario'])->toBe('Original');
    }
});

test('retirar la última entrega de código restaura el producto anterior y respeta el vencimiento', function () {
    $d = escenarioFlujosRoles();
    $envio = route('estudiante.codigo.guardar');
    $this->actingAs($d['studentA'])->post($envio, ['repositorio_url' => 'https://example.com/primera', 'version' => '1.0'])->assertSessionHasNoErrors();
    $this->post($envio, ['repositorio_url' => 'https://example.com/segunda', 'version' => '2.0'])->assertSessionHasNoErrors();
    [$primera, $ultima] = Entrega::where('apartado_guia_id', $d['codeSection']->id)->orderBy('version')->get();
    $this->delete(route('estudiante.entregas.retirar', $ultima), ['confirmar_eliminacion' => 1])->assertSessionHasNoErrors();
    $this->get(route('estudiante.codigo'))->assertOk()->assertSee('https://example.com/primera')->assertDontSee('https://example.com/segunda');
    $d['codeSection']->update(['fecha_limite' => now()->subMinute()]);
    $this->delete(route('estudiante.entregas.retirar', $primera), ['confirmar_eliminacion' => 1])->assertStatus(422);
    $this->assertModelExists($primera);
});

test('un líder edita, desactiva y elimina equipos y proyectos de su grupo con sus restricciones', function () {
    $d = escenarioFlujosRoles();
    $equipo = $d['team'];
    $proyecto = $d['project'];
    $guia = $proyecto->guiaIntegradora;
    $datosEquipo = ['nombre' => 'Equipo actualizado', 'estado' => 'inactivo'];
    $this->actingAs($d['matter'])->patch(route('equipos.actualizar', $equipo), $datosEquipo)->assertForbidden();
    $this->actingAs($d['leader'])->patch(route('equipos.actualizar', $equipo), $datosEquipo)->assertSessionHasNoErrors();
    $this->actingAs($d['studentA'])->get(route('estudiante.proyecto'))->assertOk()->assertSee('Equipo actualizado');
    $this->post(route('estudiante.entregas.guardar', $d['section']), [])->assertSessionHasErrors('ciclo');
    $this->actingAs($d['leader'])->patch(route('equipos.actualizar', $equipo), [...$datosEquipo, 'estado' => 'activo'])->assertSessionHasNoErrors();
    $this->delete(route('equipos.eliminar', $equipo), ['confirmar_eliminacion' => 1])->assertSessionHasErrors('eliminar');
    $alternativa = $guia->replicate();
    $alternativa->forceFill(['guia_origen_id' => $guia->id, 'version' => '2.0'])->save();
    $datosProyecto = ['titulo' => 'Proyecto corregido', 'descripcion' => 'Nueva descripción', 'guia_integradora_id' => $alternativa->id];
    $this->get(route('modulos.proyectos'))->assertOk()->assertSee('Editar proyecto');
    $this->patch(route('proyectos.actualizar', $proyecto), $datosProyecto)->assertSessionHasNoErrors();
    expect($proyecto->fresh()->guia_integradora_id)->toBe($alternativa->id);
    $proyecto->asignaturas()->attach($guia->asignatura_id, ['participa_evaluacion' => true]);
    $this->delete(route('proyectos.asignaturas.quitar'), ['proyecto_id' => $proyecto->id, 'asignatura_id' => $guia->asignatura_id])->assertSessionHasNoErrors();
    expect($proyecto->asignaturas()->exists())->toBeFalse();
    $this->delete(route('proyectos.eliminar', $proyecto), ['confirmar_eliminacion' => 1])->assertSessionHasNoErrors();
    $this->delete(route('equipos.eliminar', $equipo), ['confirmar_eliminacion' => 1])->assertSessionHasNoErrors();
    $this->assertModelMissing($proyecto)->assertModelMissing($equipo);
});

test('una guía con entregas conserva la versión asignada y la nueva versión copia solo su configuración', function () {
    Storage::fake('local');
    $d = escenarioFlujosRoles();
    $rol = Role::firstOrCreate(['nombre' => 'coordinacion'], ['nombre_visible' => 'Coordinación']);
    $coordinacion = User::factory()->create(['rol_id' => $rol->id]);
    $guia = $d['project']->guiaIntegradora;
    $this->actingAs($d['studentA'])->post(route('estudiante.entregas.guardar', $d['section']), [
        'archivos' => [UploadedFile::fake()->create('avance.pdf')],
    ])->assertSessionHasNoErrors();
    $this->actingAs($coordinacion)->patch(route('guias.borrador', $guia), ['confirmar_borrador' => 1])->assertSessionHasErrors('guia');
    $this->post(route('guias.versiones.guardar', $guia), ['version' => '1.0', 'motivo' => 'Revisión de contenido'])
        ->assertSessionHasErrors('version');
    $this->post(route('guias.versiones.guardar', $guia), ['version' => '2.0', 'motivo' => 'Revisión de contenido'])
        ->assertSessionHasNoErrors();
    $nueva = $guia->versiones()->firstOrFail();
    expect($nueva->estado)->toBe('borrador')->and($nueva->apartados()->count())->toBe(2)
        ->and($nueva->proyectos()->count())->toBe(0)->and($d['project']->fresh()->guia_integradora_id)->toBe($guia->id);
    $this->actingAs($d['leader'])->patch(route('proyectos.actualizar', $d['project']), [
        'titulo' => 'Título corregido', 'guia_integradora_id' => $guia->id,
    ])->assertSessionHasErrors('motivo');
    $this->patch(route('proyectos.actualizar', $d['project']), [
        'titulo' => 'Título corregido', 'guia_integradora_id' => $guia->id, 'motivo' => 'Error de captura',
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('auditoria_academica', ['modulo' => 'proyectos', 'accion' => 'editar',
        'actor_id' => $d['leader']->id, 'registro_id' => $d['project']->id]);
});

test('codigo pertenece al docente lider de la materia lider y acepta repositorio y comprimido', function () {
    Storage::fake('local');
    $data = escenarioFlujosRoles();

    $this->actingAs($data['studentA'])->post(route('estudiante.codigo.guardar'), [
        'repositorio_url' => 'https://github.com/ejemplo/proyecto',
        'version' => 'v1.0.0',
        'archivos' => [UploadedFile::fake()->create('fuentes.zip', 100)],
    ])->assertRedirect();

    $this->actingAs($data['leader'])->get(route('docente-lider.codigo'))
        ->assertOk()->assertSee('https://github.com/ejemplo/proyecto')->assertSee('fuentes.zip');
    $this->actingAs($data['matter'])->get(route('docente-lider.codigo'))->assertForbidden();
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

test('las calificaciones fuera de 0 a 10 o con mas de dos decimales se rechazan en ambos roles', function () {
    Storage::fake('local');
    $data = escenarioFlujosRoles();
    $this->actingAs($data['studentA'])->post(route('estudiante.entregas.guardar', $data['section']), [
        'archivos' => [UploadedFile::fake()->create('avance.pdf', 40)],
    ])->assertRedirect();
    $this->post(route('estudiante.codigo.guardar'), [
        'repositorio_url' => 'https://github.com/ejemplo/proyecto', 'version' => 'v1',
    ])->assertRedirect();

    $documento = \App\Models\Entrega::query()->where('apartado_guia_id', $data['section']->id)->firstOrFail();
    $codigo = \App\Models\Entrega::query()->where('apartado_guia_id', $data['codeSection']->id)->firstOrFail();
    foreach ([[$data['matter'], 'docente-materia.revisiones.guardar', $documento], [$data['leader'], 'docente-lider.codigo.revisar', $codigo]] as [$docente, $ruta, $entrega]) {
        foreach ([-0.01, 10.01, 7.555, 'texto'] as $invalida) {
            $this->actingAs($docente)->put(route($ruta, $entrega), [
                'resultado' => 'correccion', 'calificacion' => $invalida,
            ])->assertSessionHasErrors('calificacion');
        }
    }
    $this->assertDatabaseCount('revisiones', 0);
    $this->actingAs($data['matter'])->put(route('docente-materia.revisiones.guardar', $documento), [
        'resultado' => 'correccion', 'calificacion' => 7.25,
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('revisiones', ['entrega_id' => $documento->id, 'calificacion' => 7.25]);
});
