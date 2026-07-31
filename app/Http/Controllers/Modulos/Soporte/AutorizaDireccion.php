<?php

namespace App\Http\Controllers\Modulos\Soporte;

use Illuminate\Http\Request;

trait AutorizaDireccion
{
    private function autorizarDireccion(Request $request): void
    {
        abort_unless($request->user()?->hasRole('coordinacion'), 403);
    }
}
