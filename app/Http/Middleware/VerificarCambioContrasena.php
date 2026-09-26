<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarCambioContrasena
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->estado === 'activo', 403, 'La cuenta no está activa.');
        $respuesta = $next($request);
        $respuesta->headers->set('Cache-Control', 'no-store, private');
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->headers->set('Referrer-Policy', 'no-referrer');

        return $respuesta;
    }
}
