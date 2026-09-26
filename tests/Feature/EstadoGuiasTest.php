<?php

use App\Models\CierreProyecto;
use App\Models\DocumentoFinal;
use App\Models\Entrega;
use App\Models\Equipo;
use App\Models\GrupoAcademico;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\User;
use App\Servicios\DocumentosGuias;
use App\Servicios\FirmasDocentes;
use Database\Seeders\CierresDemostracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function prepararEstadoGuias($test): User
{
    Storage::fake('local');
    $mock = Mockery::mock(DocumentosGuias::class)->makePartial();
    $mock->shouldReceive('pdf')->twice()->andReturn('%PDF DEMO');
    app()->instance(DocumentosGuias::class, $mock);
    $test->seed(CierresDemostracionSeeder::class);

    return User::where('matricula', 'DEMO-LIDER-01')->firstOrFail();
}

test('panel muestra el estado de todas las guías y sus equipos sin depender de casos o comentarios', function () {
    $lider = prepararEstadoGuias($this);
    $conteos = [CierreProyecto::count(), DocumentoFinal::count(), DB::table('decisiones_cierre')->count()];
    $this->actingAs($lider)->get(route('docente-lider.estado-guias'))->assertOk()
        ->assertViewHas('resumen', fn ($r) => $r === ['total' => 7, 'finalizadas' => 2, 'decision' => 3, 'prorrogas' => 1, 'listas_pdf' => 0, 'cerradas_pendientes' => 1])
        ->assertViewHas('guias', fn ($g) => $g->count() === 3)
        ->assertSee('Prórroga activa')->assertSee('Prórroga vencida')->assertSee('Finalizada con PDF')
        ->assertSee('Cerrada con pendientes')->assertSee('Falta entrega del equipo.')
        ->assertSee('Falta aprobación firmada de Elena Rivera')
        ->assertDontSee('data:image/png;base64', false)->assertHeader('Cache-Control', 'no-store, private');
    expect([CierreProyecto::count(), DocumentoFinal::count(), DB::table('decisiones_cierre')->count()])->toBe($conteos);
    $this->get(route('dashboard'))->assertOk()->assertSee('Ver estado de las guías');
    $this->getJson(route('dashboard.buscar', ['buscar' => 'estado guias']))->assertOk()->assertJsonPath('resultados.0.id', 'estado-guias');
});

test('estado de guías exige líder activo y limita proyectos y filtros a sus propios grupos', function () {
    $lider = prepararEstadoGuias($this);
    foreach (['DEMO-COORD', 'DEMO-DOC-01', 'DEMO-CIERRE-ENTREGA-1'] as $matricula) {
        $this->actingAs(User::where('matricula', $matricula)->first())->get(route('docente-lider.estado-guias'))->assertForbidden();
    }
    $ajeno = User::factory()->create(['rol_id' => $lider->rol_id]);
    $grupo = GrupoAcademico::firstOrFail();
    $ajeno->asignaturasComoDocente()->attach($grupo->asignatura_lider_id, ['periodo_id' => $grupo->periodo_id, 'activo' => true]);
    $otroGrupo = $grupo->replicate();
    $otroGrupo->grupo = 'PRIVADO';
    $otroGrupo->nombre = 'Grupo privado';
    $otroGrupo->lider_proyecto_id = $ajeno->id;
    $otroGrupo->save();
    $equipo = Equipo::create(['grupo_academico_id' => $otroGrupo->id, 'nombre' => 'Equipo privado', 'estado' => 'activo']);
    Proyecto::create(['guia_integradora_id' => Proyecto::first()->guia_integradora_id, 'equipo_id' => $equipo->id, 'titulo' => 'PROYECTO PRIVADO', 'estado' => 'en_proceso']);
    $this->actingAs($lider)->get(route('docente-lider.estado-guias'))->assertOk()->assertDontSee('PROYECTO PRIVADO')->assertDontSee('Grupo privado')->assertViewHas('resumen', fn ($r) => $r['total'] === 7);
    $this->get(route('docente-lider.estado-guias', ['grupo' => $otroGrupo->id]))->assertForbidden();
    $periodo = Periodo::create(['nombre' => 'Periodo privado', 'fecha_inicio' => today(), 'fecha_fin' => today()->addMonths(3), 'estado' => 'borrador']);
    $this->get(route('docente-lider.estado-guias', ['periodo' => $periodo->id]))->assertForbidden();
    $lider->update(['estado' => 'inactivo']);
    $this->get(route('docente-lider.estado-guias'))->assertForbidden();
});

