<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarCambioContrasena
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->debe_cambiar_contrasena && ! $request->routeIs('contrasena.*', 'logout')) {
            return redirect()->route('contrasena.editar');
        }

        return $next($request);
    }
}
