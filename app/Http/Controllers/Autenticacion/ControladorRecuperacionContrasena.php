<?php

namespace App\Http\Controllers\Autenticacion;

use App\Correos\CodigoRecuperacion;
use App\Http\Controllers\Controller;
use App\Models\RecuperacionContrasena;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ControladorRecuperacionContrasena extends Controller
{
    public function solicitar(Request $request)
    {
        if ($this->contexto($request)) {
            return redirect()->route('password.codigo');
        }

        return view('auth.forgot-password');
    }

    public function enviar(Request $request)
    {
        $datos = $request->validate(['correo_recuperacion' => ['required', 'email', 'string', 'max:255']]);
        $correo = mb_strtolower(trim($datos['correo_recuperacion']));
        $correoHash = $this->correoHash($correo);
        // La misma espera se aplica también a direcciones no registradas.
        $recuperacion = RecuperacionContrasena::firstOrCreate(['correo_hash' => $correoHash]);
        $usuario = User::whereRaw('LOWER(correo) = ?', [$correo])->where('estado', 'activo')->first();
        $this->emitir($request, $recuperacion->id, $usuario);

        return redirect()->route('password.codigo')->with('estado', 'Si el correo corresponde a una cuenta activa, recibirás un código de recuperación.');
    }

    public function codigo(Request $request)
    {
        $recuperacion = $this->contexto($request);
        if (! $recuperacion) {
            return redirect()->route('password.request');
        }
        if ($this->autorizada($request, $recuperacion)) {
            return redirect()->route('password.nueva');
        }

        return view('auth.codigo-recuperacion', ['reenviarEn' => $recuperacion->reenviar_en?->timestamp ?? now()->timestamp,
            'ahora' => now()->timestamp]);
    }

    public function reenviar(Request $request)
    {
        $recuperacion = $this->contexto($request);
        if (! $recuperacion) {
            return redirect()->route('password.request');
        }
        if ($this->autorizada($request, $recuperacion)) {
            return redirect()->route('password.nueva');
        }
        $usuario = $recuperacion->usuario_id ? User::whereKey($recuperacion->usuario_id)->where('estado', 'activo')->first() : null;
        if ($usuario && ! hash_equals($recuperacion->correo_hash, $this->correoHash(mb_strtolower($usuario->correo)))) {
            $usuario = null;
        }
        $this->emitir($request, $recuperacion->id, $usuario);

        return redirect()->route('password.codigo')->with('estado', 'Si el correo corresponde a una cuenta activa, recibirás un nuevo código. El anterior deja de funcionar.');
    }

    private function emitir(Request $request, int $id, ?User $usuario): void
    {
        $codigo = (string) random_int(10000000, 99999999);
        $navegador = Str::random(64);
        $enviado = DB::transaction(function () use ($request, $id, $usuario, $codigo, $navegador) {
            $recuperacion = RecuperacionContrasena::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($recuperacion->reenviar_en?->isFuture()) {
                // Otra pestaña/sesión no debe invalidar un código que acaba de enviarse.
                if ((int) $request->session()->get('recuperacion.id') !== $id) {
                    $request->session()->put('recuperacion', ['id' => $id, 'navegador' => $navegador]);
                }

                return false;
            }
            $recuperacion->update(['usuario_id' => $usuario?->id, 'codigo_hash' => Hash::make($codigo),
                'navegador_hash' => hash('sha256', $navegador), 'autorizacion_hash' => null,
                'verificado_en' => null, 'reenviar_en' => now()->addMinutes(3), 'expira_en' => now()->addMinutes(10)]);
            $request->session()->regenerate();
            $request->session()->put('recuperacion', ['id' => $id, 'navegador' => $navegador]);

            return true;
        });
        if (! $enviado) {
            // PRG: nunca se devuelve un formulario POST para restaurarlo con «Atrás».
            return;
        }
        if ($usuario) {
            try {
                Mail::to($usuario->correo)->send(new CodigoRecuperacion($codigo));
            } catch (\Throwable $error) {
                // Se conserva la espera aunque falle el proveedor, para evitar reenvíos en ráfaga.
                report($error);
                throw ValidationException::withMessages(['recuperacion' => 'No se pudo enviar el correo. Inténtalo de nuevo cuando termine la cuenta regresiva.']);
            }
        }
    }

    public function verificar(Request $request)
    {
        $request->validate(['codigo' => ['required', 'regex:/^[0-9]{8}$/D']]);
        $id = $request->session()->get('recuperacion.id');
        $autorizacion = Str::random(64);
        $valida = DB::transaction(function () use ($request, $id, $autorizacion) {
            $recuperacion = RecuperacionContrasena::whereKey($id)->lockForUpdate()->first();
            if (! $recuperacion || ! $this->navegadorValido($request, $recuperacion) || ! $recuperacion->usuario_id
                || ! $recuperacion->expira_en?->isFuture() || ! $recuperacion->codigo_hash || $recuperacion->verificado_en
                || ! Hash::check($request->input('codigo'), $recuperacion->codigo_hash)) {
                return false;
            }
            $recuperacion->update(['codigo_hash' => null, 'verificado_en' => now(), 'autorizacion_hash' => hash('sha256', $autorizacion)]);

            return true;
        });
        if (! $valida) {
            return redirect()->route('password.codigo')->withErrors(['codigo' => 'El código es incorrecto, expiró o ya fue utilizado. Puedes escribirlo de nuevo o solicitar otro.']);
        }
        $request->session()->regenerate();
        $request->session()->put('recuperacion.autorizacion', $autorizacion);

        return redirect()->route('password.nueva');
    }

    public function nueva(Request $request)
    {
        $recuperacion = $this->contexto($request);
        if (! $recuperacion) {
            return redirect()->route('password.request');
        }
        if (! $this->autorizada($request, $recuperacion)) {
            return redirect()->route('password.codigo');
        }

        return view('auth.nueva-contrasena');
    }

    public function actualizar(Request $request)
    {
        $datos = $request->validate(['contrasena' => ['required', 'string', 'min:8', 'max:255', 'confirmed']]);
        $valida = DB::transaction(function () use ($request, $datos) {
            $recuperacion = RecuperacionContrasena::whereKey($request->session()->get('recuperacion.id'))->lockForUpdate()->first();
            if (! $recuperacion || ! $this->autorizada($request, $recuperacion)) {
                return false;
            }
            $usuario = User::whereKey($recuperacion->usuario_id)->where('estado', 'activo')->lockForUpdate()->first();
            if (! $usuario || ! hash_equals($recuperacion->correo_hash, $this->correoHash(mb_strtolower($usuario->correo)))) {
                return false;
            }
            $usuario->update(['contrasena' => $datos['contrasena'], 'debe_cambiar_contrasena' => false,
                'contrasena_actualizada_en' => now()]);
            // token_recordar no es fillable: rotarlo explícitamente revoca las cookies anteriores.
            $usuario->setRememberToken(Str::random(60));
            $usuario->save();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $usuario->id)->delete();
            }
            $recuperacion->update(['codigo_hash' => null, 'navegador_hash' => null, 'autorizacion_hash' => null,
                'verificado_en' => null, 'expira_en' => now()]);

            return true;
        });
        if (! $valida) {
            return redirect()->route('password.codigo')->withErrors(['codigo' => 'La autorización ya no es válida. Solicita un nuevo código.']);
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('estado', 'Contraseña actualizada. Inicia sesión con tu nueva contraseña.');
    }

    private function contexto(Request $request): ?RecuperacionContrasena
    {
        return RecuperacionContrasena::find($request->session()->get('recuperacion.id'));
    }

    private function navegadorValido(Request $request, RecuperacionContrasena $recuperacion): bool
    {
        $navegador = $request->session()->get('recuperacion.navegador');

        return is_string($navegador) && $recuperacion->navegador_hash && hash_equals($recuperacion->navegador_hash, hash('sha256', $navegador));
    }

    private function autorizada(Request $request, RecuperacionContrasena $recuperacion): bool
    {
        $autorizacion = $request->session()->get('recuperacion.autorizacion');

        return $this->navegadorValido($request, $recuperacion) && $recuperacion->verificado_en && $recuperacion->expira_en?->isFuture()
            && is_string($autorizacion) && $recuperacion->autorizacion_hash && hash_equals($recuperacion->autorizacion_hash, hash('sha256', $autorizacion));
    }

    private function correoHash(string $correo): string
    {
        return hash_hmac('sha256', $correo, config('app.key'));
    }
}