test('filtros muestran finalizados, prórrogas y búsqueda sin ejecutar ni exponer entradas', function () {
    $lider = prepararEstadoGuias($this);
    $this->actingAs($lider);
    $this->get(route('docente-lider.estado-guias', ['estado' => 'finalizada']))->assertOk()->assertViewHas('guias', fn ($g) => $g->flatten(1)->count() === 2)->assertDontSee('Cierre pendiente: faltan entregas');
    $this->get(route('docente-lider.estado-guias', ['estado' => 'prorroga_activa']))->assertOk()->assertSee('03 - Prórroga activa')->assertDontSee('04 - Prórroga vencida');
    $this->get(route('docente-lider.estado-guias', ['buscar' => 'falta firma']))->assertOk()->assertViewHas('resumen', fn ($r) => $r['total'] === 1);
    $this->get(route('docente-lider.estado-guias', ['buscar' => '<script>alert(1)</script>']))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    $this->get(route('docente-lider.estado-guias', ['estado' => 'inventado']))->assertSessionHasErrors('estado');
    $this->get(route('docente-lider.estado-guias', ['buscar' => str_repeat('a', 161)]))->assertSessionHasErrors('buscar');
    $this->get(route('docente-lider.estado-guias', ['grupo' => [1]]))->assertSessionHasErrors('grupo');
    $this->get(route('docente-lider.estado-guias', ['buscar' => "' OR 1=1 --"]))->assertOk()->assertViewHas('resumen', fn ($r) => $r['total'] === 0);
});

test('panel usa última versión y distingue listo para PDF de finalizado con archivo', function () {
    $lider = prepararEstadoGuias($this);
    $this->actingAs($lider);
    $proyecto = Proyecto::where('titulo', 'like', '02 -%')->firstOrFail();
    $codigo = $proyecto->entregas()->whereHas('apartado', fn ($q) => $q->where('requiere_codigo', true))->firstOrFail();
    app(FirmasDocentes::class)->guardarRevision($lider, $codigo, ['resultado' => 'aprobada', 'calificacion' => 9], true);
    $this->get(route('docente-lider.estado-guias', ['buscar' => '02 -']))->assertOk()->assertViewHas('resumen', fn ($r) => $r['listas_pdf'] === 1 && $r['finalizadas'] === 0)->assertSee('falta emitir y guardar el PDF final');
    $anterior = $proyecto->entregas()->whereHas('apartado', fn ($q) => $q->where('orden', 1))->firstOrFail();
    Entrega::create(['proyecto_id' => $proyecto->id, 'equipo_id' => $proyecto->equipo_id, 'apartado_guia_id' => $anterior->apartado_guia_id, 'entregado_por_id' => $anterior->entregado_por_id, 'version' => 2, 'estado' => 'enviada', 'entregado_en' => now()]);
    $this->get(route('docente-lider.estado-guias', ['buscar' => '02 -']))->assertOk()->assertViewHas('guias', function ($guias) {
        $fila = $guias->first()->first();

        return $fila['estado'] === 'necesita_prorroga' && $fila['apartados'][0]['version'] === 2 && $fila['apartados'][0]['firmadas'] === 0;
    })->assertSee('Entrega versión 2');
});

test('líder puede abrir la decisión de un cierre vencido sin esperar al scheduler y GET no lo registra', function () {
    $lider = prepararEstadoGuias($this);
    $proyecto = Proyecto::where('titulo', 'like', '01 -%')->firstOrFail();
    $proyecto->cierre()->delete();
    $conteo = CierreProyecto::count();
    $this->actingAs($lider)->get(route('docente-lider.estado-guias', ['buscar' => '01 -']))->assertOk()->assertSee('Requiere decisión de cierre');
    expect(CierreProyecto::count())->toBe($conteo);
    $this->actingAs(User::where('matricula', 'DEMO-DOC-01')->first())->post(route('docente-lider.estado-guias.preparar-cierre', $proyecto))->assertForbidden();
    $this->actingAs($lider)->post(route('docente-lider.estado-guias.preparar-cierre', $proyecto))->assertRedirect(route('docente-lider.cierres.mostrar', $proyecto));
    expect(CierreProyecto::count())->toBe($conteo + 1);
});
