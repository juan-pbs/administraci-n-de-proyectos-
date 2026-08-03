<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Autenticacion\ControladorActualizacionContrasena;
use App\Http\Controllers\ControladorAyuda;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/ayuda/acceso', [ControladorAyuda::class, 'acceso'])->name('ayuda.acceso');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/actualizar-contrasena', [ControladorActualizacionContrasena::class, 'editar'])->name('contrasena.editar');
    Route::post('/actualizar-contrasena', [ControladorActualizacionContrasena::class, 'actualizar'])->name('contrasena.actualizar');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('cambio.contrasena')->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/ayuda', ControladorAyuda::class)->name('ayuda');
        require __DIR__.'/modulos.php';
    });
});
