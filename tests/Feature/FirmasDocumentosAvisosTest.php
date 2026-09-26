<?php

use App\Jobs\EnviarAvisoAsignacion;
use App\Models\ApartadoGuia;
use App\Models\ArchivoEntrega;
use App\Models\Asignatura;
use App\Models\AvisoAsignacion;
use App\Models\Carrera;
use App\Models\DocumentoFinal;
use App\Models\Entrega;
use App\Models\Equipo;
use App\Models\FirmaApartadoGuia;
use App\Models\FirmaDocente;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\ProductoCodigo;
use App\Models\Proyecto;
use App\Models\Revision;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AsignacionAcademica;
use App\Servicios\AvisosAsignaciones;
use App\Servicios\DocumentosGuias;
use App\Servicios\FirmasDocentes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function escenarioFirmas(): array
{
    $roles = collect(['coordinacion', 'docente_lider', 'docente_materia', 'estudiante'])->mapWithKeys(fn ($rol) => [$rol => Role::firstOrCreate(['nombre' => $rol], ['nombre_visible' => $rol])]);
    $periodo = Periodo::create(['nombre' => 'Periodo', 'fecha_inicio' => now()->subMonth(), 'fecha_fin' => now()->addMonths(2), 'estado' => 'activo']);
    $carrera = Carrera::create(['nombre' => 'Tecnologías', 'clave' => 'TI', 'estado' => 'activa']);
    $materia = Asignatura::create(['carrera_id' => $carrera->id, 'nombre' => 'Integradora', 'grado' => 5]);
    $usuarios = $roles->map(fn ($rol) => User::factory()->create(['rol_id' => $rol->id, 'carrera_id' => $carrera->id]));
    $lider = $usuarios['docente_lider'];
    $docente = $usuarios['docente_materia'];
    $alumno = $usuarios['estudiante'];
    $coordinacion = $usuarios['coordinacion'];
    $lider->asignaturasComoDocente()->attach($materia->id, ['periodo_id' => $periodo->id, 'activo' => true]);
    $grupo = GrupoAcademico::create(['periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'lider_proyecto_id' => $lider->id, 'asignatura_lider_id' => $materia->id, 'grado' => 5, 'grupo' => 'A', 'nombre' => '5A']);
    $equipo = Equipo::create(['grupo_academico_id' => $grupo->id, 'nombre' => 'Equipo 1', 'estado' => 'activo']);
    $equipo->integrantes()->attach($alumno->id, ['activo' => true]);
    $guia = GuiaIntegradora::create(['periodo_id' => $periodo->id, 'asignatura_id' => $materia->id, 'nombre' => 'Guía', 'cuatrimestre' => 5, 'estado' => 'publicada', 'version' => '1']);
    $apartado = ApartadoGuia::create(['guia_integradora_id' => $guia->id, 'orden' => 1, 'titulo' => 'Protocolo', 'descripcion' => 'Objetivos y justificación', 'fecha_limite' => now()->addDays(10), 'ponderacion' => 100, 'requiere_documento' => true, 'requiere_codigo' => false]);
    $proyecto = Proyecto::create(['guia_integradora_id' => $guia->id, 'equipo_id' => $equipo->id, 'titulo' => 'Aplicación', 'estado' => 'en_proceso']);
    $proyecto->docentes()->attach($docente->id, ['activo' => true, 'tipo_participacion' => 'evaluador']);
    FirmaApartadoGuia::create(['apartado_guia_id' => $apartado->id, 'docente_id' => $docente->id, 'orden' => 1, 'etiqueta' => 'Evaluador', 'requerida' => true]);

    return compact('lider', 'docente', 'alumno', 'coordinacion', 'equipo', 'guia', 'apartado', 'proyecto');
}

function imagenFirmaPrueba(): string
{
    $imagen = imagecreatetruecolor(400, 100);
    imagefill($imagen, 0, 0, imagecolorallocate($imagen, 255, 255, 255));
    imagestring($imagen, 5, 30, 40, 'Firma de prueba', imagecolorallocate($imagen, 0, 0, 0));
    ob_start();
    imagepng($imagen);
    $png = ob_get_clean();
    imagedestroy($imagen);

    return $png;
}

function entregaFirmaPrueba(array $d, int $version = 1): Entrega
{
    return Entrega::create(['proyecto_id' => $d['proyecto']->id, 'equipo_id' => $d['equipo']->id, 'apartado_guia_id' => $d['apartado']->id, 'entregado_por_id' => $d['alumno']->id, 'version' => $version, 'estado' => 'enviada', 'entregado_en' => now()]);
}

test('firma exige rol y contraseña, rechaza SVG y queda cifrada sin exponer original', function () {
    $d = escenarioFirmas();
    $this->actingAs($d['alumno'])->get(route('docente.firma'))->assertForbidden();
    $this->actingAs($d['docente'])->post(route('docente.firma.guardar'), ['contrasena_actual' => 'incorrecta', 'firma_dibujada' => 'data:image/png;base64,'.base64_encode(imagenFirmaPrueba())])->assertSessionHasErrors('contrasena_actual');
    expect(session()->getOldInput('contrasena_actual'))->toBeNull();
    $this->post(route('docente.firma.guardar'), ['contrasena_actual' => 'password', 'firma' => UploadedFile::fake()->createWithContent('firma.svg', '<svg onload="alert(1)"></svg>')])->assertSessionHasErrors('firma');
    $this->post(route('docente.firma.guardar'), ['contrasena_actual' => 'password', 'firma_dibujada' => 'data:image/png;base64,'.base64_encode(imagenFirmaPrueba())])->assertSessionHasNoErrors();
    $firma = FirmaDocente::firstOrFail();
    expect(DB::table('firmas_docentes')->value('imagen'))->not->toBe($firma->imagen);
    $this->get(route('docente.firma'))->assertOk()->assertDontSee($firma->imagen)->assertHeader('Cache-Control', 'no-store, private');
});

test('aprobacion requiere autorización y firma de esa version y se revoca al corregir', function () {
    $d = escenarioFirmas();
    $entrega = entregaFirmaPrueba($d);
    $datos = ['resultado' => 'aprobada', 'calificacion' => 9];
    $this->actingAs($d['docente'])->put(route('docente-materia.revisiones.guardar', $entrega), $datos)->assertSessionHasErrors('autorizar_firma');
    $png = app(FirmasDocentes::class)->normalizar(imagenFirmaPrueba());
    FirmaDocente::create(['docente_id' => $d['docente']->id, 'imagen' => $png, 'sha256' => hash('sha256', $png)]);
    $this->put(route('docente-materia.revisiones.guardar', $entrega), [...$datos, 'autorizar_firma' => 1])->assertSessionHasNoErrors();
    $revision = Revision::firstOrFail();
    expect($revision->firma_imagen)->toBe($png)->and(DB::table('revisiones')->value('firma_imagen'))->not->toBe($png);
    FirmaDocente::first()->update(['imagen' => base64_encode('otra imagen')]);
    expect($revision->fresh()->firma_imagen)->toBe($png);
    $this->put(route('docente-materia.revisiones.guardar', $entrega), ['resultado' => 'correccion', 'calificacion' => 7])->assertSessionHasNoErrors();
    expect($revision->fresh()->firma_imagen)->toBeNull();
    entregaFirmaPrueba($d, 2);
    $this->put(route('docente-materia.revisiones.guardar', $entrega), ['resultado' => 'correccion', 'calificacion' => 7])->assertStatus(409);
});

test('PDF final requiere firmas, queda cifrado e inmutable y no acepta acceso de otro equipo', function () {
    $d = escenarioFirmas();
    $entrega = entregaFirmaPrueba($d);
    $this->actingAs($d['alumno'])->post(route('documentos.generar', $d['proyecto']))->assertSessionHasErrors('documento');
    $png = app(FirmasDocentes::class)->normalizar(imagenFirmaPrueba());
    FirmaDocente::create(['docente_id' => $d['docente']->id, 'imagen' => $png, 'sha256' => hash('sha256', $png)]);
    app(FirmasDocentes::class)->guardarRevision($d['docente'], $entrega, ['resultado' => 'aprobada', 'calificacion' => 9], true);
    $this->post(route('documentos.generar', $d['proyecto']))->assertSessionHasNoErrors()->assertRedirect();
    $documento = DocumentoFinal::firstOrFail();
    $response = $this->get(route('documentos.descargar', ['proyecto' => $d['proyecto'], 'documento' => $documento]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF')->and(DB::table('documentos_finales')->value('pdf'))->not->toBe($documento->pdf);
    $bytesEmitidos = $response->getContent();
    $this->post(route('documentos.generar', $d['proyecto']))->assertRedirect();
    expect(DocumentoFinal::count())->toBe(1);
    $hash = $documento->sha256;
    $d['apartado']->update(['descripcion' => 'Requisitos cambiados después de la aprobación']);
    $this->post(route('documentos.generar', $d['proyecto']))->assertSessionHasErrors('documento');
    entregaFirmaPrueba($d, 2);
    $this->post(route('documentos.generar', $d['proyecto']))->assertSessionHasErrors('documento');
    $d['proyecto']->update(['titulo' => 'Título cambiado después de emitir']);
    $d['docente']->update(['nombre' => 'Nombre actualizado']);
    $d['guia']->update(['objetivo_aprendizaje' => 'Objetivo actualizado']);
    $this->partialMock(DocumentosGuias::class, fn ($mock) => $mock->shouldNotReceive('pdf'));
    $descargaHistorica = $this->get(route('documentos.descargar', ['proyecto' => $d['proyecto'], 'documento' => $documento]))->assertOk();
    expect($descargaHistorica->getContent())->toBe($bytesEmitidos);
    expect(fn () => $documento->update(['pdf' => base64_encode('sobrescritura')]))->toThrow(LogicException::class);
    expect(DocumentoFinal::first()->sha256)->toBe($hash);
    $ajeno = User::factory()->create(['rol_id' => $d['alumno']->rol_id]);
    $this->actingAs($ajeno)->get(route('documentos.preview', $d['proyecto']))->assertForbidden();
    $this->get(route('documentos.descargar', ['proyecto' => $d['proyecto'], 'documento' => $documento]))->assertForbidden();
});

test('producto tecnico no exige URL de demostracion y solo lo firma el docente con acceso al repositorio', function () {
    $d = escenarioFirmas();
    $d['apartado']->update(['requiere_codigo' => true]);
    $this->actingAs($d['alumno'])->post(route('estudiante.codigo.guardar'), ['repositorio_url' => 'https://github.com/equipo/trabajo', 'version' => '1'])->assertSessionHasNoErrors();
    expect(ProductoCodigo::first()->demostracion_url)->toBeNull();
    $entrega = Entrega::firstOrFail();
    $png = app(FirmasDocentes::class)->normalizar(imagenFirmaPrueba());
    FirmaDocente::create(['docente_id' => $d['lider']->id, 'imagen' => $png, 'sha256' => hash('sha256', $png)]);
    $this->actingAs($d['lider'])->put(route('docente-lider.codigo.revisar', $entrega), ['resultado' => 'aprobada', 'calificacion' => 10, 'autorizar_firma' => 1])->assertSessionHasNoErrors();
    expect(app(DocumentosGuias::class)->datos($d['proyecto']->fresh())['pendientes'])->toBeEmpty();
});

test('firmas no permiten suplantar propietario ni conservar código añadido al PNG', function () {
    $d = escenarioFirmas();
    $png = imagenFirmaPrueba().'<?php echo "inyeccion"; ?>';
    $this->actingAs($d['docente'])->post(route('docente.firma.guardar'), ['docente_id' => $d['lider']->id, 'contrasena_actual' => 'password', 'firma_dibujada' => 'data:image/png;base64,'.base64_encode($png)])->assertSessionHasNoErrors();
    $firma = FirmaDocente::firstOrFail();
    expect($firma->docente_id)->toBe($d['docente']->id)->and(base64_decode($firma->imagen))->not->toContain('<?php');
    $d['docente']->update(['estado' => 'inactivo']);
    $this->get(route('docente.firma'))->assertForbidden();
});

test('preview de apartado no guarda datos y queda limitado a coordinacion', function () {
    $d = escenarioFirmas();
    $datos = ['guia_integradora_id' => $d['guia']->id, 'orden' => 1, 'titulo' => '<script>texto</script>', 'descripcion' => 'Contenido nuevo', 'ponderacion' => 20];
    $this->actingAs($d['alumno'])->post(route('guias.preview-apartado'), $datos)->assertForbidden();
    $this->actingAs($d['coordinacion'])->post(route('guias.preview-apartado'), $datos)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($d['apartado']->fresh()->titulo)->toBe('Protocolo');
});

test('demo y APK usan permisos de repositorio y rechazan URLs internas e inyecciones', function () {
    Storage::fake('local');
    $d = escenarioFirmas();
    $d['apartado']->update(['requiere_codigo' => true]);
    foreach (['javascript:alert(1)', 'https://127.0.0.1/a', 'https://usuario:clave@example.com', 'https://localhost/a', 'https://2130706433/a', 'https://[::1]/a'] as $url) {
        $this->actingAs($d['alumno'])->post(route('estudiante.codigo.guardar'), ['demostracion_url' => $url, 'version' => '1'])->assertSessionHasErrors('demostracion_url');
    }
    $this->post(route('estudiante.codigo.guardar'), ['demostracion_url' => 'https://demo.example.com', 'version' => '1'])->assertSessionHasNoErrors();
    $producto = ProductoCodigo::firstOrFail();
    $this->actingAs($d['docente'])->get(route('docente-lider.demostracion', $producto))->assertForbidden();
    $this->actingAs($d['lider'])->get(route('docente-lider.demostracion', $producto))->assertOk()->assertSee('sandbox="allow-scripts allow-forms"', false);
    $otroLider = User::factory()->create(['rol_id' => $d['lider']->rol_id]);
    $this->actingAs($otroLider)->get(route('docente-lider.demostracion', $producto))->assertForbidden();
    $apkPath = tempnam(sys_get_temp_dir(), 'apk');
    $zip = new ZipArchive;
    $zip->open($apkPath, ZipArchive::OVERWRITE);
    $zip->addFromString('AndroidManifest.xml', 'test');
    $zip->close();
    $this->actingAs($d['alumno'])->post(route('estudiante.codigo.guardar'), ['version' => '2', 'aplicacion' => new UploadedFile($apkPath, 'demo.apk', null, null, true)])->assertSessionHasNoErrors();
    $archivo = ArchivoEntrega::firstOrFail();
    expect($archivo->es_aplicacion)->toBeTruthy();
    $this->actingAs($d['docente'])->get(route('docente-lider.codigo.archivo', $archivo))->assertForbidden();
    $this->get(route('docente-materia.archivos.descargar', $archivo))->assertForbidden();
    $this->actingAs($d['lider'])->get(route('docente-lider.codigo.archivo', $archivo))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('avisos se deduplican, usan correo y cancelan asignaciones que cambiaron antes del envío', function () {
    Queue::fake();
    Notification::fake();
    $d = escenarioFirmas();
    $servicio = app(AvisosAsignaciones::class);
    $servicio->sincronizar();
    $servicio->sincronizar();
    expect(AvisoAsignacion::count())->toBe(1);
    Queue::assertPushed(EnviarAvisoAsignacion::class, 1);
    $aviso = AvisoAsignacion::firstOrFail();
    $d['apartado']->update(['fecha_limite' => now()->addDays(5)]);
    (new EnviarAvisoAsignacion($aviso->id))->handle($servicio);
    expect($aviso->fresh()->estado)->toBe('cancelado');
    Notification::assertNothingSent();
    $servicio->sincronizar();
    $nuevo = AvisoAsignacion::latest('id')->first();
    (new EnviarAvisoAsignacion($nuevo->id))->handle($servicio);
    (new EnviarAvisoAsignacion($nuevo->id))->handle($servicio);
    Notification::assertSentToTimes($d['alumno'], AsignacionAcademica::class, 1);
    expect($d['alumno']->routeNotificationForMail())->toBe($d['alumno']->correo);
});

test('recordatorios 72 y 24 horas no se envían si el equipo ya entregó ni tras el vencimiento', function () {
    Queue::fake();
    Notification::fake();
    $d = escenarioFirmas();
    $d['apartado']->update(['fecha_limite' => now()->addHours(71)]);
    $servicio = app(AvisosAsignaciones::class);
    $servicio->sincronizar();
    expect(AvisoAsignacion::where('tipo', 'recordatorio_72')->count())->toBe(1);
    $recordatorio = AvisoAsignacion::where('tipo', 'recordatorio_72')->first();
    entregaFirmaPrueba($d);
    (new EnviarAvisoAsignacion($recordatorio->id))->handle($servicio);
    expect($recordatorio->fresh()->estado)->toBe('cancelado');
    $d['apartado']->update(['fecha_limite' => now()->addHours(23)]);
    Entrega::first()->update(['estado' => 'correccion']);
    $servicio->sincronizar();
    expect(AvisoAsignacion::where('tipo', 'recordatorio_24')->count())->toBe(1);
    $d['apartado']->update(['fecha_limite' => now()->subMinute()]);
    $aviso = AvisoAsignacion::where('tipo', 'recordatorio_24')->first();
    (new EnviarAvisoAsignacion($aviso->id))->handle($servicio);
    expect($aviso->fresh()->estado)->toBe('cancelado');
    Notification::assertNothingSent();
});
