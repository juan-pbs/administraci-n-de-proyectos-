<?php

use App\Models\DocumentoFinal;
use App\Models\Entrega;
use App\Models\FirmaDocente;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Revision;
use App\Models\Role;
use App\Models\User;
use App\Servicios\DocumentosGuias;
use Database\Seeders\HistorialDemostracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('historial ficticio conserva datos existentes y carga aprobaciones válidas sin duplicarlas ni enviar correos', function () {
    Storage::fake('local');
    Mail::fake();
    foreach (['coordinacion', 'docente_lider', 'docente_materia', 'estudiante'] as $rol) {
        Role::firstOrCreate(['nombre' => $rol], ['nombre_visible' => $rol]);
    }
    $activo = Periodo::create(['nombre' => 'Periodo existente', 'fecha_inicio' => '2026-05-01', 'fecha_fin' => '2026-08-31', 'estado' => 'activo']);
    $usuario = User::factory()->create(['rol_id' => Role::where('nombre', 'docente_lider')->value('id')]);
    $firma = FirmaDocente::create(['docente_id' => $usuario->id, 'imagen' => 'firma existente', 'sha256' => str_repeat('a', 64)]);
    $contrasena = $usuario->getRawOriginal('contrasena');
    // La generación visual de PDF se comprueba con los archivos reales de la carga local.
    $this->partialMock(DocumentosGuias::class, fn ($mock) => $mock->shouldReceive('pdf')->times(25)->andReturn('%PDF-1.7 DEMO'));

    $this->seed(HistorialDemostracionSeeder::class);
    expect(Periodo::count())->toBe(7)
        ->and(GuiaIntegradora::count())->toBe(7)
        ->and(Proyecto::count())->toBe(28)
        ->and(Entrega::count())->toBe(152)
        ->and(DocumentoFinal::count())->toBe(25)
        ->and(FirmaDocente::count())->toBe(4)
        ->and(Revision::where('resultado', 'aprobada')->count())->toBe(358)
        ->and(Revision::where('resultado', 'correccion')->count())->toBe(15)
        ->and(Revision::where('calificacion', '>', 10)->count())->toBe(0)
        ->and($activo->fresh()->estado)->toBe('activo')
        ->and($firma->fresh()->imagen)->toBe('firma existente')
        ->and($usuario->fresh()->getRawOriginal('contrasena'))->toBe($contrasena);

    $lider = User::where('matricula', 'DEMO-LIDER-01')->firstOrFail();
    expect(Revision::whereHas('entrega.apartado', fn ($query) => $query->where('requiere_codigo', true))->where('revisor_id', '!=', $lider->id)->count())->toBe(0);
    $this->actingAs($lider)->get(route('dashboard'))->assertOk()->assertSee('8D-DEMO');
    $this->get(route('docente-lider.codigo'))->assertOk()->assertSee('Reservas de laboratorios - ACTUAL (DEMO)')->assertSee('Correccion');
    $this->actingAs(User::where('matricula', 'DEMO-DOC-01')->firstOrFail())
        ->get(route('docente-materia.revisiones'))->assertOk()->assertSee('Biblioteca digital - ACTUAL (DEMO)');
    $completo = Proyecto::where('estado', 'finalizado')->firstOrFail();
    $this->actingAs(User::where('matricula', 'DEMO-COORD')->firstOrFail())
        ->get(route('documentos.mostrar', $completo))->assertOk();
    $this->get(route('documentos.descargar', [$completo, DocumentoFinal::where('proyecto_id', $completo->id)->firstOrFail()]))
        ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $documentos = app(DocumentosGuias::class);
    foreach (Proyecto::where('estado', 'finalizado')->get() as $proyecto) {
        expect($documentos->datos($proyecto)['pendientes'])->toBe([]);
    }
    foreach (Proyecto::where('estado', 'en_proceso')->get() as $proyecto) {
        expect($documentos->datos($proyecto)['pendientes'])->not->toBe([]);
    }
    $revision = Revision::where('resultado', 'aprobada')->firstOrFail();
    expect(DB::table('revisiones')->where('id', $revision->id)->value('firma_imagen'))->not->toBe($revision->firma_imagen);
    $lider->update(['contrasena' => 'Contraseña de prueba cambiada']);
    $claveCambiada = $lider->getRawOriginal('contrasena');
    $conteos = [User::count(), Revision::count(), DB::table('archivos_entrega')->count()];
    $this->seed(HistorialDemostracionSeeder::class);
    expect([User::count(), Revision::count(), DB::table('archivos_entrega')->count()])->toBe($conteos)
        ->and(DocumentoFinal::count())->toBe(25)
        ->and($lider->fresh()->getRawOriginal('contrasena'))->toBe($claveCambiada);
    Mail::assertNothingSent();
    Mail::assertNothingQueued();
});
