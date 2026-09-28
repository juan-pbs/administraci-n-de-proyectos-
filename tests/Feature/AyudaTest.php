<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('la ayuda abre una seccion limpia diferente por rol', function (string $role, string $expectedText) {
    $roleModel = Role::query()->firstOrCreate(
        ['nombre' => $role],
        [
            'nombre_visible' => str($role)->replace('_', ' ')->title()->toString(),
            'descripcion' => 'Rol de prueba',
        ],
    );

    $user = User::factory()->create(['rol_id' => $roleModel->id]);

    $this->actingAs($user)
        ->get('/ayuda?seccion=panel-principal')
        ->assertOk()
        ->assertSee('Panel principal')
        ->assertSee($expectedText)
        ->assertSee('Cerrar')
        ->assertDontSee('Proyectos integradores')
        ->assertDontSee('Cerrar Sesion');
})->with([
    ['coordinacion', 'periodo activo y avisos'],
    ['docente_lider', 'tus grupos de trabajo'],
    ['docente_materia', 'entregas recibidas'],
    ['estudiante', 'avance del equipo'],
]);

test('el header muestra ayuda como desplegable y no como opcion lateral', function () {
    $role = Role::query()->firstOrCreate(
        ['nombre' => 'estudiante'],
        ['nombre_visible' => 'Estudiante', 'descripcion' => 'Rol de prueba'],
    );

    $user = User::factory()->create(['rol_id' => $role->id]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('<summary', false)
        ->assertSee('Ayuda')
        ->assertSee(route('ayuda', ['seccion' => 'iniciar-sesion']), false)
        ->assertSee(route('ayuda', ['seccion' => 'mi-proyecto']), false)
        ->assertSee('target="_blank"', false)
        ->assertSee('window.open(this.href', false)
        ->assertDontSee('<span class="truncate">Ayuda</span>', false)
        ->assertDontSee('href="'.route('ayuda').'"', false);
});

test('las opciones del desplegable cambian por rol', function () {
    $coordinacion = Role::query()->firstOrCreate(
        ['nombre' => 'coordinacion'],
        ['nombre_visible' => 'Coordinacion', 'descripcion' => 'Rol de prueba'],
    );

    $estudiante = Role::query()->firstOrCreate(
        ['nombre' => 'estudiante'],
        ['nombre_visible' => 'Estudiante', 'descripcion' => 'Rol de prueba'],
    );

    $usuarioCoordinacion = User::factory()->create(['rol_id' => $coordinacion->id]);
    $usuarioEstudiante = User::factory()->create(['rol_id' => $estudiante->id]);

    $this->actingAs($usuarioCoordinacion)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Periodos')
        ->assertSee(route('ayuda', ['seccion' => 'guias']), false)
        ->assertDontSee(route('ayuda', ['seccion' => 'mi-proyecto']), false);

    $this->actingAs($usuarioEstudiante)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Mi proyecto')
        ->assertSee(route('ayuda', ['seccion' => 'codigo-repositorio']), false)
        ->assertDontSee(route('ayuda', ['seccion' => 'periodos']), false);
});

test('ayuda directa inicia en la seccion de inicio de sesion', function () {
    $role = Role::query()->firstOrCreate(
        ['nombre' => 'estudiante'],
        ['nombre_visible' => 'Estudiante', 'descripcion' => 'Rol de prueba'],
    );

    $user = User::factory()->create(['rol_id' => $role->id]);

    $this->actingAs($user)
        ->get('/ayuda')
        ->assertOk()
        ->assertSee('Iniciar sesion')
        ->assertSee('matricula institucional')
        ->assertSee('data-help-annotated-figure', false)
        ->assertSee('marker-end=', false)
        ->assertSee('Recuperacion de acceso')
        ->assertSee('Sigue las flechas')
        ->assertSee('Campos y botones clave')
        ->assertDontSee('Mi proyecto')
        ->assertDontSee('Preguntas frecuentes');
});

test('la ayuda publica de acceso solo muestra temas de ingreso', function () {
    $this->get('/ayuda/acceso?seccion=recuperar-contrasena')
        ->assertOk()
        ->assertSee('Recuperar contrasena')
        ->assertSee('Correo registrado')
        ->assertSee('Enviar codigo')
        ->assertSee('acceso-recuperacion.png')
        ->assertSee('acceso-codigo.png')
        ->assertSee('acceso-nueva-contrasena.png')
        ->assertDontSee('Mi proyecto')
        ->assertDontSee('Periodos academicos')
        ->assertDontSee('Guias integradoras');
});

test('las pantallas de acceso muestran boton de ayuda contextual', function () {
    $role = Role::query()->firstOrCreate(
        ['nombre' => 'estudiante'],
        ['nombre_visible' => 'Estudiante', 'descripcion' => 'Rol de prueba'],
    );

    $usuarioTemporal = User::factory()->create([
        'rol_id' => $role->id,
        'debe_cambiar_contrasena' => true,
    ]);

    $this->get('/login')
        ->assertOk()
        ->assertSee('Ayuda')
        ->assertSee(route('ayuda.acceso', ['seccion' => 'iniciar-sesion']), false);

    $this->get('/forgot-password')
        ->assertOk()
        ->assertSee('Ayuda')
        ->assertSee(route('ayuda.acceso', ['seccion' => 'recuperar-contrasena']), false);

    $this->actingAs($usuarioTemporal)
        ->get('/actualizar-contrasena')
        ->assertOk()
        ->assertSee('Ayuda')
        ->assertSee(route('ayuda.acceso', ['seccion' => 'actualizar-contrasena']), false);
});

test('ayuda de docente lider sin grupo conserva contenido pero no enlaza al modulo', function () {
    $role = Role::query()->firstOrCreate(
        ['nombre' => 'docente_lider'],
        ['nombre_visible' => 'Docente lider', 'descripcion' => 'Rol de prueba'],
    );

    $user = User::factory()->create(['rol_id' => $role->id]);

    $this->actingAs($user)
        ->get('/ayuda?seccion=equipos')
        ->assertOk()
        ->assertSee('Equipos')
        ->assertDontSee('href="http://localhost/modulos/equipos"', false)
        ->assertDontSee('Abrir vista');
});

test('ayuda de estudiante no muestra apartados administrativos', function () {
    $role = Role::query()->firstOrCreate(
        ['nombre' => 'estudiante'],
        ['nombre_visible' => 'Estudiante', 'descripcion' => 'Rol de prueba'],
    );

    $user = User::factory()->create(['rol_id' => $role->id]);

    $this->actingAs($user)
        ->get('/ayuda?seccion=mi-proyecto')
        ->assertOk()
        ->assertSee('Mi proyecto')
        ->assertDontSee('Periodos academicos')
        ->assertDontSee('Guias integradoras');
});

test('la ayuda nueva muestra el contenido correcto y sus opciones solo al rol correspondiente', function () {
    $roles = [];
    foreach (['coordinacion', 'docente_lider', 'docente_materia', 'estudiante'] as $nombre) {
        $rol = Role::query()->firstOrCreate(['nombre' => $nombre], ['nombre_visible' => $nombre, 'descripcion' => 'Rol de prueba']);
        $roles[$nombre] = User::factory()->create(['rol_id' => $rol->id]);
    }

    $this->actingAs($roles['coordinacion'])
        ->get('/ayuda?seccion=guias')
        ->assertOk()
        ->assertSee('Actualizar vista previa')
        ->assertSee('Se cierra al terminar el periodo');

    $this->actingAs($roles['docente_lider'])
        ->get('/ayuda?seccion=estado-guias')
        ->assertOk()
        ->assertSee('10, 20 o 40 proyectos')
        ->assertSee('Volver conserva filtros y pagina')
        ->assertSee('lider-estado-guias.png');

    $this->actingAs($roles['docente_materia'])
        ->get('/ayuda?seccion=mi-firma')
        ->assertOk()
        ->assertSee('sube una imagen PNG o JPG')
        ->assertSee('version recibida')
        ->assertSee('docente-firma.png');

    $this->actingAs($roles['estudiante'])
        ->get('/ayuda?seccion=codigo-repositorio')
        ->assertOk()
        ->assertSee('URL de demostracion no es obligatoria')
        ->assertSee('estudiante-codigo-formulario.png')
        ->assertDontSee('Cierres y prorrogas');
});
