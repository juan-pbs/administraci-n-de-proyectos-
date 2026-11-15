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

test('la interfaz muestra el menu plegable, filtros automaticos y edicion multiple', function () {
    $d = datosRecomendaciones();
    $this->actingAs($d['usuario']);

    $this->get(route('modulos.show', 'carreras-grupos'))
        ->assertOk()->assertSee('id="toggle-sidebar"', false)
        ->assertSee('name="periodo_grupos" onchange="this.form.requestSubmit()"', false)
        ->assertSee('name="carrera_grupos" onchange="this.form.requestSubmit()"', false);

    $this->get(route('modulos.show', 'guias'))
        ->assertOk()->assertSee('Crear o actualizar varios apartados')
        ->assertSee('Cargar apartados existentes');
});
