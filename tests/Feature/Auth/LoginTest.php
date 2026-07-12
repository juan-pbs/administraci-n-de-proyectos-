<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSee('name="matricula"', false);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create([
        'contrasena' => 'password',
    ]);

    $response = $this->post('/login', [
        'matricula' => $user->matricula,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');
});

test('users with temporary password must update it on first login', function () {
    $user = User::factory()->create([
        'contrasena' => 'password',
        'debe_cambiar_contrasena' => true,
    ]);

    $response = $this->post('/login', [
        'matricula' => $user->matricula,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('contrasena.editar'));

    $this->get('/dashboard')->assertRedirect(route('contrasena.editar'));

    $this->post(route('contrasena.actualizar'), [
        'contrasena_actual' => 'password',
        'contrasena' => 'NuevaClave2026',
        'contrasena_confirmation' => 'NuevaClave2026',
    ])->assertRedirect('/dashboard');

    expect($user->fresh()->debe_cambiar_contrasena)->toBeFalse();
});

test('login validation messages are shown in spanish', function () {
    $response = $this->from('/login')->post('/login', []);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors([
        'matricula' => 'El campo matrícula es obligatorio.',
        'password' => 'El campo contraseña es obligatorio.',
    ]);
});

test('dashboard requires authentication', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/login');
});
