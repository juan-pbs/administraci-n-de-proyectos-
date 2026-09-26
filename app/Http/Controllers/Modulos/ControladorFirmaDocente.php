<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\FirmaDocente;
use App\Servicios\FirmasDocentes;
use App\Soporte\SistemaInterfaz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class ControladorFirmaDocente extends Controller
{
    public function mostrar(Request $request)
    {
        $usuario = $this->docente($request);

        return response()->view('modulos.docente.firma', [
            'firma' => FirmaDocente::query()->where('docente_id', $usuario->id)->first(),
            'navegacion' => SistemaInterfaz::navegacionPara($usuario->role->nombre),
            'roleName' => $usuario->role->nombre_visible,
        ])->header('Cache-Control', 'no-store, private')->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function guardar(Request $request, FirmasDocentes $firmas)
    {
        $usuario = $this->docente($request);
        $datos = $request->validate([
            'contrasena_actual' => ['required', 'string', 'max:255'],
            'firma' => ['nullable', 'required_without:firma_dibujada', File::types(['png', 'jpg', 'jpeg'])->max('2mb')],
            'firma_dibujada' => ['nullable', 'required_without:firma', 'string', 'max:2800000'],
        ]);
        if (! Hash::check($datos['contrasena_actual'], $usuario->getAuthPassword())) {
            throw ValidationException::withMessages(['contrasena_actual' => 'La contraseña actual no coincide.']);
        }
        if ($request->hasFile('firma')) {
            $bytes = file_get_contents($request->file('firma')->getRealPath());
        } else {
            $data = $datos['firma_dibujada'];
            if (! str_starts_with($data, 'data:image/png;base64,') || ($bytes = base64_decode(substr($data, 22), true)) === false) {
                throw ValidationException::withMessages(['firma' => 'El dibujo de la firma no es válido.']);
            }
        }
        $imagen = $firmas->normalizar($bytes);
        FirmaDocente::query()->updateOrCreate(['docente_id' => $usuario->id], ['imagen' => $imagen, 'sha256' => hash('sha256', base64_decode($imagen))]);

        return back()->with('estado', 'Firma guardada. Se incorporará únicamente al aprobar y autorizar cada entrega.');
    }

    private function docente(Request $request)
    {
        $usuario = $request->user()->loadMissing('role');
        abort_unless($usuario->hasAnyRole('docente_lider', 'docente_materia'), 403);

        return $usuario;
    }
}
