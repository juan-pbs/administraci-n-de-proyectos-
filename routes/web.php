<?php

use App\Http\Controllers\Autenticacion\ControladorActualizacionContrasena;
use App\Http\Controllers\Autenticacion\ControladorRecuperacionContrasena;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ControladorAyuda;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\VerificarCicloAcademico;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/ayuda/acceso', [ControladorAyuda::class, 'acceso'])->name('ayuda.acceso');

Route::middleware(['auth.privado', 'guest'])->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/forgot-password', [ControladorRecuperacionContrasena::class, 'solicitar'])->name('password.request');
    Route::post('/forgot-password', [ControladorRecuperacionContrasena::class, 'enviar'])->name('password.enviar');
    Route::get('/recuperar-contrasena/codigo', [ControladorRecuperacionContrasena::class, 'codigo'])->name('password.codigo');
    Route::post('/recuperar-contrasena/codigo', [ControladorRecuperacionContrasena::class, 'verificar'])->name('password.verificar');
    Route::post('/recuperar-contrasena/reenviar', [ControladorRecuperacionContrasena::class, 'reenviar'])->name('password.reenviar');
    Route::get('/recuperar-contrasena/nueva', [ControladorRecuperacionContrasena::class, 'nueva'])->name('password.nueva');
    Route::post('/recuperar-contrasena/nueva', [ControladorRecuperacionContrasena::class, 'actualizar'])->name('password.actualizar');
});

Route::middleware(['auth.privado', 'auth'])->group(function (): void {
    Route::get('/actualizar-contrasena', [ControladorActualizacionContrasena::class, 'editar'])->name('contrasena.editar');
    Route::post('/actualizar-contrasena', [ControladorActualizacionContrasena::class, 'actualizar'])->name('contrasena.actualizar');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('cambio.contrasena')->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/dashboard/buscar', [DashboardController::class, 'buscar'])->name('dashboard.buscar');
        Route::get('/ayuda', ControladorAyuda::class)->name('ayuda');
        Route::middleware(VerificarCicloAcademico::class)->group(function (): void {
            require __DIR__.'/modulos.php';
        });
    });
});
