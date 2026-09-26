<?php

use App\Correos\CodigoRecuperacion;
use App\Models\RecuperacionContrasena;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function solicitarCodigoPrueba($test, User $usuario): string
{
    $test->post(route('password.enviar'), ['correo_recuperacion' => $usuario->correo])->assertRedirect(route('password.codigo'));

    return Mail::sent(CodigoRecuperacion::class)->last()->codigo;
}

test('recuperacion completa exige código y vuelve al login sin permitir reutilizar autorización', function () {
    Mail::fake();
    $usuario = User::factory()->create();
    $recordarAnterior = $usuario->getRememberToken();
    $codigo = solicitarCodigoPrueba($this, $usuario);
    $recuperacion = RecuperacionContrasena::firstOrFail();
    expect($recuperacion->codigo_hash)->not->toBe($codigo);
    expect(Hash::check($codigo, $recuperacion->codigo_hash))->toBeTrue();
    $this->get(route('password.nueva'))->assertRedirect(route('password.codigo'));
    $this->post(route('password.actualizar'), ['contrasena' => 'NuevaClave123', 'contrasena_confirmation' => 'NuevaClave123'])->assertRedirect(route('password.codigo'));
    expect(Hash::check('password', $usuario->fresh()->getAuthPassword()))->toBeTrue();
    $this->post(route('password.verificar'), ['codigo' => $codigo])->assertRedirect(route('password.nueva'));
    expect($recuperacion->fresh()->codigo_hash)->toBeNull();
    $this->get(route('password.codigo'))->assertRedirect(route('password.nueva'));
    $contextoAnterior = session('recuperacion');
    $this->get(route('password.nueva'))->assertOk()->assertDontSee($codigo);
    $this->post(route('password.actualizar'), ['contrasena' => 'NuevaClave123', 'contrasena_confirmation' => 'NuevaClave123'])->assertRedirect(route('login'));
    $this->assertGuest();
    expect(Hash::check('NuevaClave123', $usuario->fresh()->getAuthPassword()))->toBeTrue();
    expect($usuario->fresh()->getRememberToken())->not->toBe($recordarAnterior);
    expect(session('recuperacion'))->toBeNull();
    $this->withSession(['recuperacion' => $contextoAnterior])->post(route('password.actualizar'), ['contrasena' => 'OtraClave123', 'contrasena_confirmation' => 'OtraClave123'])->assertSessionHasErrors('codigo');
    expect(Hash::check('NuevaClave123', $usuario->fresh()->getAuthPassword()))->toBeTrue();
    $this->get(route('login'))->assertOk();
    $this->get(route('password.nueva'))->assertRedirect(route('password.request'));
    $this->post(route('login'), ['matricula' => $usuario->matricula, 'password' => 'NuevaClave123'])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($usuario);
});

test('código incorrecto muestra error sin guardar campos y el reenvío espera tres minutos', function () {
    Mail::fake();
    $usuario = User::factory()->create();
    $anterior = solicitarCodigoPrueba($this, $usuario);
    $this->post(route('password.verificar'), ['codigo' => '00000000'])->assertSessionHasErrors('codigo');
    expect(session()->getOldInput('codigo'))->toBeNull();
    $this->get(route('password.codigo'))->assertOk()->assertSee('03:00')->assertSee('disabled', false)->assertDontSee('value="00000000"', false);
    $this->post(route('password.reenviar'))->assertRedirect(route('password.codigo'));
    $this->post(route('password.enviar'), ['correo_recuperacion' => $usuario->correo])->assertRedirect(route('password.codigo'));
    Mail::assertSentCount(1);
    $this->travel(179)->seconds();
    $this->post(route('password.reenviar'))->assertRedirect(route('password.codigo'));
    Mail::assertSentCount(1);
    $this->travel(2)->seconds();
    $this->post(route('password.reenviar'))->assertRedirect(route('password.codigo'));
    Mail::assertSentCount(2);
    $nuevo = Mail::sent(CodigoRecuperacion::class)->last()->codigo;
    $this->post(route('password.verificar'), ['codigo' => $anterior])->assertSessionHasErrors('codigo');
    $this->post(route('password.verificar'), ['codigo' => $nuevo])->assertRedirect(route('password.nueva'));
});

