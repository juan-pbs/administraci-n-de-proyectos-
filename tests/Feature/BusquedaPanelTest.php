<?php

use App\Models\Role;
use App\Models\User;
use App\Soporte\BusquedaPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function usuarioBusqueda(string $rol): User
{
    $modelo = Role::firstOrCreate(['nombre' => $rol], ['nombre_visible' => $rol]);

    return User::factory()->create(['rol_id' => $modelo->id]);
}

test('barra y endpoint de búsqueda solo están disponibles para coordinación y líder', function (string $rol, bool $permitido) {
    $this->actingAs(usuarioBusqueda($rol));
    $pagina = $this->get(route('dashboard'))->assertOk();
    $permitido ? $pagina->assertSee('data-busqueda-panel', false) : $pagina->assertDontSee('data-busqueda-panel', false);
    $respuesta = $this->getJson(route('dashboard.buscar', ['buscar' => 'firma']));
    $permitido ? $respuesta->assertOk() : $respuesta->assertForbidden();
})->with([['coordinacion', true], ['docente_lider', true], ['docente_materia', false], ['estudiante', false]]);

test('búsqueda relaciona frases sinónimos acentos palabras incompletas y errores de escritura', function (string $rol, string $consulta, string $primera) {
    // Catálogo completo para comprobar relevancia independientemente de asignaciones de prueba.
    $claves = ['usuarios', 'guias', 'equipos', 'proyectos', 'revision-codigo', 'revisiones-docente', 'mi-firma', 'asignaciones-docente', 'carreras-grupos', 'asignaturas', 'jerarquia-proyectos', 'periodos'];
    $navegacion = array_map(fn ($clave) => ['clave' => $clave, 'titulo' => $clave, 'ruta' => '/'.$clave], $claves);
    $resultados = BusquedaPanel::buscar($rol, $consulta, $navegacion);
    expect($resultados[0]['id'] ?? null)->toBe($primera);
    if ($consulta === 'poner mi firma') {
        expect(array_column($resultados, 'id'))->not->toContain('equipos');
    }
    if ($consulta === 'agregar maestros') {
        expect(array_column($resultados, 'id'))->not->toContain('cargar-alumnos', 'guias', 'grupos');
    }
})->with([
    ['coordinacion', 'quiero subir estudiantes en excel', 'cargar-alumnos'],
    ['coordinacion', 'agregar maestros', 'registrar-docentes'],
    ['coordinacion', 'cambiar fechas de capítulos', 'apartados'],
    ['coordinacion', 'vista previa pdf', 'preview-pdf'],
    ['coordinacion', 'alunmos', 'cargar-alumnos'],
    ['docente_lider', 'acomodar alumnos en equipos', 'equipos'],
    ['docente_lider', 'poner mi firma', 'firma'],
    ['docente_lider', 'fir', 'firma'],
    ['docente_lider', 'repositrio', 'revision-codigo'],
    ['docente_lider', 'ver las notas de las entregas', 'revisiones'],
]);

test('catálogo filtra módulos no autorizados incluso para líder sin grupo', function () {
    $this->actingAs(usuarioBusqueda('docente_lider'));
    $this->getJson(route('dashboard.buscar', ['buscar' => 'registrar docentes']))->assertOk()->assertJsonMissing(['id' => 'registrar-docentes']);
    $this->getJson(route('dashboard.buscar', ['buscar' => 'cargar alumnos']))->assertOk()->assertJsonMissing(['id' => 'cargar-alumnos']);
    $this->getJson(route('dashboard.buscar', ['buscar' => 'poner mi firma']))->assertOk()->assertJsonPath('resultados.0.id', 'firma')->assertJsonPath('resultados.0.ruta', route('docente.firma'));
    $this->getJson(route('dashboard.buscar', ['buscar' => 'galaxias astronomia']))->assertOk()->assertExactJson(['resultados' => []]);
});

test('búsqueda admite consulta desde dashboard sin JS y valida tamaño sin ejecutar entradas', function () {
    $this->actingAs(usuarioBusqueda('coordinacion'));
    $this->get(route('dashboard', ['buscar' => 'agregar maestros']))->assertOk()->assertViewHas('resultadosBusqueda', fn ($r) => $r[0]['id'] === 'registrar-docentes');
    $this->getJson(route('dashboard.buscar', ['buscar' => str_repeat('a', 161)]))->assertUnprocessable()->assertJsonValidationErrors('buscar');
    $this->get(route('dashboard', ['buscar' => '<script>alert(1)</script>']))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    $respuesta = $this->getJson(route('dashboard.buscar', ['buscar' => "' OR 1=1 --"]))->assertOk();
    expect($respuesta->headers->get('Cache-Control'))->toContain('no-store');
});
