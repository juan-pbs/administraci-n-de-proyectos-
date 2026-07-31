<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated users see the dashboard for their role', function (string $role, string $expectedText) {
    $roleModel = Role::query()->firstOrCreate([
        'nombre' => $role,
    ], [
        'nombre_visible' => str($role)->replace('_', ' ')->title()->toString(),
        'descripcion' => 'Rol de prueba',
    ]);

    $user = User::factory()->create([
        'rol_id' => $roleModel->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee($expectedText);
})->with([
    ['coordinacion', 'Panel de dirección / coordinación'],
    ['docente_lider', 'Panel del docente líder'],
    ['estudiante', 'Panel del estudiante'],
]);

test('dashboard de coordinacion es informativo y no contiene accesos a modulos', function () {
    $role = Role::query()->firstOrCreate(
        ['nombre' => 'coordinacion'],
        ['nombre_visible' => 'Coordinación', 'descripcion' => 'Rol de prueba'],
    );
    $user = User::factory()->create(['rol_id' => $role->id]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Atención operativa')
        ->assertDontSee('Ver reporte')
        ->assertDontSee('Carga académica');
});
