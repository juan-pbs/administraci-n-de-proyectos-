<?php

use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function datosRecomendaciones(): array
{
    $coordinacion = Role::query()->firstOrCreate(['nombre' => 'coordinacion'], ['nombre_visible' => 'Coordinación']);
    $estudiante = Role::query()->firstOrCreate(['nombre' => 'estudiante'], ['nombre_visible' => 'Estudiante']);
    $carrera = Carrera::query()->create(['nombre' => 'Mecatrónica', 'clave' => 'MEC', 'estado' => 'activa']);
    $periodo = Periodo::query()->create([
        'nombre' => 'Periodo actual', 'fecha_inicio' => now()->startOfMonth()->toDateString(),
        'fecha_fin' => now()->addMonths(2)->endOfMonth()->toDateString(), 'estado' => 'activo',
    ]);
    $asignatura = Asignatura::query()->create(['carrera_id' => $carrera->id, 'nombre' => 'Integradora', 'clave' => 'MEC-INT', 'grado' => 4, 'estado' => 'activo']);
    $usuario = User::factory()->create(['rol_id' => $coordinacion->id]);

    return compact('estudiante', 'carrera', 'periodo', 'asignatura', 'usuario');
}

test('las altas y vistas previas de alumnos exigen siete digitos', function () {
    Mail::fake();
    $d = datosRecomendaciones();
    $this->actingAs($d['usuario']);

    $datos = [
        'nombre' => 'Alumno', 'correo' => 'alumno@utvm.edu.mx',
        'rol_id' => $d['estudiante']->id, 'carrera_id' => $d['carrera']->id,
    ];
    $this->post(route('usuarios.guardar'), [...$datos, 'matricula' => '20260001'])
        ->assertSessionHasErrors('matricula');
    $this->post(route('usuarios.guardar'), [...$datos, 'matricula' => '2026001'])
        ->assertSessionHasNoErrors();

    $archivo = fn (string $matricula) => UploadedFile::fake()->createWithContent('alumnos.csv',
        "matricula,nombre_completo,correo_electronico\n{$matricula},Otro Alumno,otro@utvm.edu.mx\n");
    $lista = fn (string $matricula) => ['periodo_id' => $d['periodo']->id, 'listas' => [[
        'archivo' => $archivo($matricula), 'carrera_id' => $d['carrera']->id, 'grado' => 4, 'grupo' => 'A',
    ]]];

    $this->post(route('usuarios.alumnos.previsualizar'), $lista('ABC1234'))
        ->assertSessionHasErrors('listas.0.archivo');
    $this->post(route('usuarios.alumnos.previsualizar'), $lista('2026002'))
        ->assertSessionHasNoErrors();
});

