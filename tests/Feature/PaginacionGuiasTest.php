<?php

use App\Models\DocumentoFinal;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\User;
use App\Servicios\DocumentosGuias;
use Database\Seeders\PaginacionGuiasDemostracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function prepararPaginacionGuias($test): User
{
    Storage::fake('local');
    Queue::fake();
    Notification::fake();
    $mock = Mockery::mock(DocumentosGuias::class)->makePartial();
    $mock->shouldReceive('pdf')->times(4)->andReturn('%PDF DEMO PAGINACION');
    app()->instance(DocumentosGuias::class, $mock);
    $test->seed(PaginacionGuiasDemostracionSeeder::class);

    return User::where('matricula', 'DEMO-LIDER-01')->firstOrFail();
}

test('paginación reparte proyectos sin duplicarlos y conserva filtros y resumen global', function () {
    $lider = prepararPaginacionGuias($this);
    $periodo = Periodo::where('nombre', PaginacionGuiasDemostracionSeeder::PERIODO)->firstOrFail();
    $this->actingAs($lider);
    $ids = [];
    for ($page = 1; $page <= 3; $page++) {
        $respuesta = $this->get(route('docente-lider.estado-guias', ['periodo' => $periodo->id, 'page' => $page]));
        $respuesta->assertOk()->assertViewHas('resumen', fn ($r) => $r === ['total' => 28, 'finalizadas' => 4, 'decision' => 12, 'prorrogas' => 4, 'listas_pdf' => 4, 'cerradas_pendientes' => 4]);
        $paginacion = $respuesta->viewData('paginacion');
        expect($paginacion->total())->toBe(28)->and($paginacion->currentPage())->toBe($page)->and($paginacion->count())->toBe($page === 3 ? 8 : 10);
        expect($paginacion->url(2))->toContain('periodo='.$periodo->id)->toContain('por_pagina=10')->toContain('page=2');
        array_push($ids, ...$paginacion->getCollection()->pluck('proyecto.id')->all());
    }
    expect(count(array_unique($ids)))->toBe(28);
    $this->get(route('docente-lider.estado-guias', ['por_pagina' => 20, 'page' => 2]))->assertOk()->assertViewHas('paginacion', fn ($p) => $p->count() === 8 && $p->lastPage() === 2);
    $this->get(route('docente-lider.estado-guias', ['page' => 999]))->assertOk()->assertViewHas('paginacion', fn ($p) => $p->currentPage() === 3);
    $this->get(route('docente-lider.estado-guias', ['estado' => 'lista_pdf', 'page' => 3]))->assertOk()->assertViewHas('paginacion', fn ($p) => $p->total() === 4 && $p->currentPage() === 1)->assertViewHas('resumen', fn ($r) => $r['total'] === 28);
    $this->get(route('docente-lider.estado-guias', ['page' => 0]))->assertSessionHasErrors('page');
    $this->get(route('docente-lider.estado-guias', ['por_pagina' => 10000]))->assertSessionHasErrors('por_pagina');
});

test('formato ofrece regreso interno conservando página y filtros y descarta rutas ajenas', function () {
    $lider = prepararPaginacionGuias($this);
    $proyecto = Proyecto::firstOrFail();
    $filtros = ['periodo' => $proyecto->guiaIntegradora->periodo_id, 'grupo' => $proyecto->equipo->grupo_academico_id, 'buscar' => 'Historial', 'estado' => 'necesita_prorroga', 'page' => 2, 'por_pagina' => 10];
    $respuesta = $this->actingAs($lider)->get(route('documentos.mostrar', ['proyecto' => $proyecto, 'origen' => 'docente-lider.estado-guias', 'contexto' => $filtros]));
    $respuesta->assertOk()->assertSee('Volver al estado de las guías');
    $url = $respuesta->viewData('regreso')['url'];
    parse_str(parse_url($url, PHP_URL_QUERY), $consulta);
    expect($consulta)->toEqual($filtros)->and(strtok($url, '?'))->toBe(route('docente-lider.estado-guias'));
    foreach (['https://externo.invalid', '//externo.invalid', 'javascript:alert(1)', 'usuarios.guardar', ['x']] as $origen) {
        $this->get(route('documentos.mostrar', ['proyecto' => $proyecto, 'origen' => $origen, 'contexto' => ['page' => [1]]]))
            ->assertOk()->assertViewHas('regreso', fn ($r) => $r['url'] === route('docente-lider.estado-guias'));
    }
    $this->get(route('documentos.mostrar', ['proyecto' => $proyecto, 'origen' => 'docente-lider.cierres.mostrar']))->assertOk()
        ->assertViewHas('regreso', fn ($r) => $r['url'] === route('docente-lider.cierres.mostrar', $proyecto));
    $alumno = $proyecto->equipo->integrantes->first();
    $this->actingAs($alumno)->get(route('documentos.mostrar', $proyecto))->assertOk()
        ->assertViewHas('regreso', fn ($r) => $r['url'] === route('estudiante.proyecto', ['proyecto_contexto' => $proyecto->id]));
    $docente = User::where('matricula', 'DEMO-DOC-01')->firstOrFail();
    $this->actingAs($docente)->get(route('documentos.mostrar', $proyecto))->assertOk()->assertSee('Volver a revisiones');
    $this->actingAs(User::where('matricula', 'DEMO-COORD')->firstOrFail())->get(route('documentos.mostrar', $proyecto))->assertOk()->assertSee('Volver a proyectos');
});

test('historial de paginación conserva PDF originales y decisiones al repetir carga sin enviar avisos', function () {
    $activo = Periodo::create(['nombre' => 'Periodo existente', 'fecha_inicio' => today(), 'fecha_fin' => today()->addMonths(3), 'estado' => 'activo']);
    $lider = prepararPaginacionGuias($this);
    $pdfs = DocumentoFinal::all()->mapWithKeys(fn ($d) => [$d->id => hash('sha256', base64_decode($d->pdf))])->all();
    $lider->update(['contrasena' => 'contraseña modificada']);
    $clave = $lider->getRawOriginal('contrasena');
    $this->seed(PaginacionGuiasDemostracionSeeder::class);
    expect(Proyecto::count())->toBe(28)->and(User::count())->toBe(31)->and(DocumentoFinal::count())->toBe(4)
        ->and($activo->fresh()->estado)->toBe('activo')->and($lider->fresh()->getRawOriginal('contrasena'))->toBe($clave);
    $this->actingAs($lider);
    foreach (DocumentoFinal::all() as $pdf) {
        expect(hash('sha256', base64_decode($pdf->pdf)))->toBe($pdfs[$pdf->id]);
        $this->get(route('documentos.descargar', ['proyecto' => $pdf->proyecto_id, 'documento' => $pdf]))->assertOk()->assertContent(base64_decode($pdf->pdf));
    }
    Notification::assertNothingSent();
    Queue::assertNothingPushed();
});
