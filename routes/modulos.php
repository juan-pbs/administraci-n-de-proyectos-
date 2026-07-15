<?php

use App\Http\Controllers\Modulos\ControladorAsignaturas;
use App\Http\Controllers\Modulos\ControladorCarrerasGrupos;
use App\Http\Controllers\Modulos\ControladorEquipos;
use App\Http\Controllers\Modulos\ControladorGuias;
use App\Http\Controllers\Modulos\ControladorModuloGeneral;
use App\Http\Controllers\Modulos\ControladorPeriodos;
use App\Http\Controllers\Modulos\ControladorProyectos;
use App\Http\Controllers\Modulos\ControladorUsuarios;
use Illuminate\Support\Facades\Route;

Route::get('/modulos/periodos', [ControladorPeriodos::class, 'mostrar'])->name('modulos.periodos');
Route::post('/periodos', [ControladorPeriodos::class, 'guardar'])->name('periodos.guardar');

Route::get('/modulos/carreras-grupos', [ControladorCarrerasGrupos::class, 'mostrar'])->name('modulos.carreras-grupos');
Route::post('/carreras', [ControladorCarrerasGrupos::class, 'guardarCarrera'])->name('carreras.guardar');
Route::post('/grupos-academicos', [ControladorCarrerasGrupos::class, 'guardarGrupo'])->name('grupos-academicos.guardar');
Route::post('/docentes-carrera', [ControladorCarrerasGrupos::class, 'asignarDocenteCarrera'])->name('docentes-carrera.guardar');

Route::get('/modulos/usuarios', [ControladorUsuarios::class, 'mostrar'])->name('modulos.usuarios');
Route::post('/usuarios', [ControladorUsuarios::class, 'guardar'])->name('usuarios.guardar');
Route::post('/usuarios/alumnos/importar', [ControladorUsuarios::class, 'importarAlumnos'])->name('usuarios.alumnos.importar');
Route::patch('/usuarios/docentes/carrera', [ControladorUsuarios::class, 'actualizarCarreraDocente'])->name('usuarios.docentes.carrera.actualizar');

Route::get('/modulos/asignaturas', [ControladorAsignaturas::class, 'mostrar'])->name('modulos.asignaturas');
Route::post('/asignaturas', [ControladorAsignaturas::class, 'guardar'])->name('asignaturas.guardar');
Route::post('/asignaturas/docentes', [ControladorAsignaturas::class, 'asignarDocente'])->name('asignaturas.docentes.guardar');
Route::delete('/asignaturas/docentes', [ControladorAsignaturas::class, 'quitarDocente'])->name('asignaturas.docentes.quitar');

Route::get('/modulos/guias', [ControladorGuias::class, 'mostrar'])->name('modulos.guias');
Route::post('/guias', [ControladorGuias::class, 'guardar'])->name('guias.guardar');
Route::post('/guias/apartados', [ControladorGuias::class, 'guardarApartado'])->name('guias.apartados.guardar');
Route::post('/guias/apartados/asignaturas', [ControladorGuias::class, 'asignarAsignaturaApartado'])->name('guias.apartados.asignaturas.guardar');
Route::post('/guias/apartados/firmas', [ControladorGuias::class, 'guardarFirmaApartado'])->name('guias.apartados.firmas.guardar');

Route::get('/modulos/equipos', [ControladorEquipos::class, 'mostrar'])->name('modulos.equipos');
Route::post('/equipos', [ControladorEquipos::class, 'guardar'])->name('equipos.guardar');
Route::post('/equipos/alumnos', [ControladorEquipos::class, 'asignarAlumno'])->name('equipos.alumnos.guardar');
Route::delete('/equipos/alumnos', [ControladorEquipos::class, 'quitarAlumno'])->name('equipos.alumnos.quitar');
Route::post('/equipos/asesores', [ControladorEquipos::class, 'asignarAsesor'])->name('equipos.asesores.guardar');
Route::delete('/equipos/asesores', [ControladorEquipos::class, 'quitarAsesor'])->name('equipos.asesores.quitar');

Route::get('/modulos/proyectos', [ControladorProyectos::class, 'mostrar'])->name('modulos.proyectos');
Route::post('/proyectos', [ControladorProyectos::class, 'guardar'])->name('proyectos.guardar');
Route::post('/proyectos/docentes', [ControladorProyectos::class, 'asignarDocente'])->name('proyectos.docentes.guardar');
Route::delete('/proyectos/docentes', [ControladorProyectos::class, 'quitarDocente'])->name('proyectos.docentes.quitar');
Route::post('/proyectos/asignaturas', [ControladorProyectos::class, 'asignarAsignatura'])->name('proyectos.asignaturas.guardar');

Route::get('/modulos/{modulo}', [ControladorModuloGeneral::class, 'mostrar'])->name('modulos.show');
