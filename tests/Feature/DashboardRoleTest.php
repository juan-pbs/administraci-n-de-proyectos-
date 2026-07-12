<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated users see the dashboard for their role', function (string $role, string $expectedText) {
    $roleModel = Role::query()->create([
        'nombre' => $role,
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
    ['direccion_coordinacion', 'Panel de dirección / coordinación'],
    ['docente_asesor', 'Panel de docente / asesor'],
    ['estudiante', 'Panel de estudiante / equipo'],
]);
