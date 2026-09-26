<?php

use App\Http\Middleware\ProtegerVistasAutenticacion;
use App\Http\Middleware\VerificarCambioContrasena;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'cambio.contrasena' => VerificarCambioContrasena::class,
            'auth.privado' => ProtegerVistasAutenticacion::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['contrasena', 'contrasena_actual', 'contrasena_confirmation', 'firma_dibujada', 'codigo', 'correo_recuperacion']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->routeIs('dashboard.buscar'),
        );
    })->create();
