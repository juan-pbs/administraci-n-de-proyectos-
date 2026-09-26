<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProtegerVistasAutenticacion
{
    public function handle(Request $request, Closure $next)
    {
        $respuesta = $next($request);
        $respuesta->headers->set('Cache-Control', 'no-store, private');
        $respuesta->headers->set('Pragma', 'no-cache');
        $respuesta->headers->set('Expires', '0');
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('Referrer-Policy', 'no-referrer');

        return $respuesta;
    }
}
