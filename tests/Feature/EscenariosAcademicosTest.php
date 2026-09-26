<?php

use App\Models\{ArchivoEntrega, DocumentoFinal, FirmaDocente, GuiaIntegradora, Periodo, ProductoCodigo, Proyecto, User};
use App\Servicios\{DocumentosGuias, EstadoGuias};
use Database\Seeders\EscenariosAcademicosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification, Queue, Storage};

uses(RefreshDatabase::class);

test('escenarios académicos cubren estados y relaciones con nombres normales sin enviar correos', function () {
    Storage::fake('local'); Queue::fake(); Notification::fake();
    $this->partialMock(DocumentosGuias::class, fn ($m) => $m->shouldReceive('pdf')->times(24)->andReturn('%PDF-1.7 ESCUELA'));
    $this->seed(EscenariosAcademicosSeeder::class);
    expect(Periodo::count())->toBe(5)->and(Periodo::where('estado', 'activo')->count())->toBe(1)
        ->and(Proyecto::count())->toBe(84)->and(DocumentoFinal::count())->toBe(24)->and(FirmaDocente::count())->toBe(8)
        ->and(User::count())->toBe(325)->and(GuiaIntegradora::count())->toBe(24);
    foreach (['usuarios' => 'nombre', 'periodos' => 'nombre', 'guias_integradoras' => 'nombre', 'proyectos' => 'titulo'] as $tabla => $campo) {
        expect(DB::table($tabla)->where($campo, 'like', '%DEMO%')->exists())->toBeFalse();
    }
    $estados = Proyecto::all()->map(fn ($p) => app(EstadoGuias::class)->proyecto($p))->countBy('estado')->all();
    expect($estados)->toEqual(['finalizada' => 24, 'cerrada_pendientes' => 12, 'necesita_prorroga' => 8, 'lista_pdf' => 8, 'prorroga_activa' => 4, 'prorroga_vencida' => 4, 'en_curso' => 20, 'borrador' => 4]);
    expect(ProductoCodigo::whereNotIn('repositorio_url', EscenariosAcademicosSeeder::REPOSITORIOS)->exists())->toBeFalse()
        ->and(ArchivoEntrega::where('es_aplicacion', true)->exists())->toBeTrue();
    foreach (ArchivoEntrega::all() as $archivo) { expect(Storage::disk('local')->exists($archivo->ruta))->toBeTrue(); }
    $lider = User::where('matricula', 'DOC-TI-01')->firstOrFail();
    $this->actingAs($lider)->get(route('docente-lider.estado-guias'))->assertOk()->assertViewHas('resumen', fn ($r) => $r['total'] === 21)
        ->assertDontSee('Guía integradora MEC');
    $sinFirma = User::where('matricula', 'DOC-TI-05')->firstOrFail();
    expect($sinFirma->firma)->toBeNull();
    $this->actingAs($sinFirma)->get(route('docente-materia.asignaciones'))->assertOk()->assertSee('Comunicación técnica');
    $datosAntes = [User::count(), Proyecto::count(), DocumentoFinal::pluck('sha256', 'id')->all()];
    $this->seed(EscenariosAcademicosSeeder::class);
    expect([User::count(), Proyecto::count(), DocumentoFinal::pluck('sha256', 'id')->all()])->toBe($datosAntes);
    Notification::assertNothingSent(); Queue::assertNothingPushed();
});

test('reinicio rechaza otro nombre de base y no cambia usuarios', function () {
    $usuario = User::factory()->create();
    $this->artisan('datos:reiniciar', ['--confirmar' => 'otra_base'])->assertFailed();
    expect(User::find($usuario->id))->not->toBeNull();
});

test('reinicio conserva un respaldo completo y reemplaza los registros dentro de una transacción', function () {
    Storage::fake('local'); Queue::fake(); Notification::fake();
    $anterior = User::factory()->create(['nombre' => 'Registro anterior']);
    Storage::disk('local')->put('evidencia-anterior.txt', 'Contenido que debe conservarse en el respaldo.');
    $this->partialMock(DocumentosGuias::class, fn ($m) => $m->shouldReceive('pdf')->times(24)->andReturn('%PDF RESPALDO'));
    $this->artisan('datos:reiniciar', ['--confirmar' => DB::connection()->getDatabaseName()])->assertSuccessful();
    expect(User::where('nombre', 'Registro anterior')->exists())->toBeFalse()->and(User::count())->toBe(325);
    $respaldos = Storage::disk('local')->files('respaldos');
    expect($respaldos)->toHaveCount(1);
    $zip = new ZipArchive;
    expect($zip->open(Storage::disk('local')->path($respaldos[0])))->toBeTrue();
    $registros = json_decode($zip->getFromName('registros.json'), true, 512, JSON_THROW_ON_ERROR);
    expect(collect($registros['usuarios'])->contains('nombre', 'Registro anterior'))->toBeTrue()
        ->and($zip->getFromName('archivos/evidencia-anterior.txt'))->toBe('Contenido que debe conservarse en el respaldo.')
        ->and($zip->getFromName('clave-cifrado.txt'))->toBe(config('app.key'));
    $zip->close();
});
