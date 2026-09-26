<?php

use App\Models\CierreProyecto;
use App\Models\DocumentoFinal;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\User;
use App\Servicios\DocumentosGuias;
use Database\Seeders\CierresDemostracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('cierres demo agrega guías cerradas y casos completos sin duplicar ni alterar el periodo activo', function () {
    Storage::fake('local');
    Queue::fake();
    Notification::fake();
    $activo = Periodo::create(['nombre' => 'Activo existente', 'fecha_inicio' => today(), 'fecha_fin' => today()->addMonths(3), 'estado' => 'activo']);
    $this->partialMock(DocumentosGuias::class, fn ($mock) => $mock->shouldReceive('pdf')->twice()->andReturn('%PDF-1.7 DEMO'));
    $this->seed(CierresDemostracionSeeder::class);
    expect(GuiaIntegradora::where('estado', 'cerrada')->count())->toBe(3)
        ->and(Proyecto::count())->toBe(7)->and(DocumentoFinal::count())->toBe(2)
        ->and(CierreProyecto::where('estado', 'pendiente')->count())->toBe(3)
        ->and(CierreProyecto::where('estado', 'prorroga')->count())->toBe(1)
        ->and(CierreProyecto::where('estado', 'completo')->count())->toBe(2)
        ->and(CierreProyecto::where('estado', 'cerrado')->count())->toBe(1)
        ->and($activo->fresh()->estado)->toBe('activo');
    $lider = User::where('matricula', 'DEMO-LIDER-01')->firstOrFail();
    $this->actingAs($lider)->get(route('docente-lider.cierres'))->assertOk()->assertSee('Prórroga activa: resultados y código')->assertSee('Proyecto cerrado con PDF final firmado');
    foreach (DocumentoFinal::all() as $pdf) {
        expect(app(DocumentosGuias::class)->datos(Proyecto::findOrFail($pdf->proyecto_id), false)['pendientes'])->toBeEmpty();
        $this->get(route('documentos.descargar', ['proyecto' => $pdf->proyecto_id, 'documento' => $pdf->id]))->assertOk()->assertContent(base64_decode($pdf->pdf));
    }
    $alumno = User::where('matricula', 'DEMO-PRORROGA-ACTIVA-1')->firstOrFail();
    $this->actingAs($alumno)->get(route('estudiante.codigo'))->assertOk()->assertSee('Enviar nueva versión');
    $hashes = DocumentoFinal::pluck('sha256', 'id')->all();
    $usuarios = User::count();
    $lider->update(['contrasena' => 'nueva-contraseña-demo']);
    $password = $lider->getRawOriginal('contrasena');
    $this->seed(CierresDemostracionSeeder::class);
    expect(DocumentoFinal::pluck('sha256', 'id')->all())->toBe($hashes)->and(User::count())->toBe($usuarios)->and($lider->fresh()->getRawOriginal('contrasena'))->toBe($password);
    Notification::assertNothingSent();
    Queue::assertNothingPushed();
});
