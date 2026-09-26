<?php

use App\Jobs\EnviarAvisoAsignacion;
use App\Jobs\EnviarAvisoCierre;
use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\AvisoAsignacion;
use App\Models\AvisoCierre;
use App\Models\Carrera;
use App\Models\CierreProyecto;
use App\Models\DocumentoFinal;
use App\Models\Entrega;
use App\Models\Equipo;
use App\Models\FirmaApartadoGuia;
use App\Models\FirmaDocente;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AsignacionAcademica;
use App\Notifications\CierrePendiente;
use App\Servicios\AvisosAsignaciones;
use App\Servicios\CicloAcademico;
use App\Servicios\CierresProyectos;
use App\Servicios\DocumentosGuias;
use App\Servicios\FirmasDocentes;
use App\Servicios\PlazosProyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function escenarioCierre(): array
{
    $periodo = Periodo::create(['nombre' => 'Ciclo terminado', 'fecha_inicio' => now()->subMonths(4), 'fecha_fin' => now()->subDay(), 'estado' => 'activo']);
    $carrera = Carrera::create(['nombre' => 'Tecnologías', 'clave' => 'TI', 'estado' => 'activa']);
    $materia = Asignatura::create(['nombre' => 'Integradora', 'grado' => 5, 'carrera_id' => $carrera->id]);
    $usuarios = collect(['coordinacion', 'docente_lider', 'docente_materia', 'estudiante'])->mapWithKeys(function ($nombre) use ($carrera) {
        $rol = Role::firstOrCreate(['nombre' => $nombre], ['nombre_visible' => $nombre]);

        return [$nombre => User::factory()->create(['rol_id' => $rol->id, 'carrera_id' => $carrera->id])];
    });
    $lider = $usuarios['docente_lider'];
    $docente = $usuarios['docente_materia'];
    $alumno = $usuarios['estudiante'];
    $coordinacion = $usuarios['coordinacion'];
    $lider->asignaturasComoDocente()->attach($materia->id, ['periodo_id' => $periodo->id, 'activo' => true]);
    $grupo = GrupoAcademico::create(['periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'lider_proyecto_id' => $lider->id, 'asignatura_lider_id' => $materia->id, 'grado' => 5, 'grupo' => 'A', 'nombre' => '5A']);
    $equipo = Equipo::create(['grupo_academico_id' => $grupo->id, 'nombre' => 'Equipo de cierre', 'estado' => 'activo']);
    $equipo->integrantes()->attach($alumno->id, ['activo' => true]);
    $guia = GuiaIntegradora::create(['periodo_id' => $periodo->id, 'asignatura_id' => $materia->id, 'nombre' => 'Guía de cierre', 'cuatrimestre' => 5, 'version' => '1', 'estado' => 'publicada']);
    $apartado = ApartadoGuia::create(['guia_integradora_id' => $guia->id, 'orden' => 1, 'titulo' => 'Protocolo', 'ponderacion' => 50, 'fecha_limite' => now()->subDays(2), 'requiere_documento' => true, 'requiere_codigo' => false]);
    $codigo = ApartadoGuia::create(['guia_integradora_id' => $guia->id, 'orden' => 2, 'titulo' => 'Código', 'ponderacion' => 50, 'fecha_limite' => now()->subDays(2), 'requiere_codigo' => true]);
    foreach ([$apartado, $codigo] as $a) {
        FirmaApartadoGuia::create(['apartado_guia_id' => $a->id, 'docente_id' => $a->requiere_codigo ? $lider->id : $docente->id, 'orden' => 1, 'etiqueta' => 'Evaluador', 'requerida' => true]);
    }
    $proyecto = Proyecto::create(['guia_integradora_id' => $guia->id, 'equipo_id' => $equipo->id, 'titulo' => 'Proyecto por resolver', 'estado' => 'en_proceso']);
    $proyecto->docentes()->attach($docente->id, ['activo' => true, 'tipo_participacion' => 'evaluador']);

    return compact('periodo', 'lider', 'docente', 'alumno', 'coordinacion', 'grupo', 'equipo', 'guia', 'apartado', 'codigo', 'proyecto');
}

function entregaCierre(array $d, ApartadoGuia $apartado): Entrega
{
    return Entrega::create(['proyecto_id' => $d['proyecto']->id, 'equipo_id' => $d['equipo']->id, 'apartado_guia_id' => $apartado->id, 'entregado_por_id' => $d['alumno']->id, 'version' => 1, 'estado' => 'enviada', 'entregado_en' => now()]);
}

function firmarCierre(User $docente, Entrega $entrega): void
{
    $im = imagecreatetruecolor(400, 100);
    imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
    imagestring($im, 5, 20, 40, 'FIRMA FICTICIA TEST', imagecolorallocate($im, 0, 0, 0));
    ob_start();
    imagepng($im);
    $png = ob_get_clean();
    imagedestroy($im);
    $normal = app(FirmasDocentes::class)->normalizar($png);
    FirmaDocente::firstOrCreate(['docente_id' => $docente->id], ['imagen' => $normal, 'sha256' => hash('sha256', $normal)]);
    app(FirmasDocentes::class)->guardarRevision($docente, $entrega, ['resultado' => 'aprobada', 'calificacion' => 9], true);
}

function datosProrroga(array $d, array $apartados): array
{
    return ['decision' => 'prorroga', 'ronda' => $d['proyecto']->cierre()->firstOrFail()->ronda, 'apartados' => $apartados, 'fecha_limite' => now()->addDays(3)->format('Y-m-d H:i:s'), 'motivo' => 'Dar tiempo para atender los pendientes'];
}

test('cierre distingue entregas y firmas, avisa solo al líder y deduplica los avisos', function () {
    Queue::fake();
    Notification::fake();
    $d = escenarioCierre();
    entregaCierre($d, $d['apartado']);
    $servicio = app(CierresProyectos::class);
    expect($servicio->sincronizar())->toBe(1)->and($servicio->sincronizar())->toBe(0);
    $caso = CierreProyecto::firstOrFail();
    expect(collect($caso->pendientes)->pluck('tipo')->all())->toBe(['firma', 'entrega']);
    Queue::assertPushed(EnviarAvisoCierre::class, 1);
    (new EnviarAvisoCierre(AvisoCierre::first()->id))->handle($servicio);
    Notification::assertSentTo($d['lider'], CierrePendiente::class, function ($correo) use ($d) {
        expect($correo->pendientes)->toHaveCount(2);
        expect($correo->toMail($d['lider'])->actionUrl)->toBe(route('docente-lider.cierres.mostrar', $d['proyecto']));

        return true;
    });
    Notification::assertNotSentTo($d['docente'], CierrePendiente::class);
    Notification::assertNotSentTo($d['alumno'], CierrePendiente::class);
    (new EnviarAvisoCierre(AvisoCierre::first()->id))->handle($servicio);
    Notification::assertCount(1);
});

test('solo el líder responsable decide y la vista escapa textos del historial', function () {
    $d = escenarioCierre();
    app(CierresProyectos::class)->registrar($d['proyecto']);
    $ajeno = User::factory()->create(['rol_id' => $d['lider']->rol_id]);
    foreach ([$d['alumno'], $d['docente'], $d['coordinacion'], $ajeno] as $usuario) {
        $this->actingAs($usuario)->get(route('docente-lider.cierres.mostrar', $d['proyecto']))->assertForbidden();
        $this->post(route('docente-lider.cierres.decidir', $d['proyecto']), datosProrroga($d, [$d['codigo']->id]))->assertForbidden();
    }
    $this->actingAs($d['lider'])->get(route('docente-lider.cierres'))->assertOk()->assertSee('Proyecto por resolver');
    $datos = [...datosProrroga($d, [$d['codigo']->id]), 'motivo' => '<script>alert(1)</script>'];
    $this->post(route('docente-lider.cierres.decidir', $d['proyecto']), $datos)->assertSessionHasNoErrors();
    $this->get(route('docente-lider.cierres.mostrar', $d['proyecto']))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('docente-lider.cierres'))->assertOk()->assertSee('Fecha de prórroga');
});

test('prórroga valida fecha, selección y ronda, bloquea formularios antiguos', function () {
    $d = escenarioCierre();
    app(CierresProyectos::class)->registrar($d['proyecto']);
    $this->actingAs($d['lider']);
    $datos = datosProrroga($d, [$d['codigo']->id]);
    foreach ([['fecha_limite' => now()->subMinute()->toDateTimeString()], ['apartados' => []], ['apartados' => [99999]], ['apartados' => [$d['codigo']->id, $d['codigo']->id]], ['decision' => 'inyectar'], ['motivo' => str_repeat('x', 2001)]] as $invalidos) {
        $this->post(route('docente-lider.cierres.decidir', $d['proyecto']), [...$datos, ...$invalidos])->assertSessionHasErrors();
    }
    $this->post(route('docente-lider.cierres.decidir', $d['proyecto']), $datos)->assertSessionHasNoErrors();
    $this->post(route('docente-lider.cierres.decidir', $d['proyecto']), $datos)->assertSessionHasErrors('cierre');
    expect(DB::table('decisiones_cierre')->count())->toBe(1);
});

test('prórroga reabre solo el apartado y equipo elegidos con periodo y guía cerrados', function () {
    Storage::fake('local');
    $d = escenarioCierre();
    app(CicloAcademico::class)->cerrar($d['periodo']);
    $fechaOriginal = $d['codigo']->fecha_limite->toDateTimeString();
    $this->actingAs($d['lider'])->post(route('docente-lider.cierres.decidir', $d['proyecto']), datosProrroga($d, [$d['codigo']->id]))->assertSessionHasNoErrors();
    expect($d['guia']->fresh()->estado)->toBe('cerrada')->and($d['periodo']->fresh()->estado)->toBe('cerrado')->and($d['codigo']->fresh()->fecha_limite->toDateTimeString())->toBe($fechaOriginal);
    $this->actingAs($d['alumno'])->post(route('estudiante.codigo.guardar'), ['version' => 'v2', 'repositorio_url' => 'https://github.com/equipo/proyecto'])->assertSessionHasNoErrors();
    $this->post(route('estudiante.entregas.guardar', $d['apartado']), ['archivos' => [UploadedFile::fake()->create('informe.pdf', 2, 'application/pdf')]])->assertSessionHasErrors('ciclo');
    $this->get(route('estudiante.codigo'))->assertOk()->assertSee('Enviar nueva versión');
    $otroEquipo = Equipo::create(['grupo_academico_id' => $d['grupo']->id, 'nombre' => 'Otro equipo', 'estado' => 'activo']);
    $otroProyecto = Proyecto::create(['guia_integradora_id' => $d['guia']->id, 'equipo_id' => $otroEquipo->id, 'titulo' => 'Otro proyecto', 'estado' => 'en_proceso']);
    expect(app(PlazosProyecto::class)->permite($otroProyecto, $d['codigo']))->toBeFalse();
    $this->travel(4)->days();
    $this->post(route('estudiante.codigo.guardar'), ['version' => 'v3', 'repositorio_url' => 'https://github.com/equipo/proyecto'])->assertSessionHasErrors('ciclo');
    Queue::fake();
    expect(app(CierresProyectos::class)->sincronizar())->toBe(2);
    expect($d['proyecto']->cierre()->first()->estado)->toBe('pendiente');
});

test('revisiones en prórroga aparecen aun con otro periodo activo conservando permisos de código', function () {
    $d = escenarioCierre();
    entregaCierre($d, $d['apartado']);
    $codigoEntrega = entregaCierre($d, $d['codigo']);
    app(CicloAcademico::class)->cerrar($d['periodo']);
    app(CierresProyectos::class)->decidir($d['lider'], $d['proyecto'], datosProrroga($d, [$d['codigo']->id, $d['apartado']->id]));
    Periodo::create(['nombre' => 'Nuevo ciclo', 'fecha_inicio' => now(), 'fecha_fin' => now()->addMonths(3), 'estado' => 'activo']);
    $this->actingAs($d['lider'])->get(route('docente-lider.codigo'))->assertOk()->assertSee('Proyecto por resolver')->assertSee('Disponible para revisión');
    $this->actingAs($d['docente'])->get(route('docente-materia.revisiones'))->assertOk()->assertSee('Proyecto por resolver')->assertDontSee('Código · Versión');
    $this->put(route('docente-materia.revisiones.guardar', $codigoEntrega), ['resultado' => 'correccion', 'calificacion' => 7])->assertForbidden();
    $this->actingAs($d['lider'])->put(route('docente-lider.codigo.revisar', $codigoEntrega), ['resultado' => 'correccion', 'calificacion' => 7])->assertSessionHasNoErrors();
});

test('cierre con pendientes conserva constancia, cancela avisos y bloquea entregas y PDF final', function () {
    Notification::fake();
    $d = escenarioCierre();
    app(CierresProyectos::class)->registrar($d['proyecto']);
    $this->actingAs($d['lider'])->post(route('docente-lider.cierres.decidir', $d['proyecto']), ['decision' => 'cerrar', 'ronda' => 1, 'motivo' => 'Cierre aprobado por líder', 'confirmar_cierre' => 1])->assertSessionHasNoErrors();
    expect($d['proyecto']->fresh()->estado)->toBe('cerrado_con_pendientes')->and(CierreProyecto::first()->pendientes)->toHaveCount(2)->and(DB::table('decisiones_cierre')->count())->toBe(1);
    (new EnviarAvisoCierre(AvisoCierre::first()->id))->handle(app(CierresProyectos::class));
    Notification::assertNothingSent();
    $this->actingAs($d['alumno'])->post(route('estudiante.codigo.guardar'), ['version' => 'v2', 'repositorio_url' => 'https://github.com/equipo/proyecto'])->assertSessionHasErrors('ciclo');
    $this->post(route('documentos.generar', $d['proyecto']))->assertSessionHasErrors('documento');
    expect(DocumentoFinal::count())->toBe(0);
});

test('plazo nuevo invalida solo firmas del apartado elegido y se conserva después del vencimiento', function () {
    $d = escenarioCierre();
    $entrega = entregaCierre($d, $d['apartado']);
    $codigo = entregaCierre($d, $d['codigo']);
    firmarCierre($d['docente'], $entrega);
    firmarCierre($d['lider'], $codigo);
    expect(app(DocumentosGuias::class)->datos($d['proyecto']->fresh(), false)['pendientes'])->toBeEmpty();
    app(CierresProyectos::class)->registrar($d['proyecto']);
    expect(CierreProyecto::first()->pendientes[0]['tipo'])->toBe('pdf');
    app(CierresProyectos::class)->decidir($d['lider'], $d['proyecto'], datosProrroga($d, [$d['codigo']->id]));
    $datos = app(DocumentosGuias::class)->datos($d['proyecto']->fresh(), false);
    expect($datos['filas'][$d['apartado']->id]['firmantes'][0]['revision'])->not->toBeNull()->and($datos['filas'][$d['codigo']->id]['firmantes'][0]['revision'])->toBeNull();
    firmarCierre($d['lider'], $codigo);
    $this->travel(4)->days();
    expect(app(DocumentosGuias::class)->datos($d['proyecto']->fresh(), false)['pendientes'])->toBeEmpty();
});

test('PDF guardado resuelve el cierre y conserva los mismos bytes al descargar', function () {
    $d = escenarioCierre();
    firmarCierre($d['docente'], entregaCierre($d, $d['apartado']));
    firmarCierre($d['lider'], entregaCierre($d, $d['codigo']));
    app(CierresProyectos::class)->registrar($d['proyecto']);
    $this->actingAs($d['lider'])->post(route('documentos.generar', $d['proyecto']))->assertSessionHasNoErrors();
    $pdf = DocumentoFinal::firstOrFail();
    expect(CierreProyecto::first()->estado)->toBe('completo')->and(AvisoCierre::first()->estado)->toBe('cancelado');
    $this->post(route('documentos.generar', $d['proyecto']))->assertSessionHasNoErrors();
    expect(DocumentoFinal::count())->toBe(1);
    $d['proyecto']->update(['titulo' => 'Nombre modificado']);
    $this->get(route('documentos.descargar', ['proyecto' => $d['proyecto'], 'documento' => $pdf]))->assertOk()->assertContent(base64_decode($pdf->pdf));
});

test('aviso pendiente no llega a un líder reemplazado y se envía al nuevo responsable', function () {
    Queue::fake();
    Notification::fake();
    $d = escenarioCierre();
    $servicio = app(CierresProyectos::class);
    $servicio->sincronizar();
    $viejo = AvisoCierre::firstOrFail();
    $nuevo = User::factory()->create(['rol_id' => $d['lider']->rol_id]);
    $nuevo->asignaturasComoDocente()->attach($d['grupo']->asignatura_lider_id, ['periodo_id' => $d['periodo']->id, 'activo' => true]);
    $d['grupo']->update(['lider_proyecto_id' => $nuevo->id]);
    (new EnviarAvisoCierre($viejo->id))->handle($servicio);
    Notification::assertNothingSent();
    expect($servicio->sincronizar())->toBe(1);
    (new EnviarAvisoCierre(AvisoCierre::latest('id')->first()->id))->handle($servicio);
    Notification::assertSentTo($nuevo, CierrePendiente::class);
    Notification::assertNotSentTo($d['lider'], CierrePendiente::class);
});

test('avisos de alumnos en prórroga usan solo apartados abiertos y su fecha efectiva', function () {
    Queue::fake();
    $d = escenarioCierre();
    app(CicloAcademico::class)->cerrar($d['periodo']);
    $datos = datosProrroga($d, [$d['codigo']->id]);
    app(CierresProyectos::class)->decidir($d['lider'], $d['proyecto'], $datos);
    app(AvisosAsignaciones::class)->sincronizar();
    expect(AvisoAsignacion::where('apartado_guia_id', $d['apartado']->id)->count())->toBe(0)->and(AvisoAsignacion::where('apartado_guia_id', $d['codigo']->id)->count())->toBeGreaterThan(0);
    $aviso = AvisoAsignacion::firstOrFail();
    Notification::fake();
    (new EnviarAvisoAsignacion($aviso->id))->handle(app(AvisosAsignaciones::class));
    Notification::assertSentTo($d['alumno'], AsignacionAcademica::class, function ($correo) use ($d, $datos) {
        expect(implode(' ', $correo->toMail($d['alumno'])->introLines))->toContain(Carbon::parse($datos['fecha_limite'])->format('d/m/Y H:i'));

        return true;
    });
});

test('alumno con otro equipo actual puede atender la prórroga anterior sin cambiar de proyecto por accidente', function () {
    $d = escenarioCierre();
    app(CicloAcademico::class)->cerrar($d['periodo']);
    app(CierresProyectos::class)->decidir($d['lider'], $d['proyecto'], datosProrroga($d, [$d['codigo']->id]));
    $otroEquipo = Equipo::create(['grupo_academico_id' => $d['grupo']->id, 'nombre' => 'Equipo nuevo', 'estado' => 'activo']);
    $otroEquipo->integrantes()->attach($d['alumno']->id, ['activo' => true]);
    $otroProyecto = Proyecto::create(['guia_integradora_id' => $d['guia']->id, 'equipo_id' => $otroEquipo->id, 'titulo' => 'Proyecto nuevo', 'estado' => 'en_proceso']);
    $this->actingAs($d['alumno'])->get(route('estudiante.codigo', ['proyecto_contexto' => $d['proyecto']->id]))->assertOk()->assertSee('Proyecto y periodo')->assertSee('name="proyecto_contexto" value="'.$d['proyecto']->id.'"', false)->assertSee('proyecto_contexto='.$d['proyecto']->id);
    $datos = ['version' => 'v2', 'repositorio_url' => 'https://github.com/equipo/proyecto'];
    $this->post(route('estudiante.codigo.guardar'), [...$datos, 'proyecto_contexto' => $d['proyecto']->id])->assertSessionHasNoErrors();
    expect(Entrega::where('proyecto_id', $d['proyecto']->id)->count())->toBe(1)->and(Entrega::where('proyecto_id', $otroProyecto->id)->count())->toBe(0);
    $ajeno = User::factory()->create(['rol_id' => $d['alumno']->rol_id]);
    $this->actingAs($ajeno)->get(route('estudiante.codigo', ['proyecto_contexto' => $d['proyecto']->id]))->assertForbidden();
    $this->post(route('estudiante.codigo.guardar'), [...$datos, 'proyecto_contexto' => $d['proyecto']->id])->assertForbidden();
});