test('otra sesión no evita la espera ni usa el código emitido para el navegador original', function () {
    Mail::fake();
    $usuario = User::factory()->create();
    $codigo = solicitarCodigoPrueba($this, $usuario);
    $contextoOriginal = session('recuperacion');
    $this->flushSession();
    $this->post(route('password.enviar'), ['correo_recuperacion' => mb_strtoupper($usuario->correo)])->assertRedirect(route('password.codigo'));
    Mail::assertSentCount(1);
    $this->post(route('password.verificar'), ['codigo' => $codigo])->assertSessionHasErrors('codigo');
    $this->withSession(['recuperacion' => $contextoOriginal])->post(route('password.verificar'), ['codigo' => $codigo])->assertRedirect(route('password.nueva'));
});

test('el código y la autorización expiran y una cuenta desactivada no puede recuperarse', function () {
    Mail::fake();
    $usuario = User::factory()->create();
    $codigo = solicitarCodigoPrueba($this, $usuario);
    $this->travel(10)->minutes();
    $this->post(route('password.verificar'), ['codigo' => $codigo])->assertSessionHasErrors('codigo');
    $this->post(route('password.reenviar'))->assertRedirect(route('password.codigo'));
    $codigo = Mail::sent(CodigoRecuperacion::class)->last()->codigo;
    $this->post(route('password.verificar'), ['codigo' => $codigo])->assertRedirect(route('password.nueva'));
    $usuario->update(['estado' => 'inactivo']);
    $this->post(route('password.actualizar'), ['contrasena' => 'NuevaClave123', 'contrasena_confirmation' => 'NuevaClave123'])->assertSessionHasErrors('codigo');
    expect(Hash::check('password', $usuario->fresh()->getAuthPassword()))->toBeTrue();
    $usuario->update(['estado' => 'activo']);
    $this->travel(10)->minutes();
    $this->get(route('password.nueva'))->assertRedirect(route('password.codigo'));
    $this->post(route('password.actualizar'), ['contrasena' => 'NuevaClave123', 'contrasena_confirmation' => 'NuevaClave123'])->assertSessionHasErrors('codigo');
});

test('correo desconocido o inactivo no revela cuentas ni recibe correo', function () {
    Mail::fake();
    $usuario = User::factory()->create(['estado' => 'inactivo']);
    foreach ([$usuario->correo, 'desconocido@example.com'] as $correo) {
        $this->post(route('password.enviar'), ['correo_recuperacion' => $correo])->assertRedirect(route('password.codigo'))->assertSessionHas('estado', 'Si el correo corresponde a una cuenta activa, recibirás un código de recuperación.');
        expect(session()->getOldInput('correo_recuperacion'))->toBeNull();
        $this->post(route('password.verificar'), ['codigo' => '12345678'])->assertSessionHasErrors('codigo');
        $this->get(route('login'));
    }
    Mail::assertNothingSent();
});

test('pantallas privadas no se almacenan en caché ni conservan contraseñas al fallar validaciones', function () {
    Mail::fake();
    $usuario = User::factory()->create();
    $this->get(route('password.request'))->assertOk()->assertHeader('Pragma', 'no-cache')->assertSee('data-auth-privado', false);
    $codigo = solicitarCodigoPrueba($this, $usuario);
    $response = $this->get(route('password.codigo'))->assertOk()->assertHeader('Pragma', 'no-cache');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $this->post(route('password.verificar'), ['codigo' => $codigo])->assertRedirect(route('password.nueva'));
    $this->from(route('password.nueva'))->post(route('password.actualizar'), ['contrasena' => 'ClaveCorta', 'contrasena_confirmation' => 'Distinta'])->assertSessionHasErrors('contrasena');
    expect(session()->getOldInput('contrasena'))->toBeNull();
    expect(session()->getOldInput('contrasena_confirmation'))->toBeNull();
    $this->get(route('password.nueva'))->assertOk()->assertDontSee('ClaveCorta');
});

test('la recuperación cierra sesiones en base de datos y el login no bloquea intentos fallidos', function () {
    Mail::fake();
    config(['session.driver' => 'database']);
    $usuario = User::factory()->create();
    foreach (range(1, 8) as $intento) {
        $this->post(route('login'), ['matricula' => $usuario->matricula, 'password' => 'incorrecta'])->assertSessionHasErrors('matricula')->assertStatus(302);
    }
    $codigo = solicitarCodigoPrueba($this, $usuario);
    $this->post(route('password.verificar'), ['codigo' => $codigo]);
    DB::table('sessions')->insert(['id' => 'sesion-otro-dispositivo', 'user_id' => $usuario->id, 'payload' => '', 'last_activity' => now()->timestamp]);
    $this->post(route('password.actualizar'), ['contrasena' => 'NuevaClave123', 'contrasena_confirmation' => 'NuevaClave123'])->assertRedirect(route('login'));
    expect(DB::table('sessions')->where('id', 'sesion-otro-dispositivo')->exists())->toBeFalse();
});
