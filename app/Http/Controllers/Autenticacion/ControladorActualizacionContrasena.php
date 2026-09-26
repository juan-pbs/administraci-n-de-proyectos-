<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ControladorActualizacionContrasena extends Controller
{
    public function editar(): View
    {
        return view('autenticacion.actualizar-contrasena');
    }

    public function actualizar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'contrasena_actual' => ['required', 'string'],
            'contrasena' => ['required', 'string', 'min:8', 'max:255', 'confirmed', 'different:contrasena_actual'],
        ]);

        if (! Hash::check($datos['contrasena_actual'], $request->user()->getAuthPassword())) {
            throw ValidationException::withMessages([
                'contrasena_actual' => 'La contraseña actual no coincide.',
            ]);
        }

        $request->user()->forceFill([
            'contrasena' => $datos['contrasena'],
            'debe_cambiar_contrasena' => false,
            'contrasena_actualizada_en' => now(),
        ])->save();

        return redirect()->route('dashboard')->with('estado', 'Contraseña actualizada correctamente.');
    }
}