test('varios apartados se crean y actualizan juntos sin guardar un lote invalido', function () {
    $d = datosRecomendaciones();
    $guia = GuiaIntegradora::query()->create([
        'periodo_id' => $d['periodo']->id, 'asignatura_id' => $d['asignatura']->id,
        'creado_por' => $d['usuario']->id, 'nombre' => 'Guía de prueba',
        'cuatrimestre' => 4, 'version' => '1.0', 'estado' => 'borrador',
    ]);
    $this->actingAs($d['usuario']);
    $fecha = now()->addDay()->format('Y-m-d H:i');
    $apartados = [
        ['orden' => 1, 'titulo' => 'Primero', 'ponderacion' => 50, 'fecha_limite' => $fecha, 'requiere_documento' => 1],
        ['orden' => 2, 'titulo' => 'Segundo', 'ponderacion' => 50, 'fecha_limite' => $fecha, 'requiere_codigo' => 1],
    ];

    $this->post(route('guias.apartados.lote.guardar'), [
        'guia_integradora_id' => $guia->id, 'apartados' => $apartados,
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('apartados_guia', 2);

    $apartados[0]['titulo'] = 'Primero actualizado';
    $apartados[1]['titulo'] = 'Segundo actualizado';
    $this->post(route('guias.apartados.lote.guardar'), [
        'guia_integradora_id' => $guia->id, 'apartados' => $apartados,
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('apartados_guia', 2);
    $this->assertDatabaseHas('apartados_guia', ['guia_integradora_id' => $guia->id, 'orden' => 1, 'titulo' => 'Primero actualizado']);

    $apartados[0]['titulo'] = 'No debe guardarse';
    $apartados[1]['fecha_limite'] = now()->subDay()->format('Y-m-d H:i');
    $this->post(route('guias.apartados.lote.guardar'), [
        'guia_integradora_id' => $guia->id, 'apartados' => $apartados,
    ])->assertSessionHasErrors('apartados.1.fecha_limite');
    $this->assertDatabaseMissing('apartados_guia', ['titulo' => 'No debe guardarse']);
});

test('la vista previa incluye varios apartados sin guardar los cambios', function () {
    $d = datosRecomendaciones();
    $guia = GuiaIntegradora::query()->create([
        'periodo_id' => $d['periodo']->id, 'asignatura_id' => $d['asignatura']->id,
        'creado_por' => $d['usuario']->id, 'nombre' => 'Guía de prueba',
        'cuatrimestre' => 4, 'version' => '1.0', 'estado' => 'borrador',
    ]);
    ApartadoGuia::query()->create([
        'guia_integradora_id' => $guia->id, 'orden' => 1, 'titulo' => 'Original',
        'ponderacion' => 50, 'requiere_documento' => true,
    ]);
    $apartados = [
        ['orden' => 1, 'titulo' => 'Editado', 'ponderacion' => 50, 'requiere_documento' => 1],
        ['orden' => 2, 'titulo' => 'Nuevo', 'ponderacion' => 50, 'requiere_documento' => 1],
    ];

    $this->actingAs($d['usuario'])->post(route('guias.preview-apartado'), [
        'guia_integradora_id' => $guia->id, 'apartados' => $apartados,
    ])->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->assertDatabaseCount('apartados_guia', 1);
    $this->assertDatabaseHas('apartados_guia', ['guia_integradora_id' => $guia->id, 'orden' => 1, 'titulo' => 'Original']);

    $apartados[1]['orden'] = 1;
    $this->post(route('guias.preview-apartado'), [
        'guia_integradora_id' => $guia->id, 'apartados' => $apartados,
    ])->assertSessionHasErrors('apartados.1.orden');
});

test('el nombre del periodo debe coincidir con los meses y se puede corregir en borrador', function () {
    $d = datosRecomendaciones();
    $this->actingAs($d['usuario']);
    $inicio = now()->addMonths(4)->startOfMonth();
    $fin = $inicio->copy()->addMonths(3)->endOfMonth();
    $nombre = ucfirst($inicio->locale('es')->monthName).' - '.ucfirst($fin->locale('es')->monthName).' '.$inicio->year;

    $this->post(route('periodos.guardar'), [
        'nombre' => 'Enero - Abril 2099', 'fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $fin->toDateString(),
    ])->assertSessionHasErrors('nombre');

    $periodo = Periodo::query()->create([
        'nombre' => 'Borrador por corregir', 'fecha_inicio' => $inicio->toDateString(),
        'fecha_fin' => $fin->toDateString(), 'estado' => 'borrador',
    ]);
    $this->patch(route('periodos.actualizar', $periodo), [
        'nombre' => $nombre, 'fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $fin->toDateString(),
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('periodos', ['id' => $periodo->id, 'nombre' => $nombre, 'fecha_inicio' => $inicio->toDateString()]);
});

test('la interfaz muestra el menu plegable, filtros automaticos y edicion multiple', function () {
    $d = datosRecomendaciones();
    $this->actingAs($d['usuario']);

    $this->get(route('modulos.show', 'carreras-grupos'))
        ->assertOk()->assertSee('id="toggle-sidebar"', false)
        ->assertSee('name="periodo_grupos" onchange="this.form.requestSubmit()"', false)
        ->assertSee('name="carrera_grupos" onchange="this.form.requestSubmit()"', false);

    $this->get(route('modulos.show', 'guias'))
        ->assertOk()->assertSee('Crear o actualizar apartados')
        ->assertSee('Cargar apartados existentes')
        ->assertSee('id="guia-preview-dialog"', false)
        ->assertSee('data-open-preview', false)
        ->assertDontSee('Crear o actualizar apartado</h4>', false);
});
