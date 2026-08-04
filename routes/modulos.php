<?php

use App\Http\Controllers\Modulos\ControladorAsignaturas;
use App\Http\Controllers\Modulos\ControladorCarrerasGrupos;
use App\Http\Controllers\Modulos\ControladorDocenteMateria;
use App\Http\Controllers\Modulos\ControladorRevisionCodigo;
use App\Http\Controllers\Modulos\ControladorEstudiante;
use App\Http\Controllers\Modulos\ControladorEquipos;
use App\Http\Controllers\Modulos\ControladorGuias;
use App\Http\Controllers\Modulos\ControladorModuloGeneral;
use App\Http\Controllers\Modulos\ControladorPeriodos;
use App\Http\Controllers\Modulos\ControladorProyectos;
use App\Http\Controllers\Modulos\ControladorUsuarios;
use App\Http\Controllers\Modulos\ControladorJerarquiaProyectos;
use Illuminate\Support\Facades\Route;

Route::get('/modulos/periodos', [ControladorPeriodos::class, 'mostrar'])->name('modulos.periodos');
Route::post('/periodos', [ControladorPeriodos::class, 'guardar'])->name('periodos.guardar');

Route::get('/modulos/carreras-grupos', [ControladorCarrerasGrupos::class, 'mostrar'])->name('modulos.carreras-grupos');
Route::post('/carreras', [ControladorCarrerasGrupos::class, 'guardarCarrera'])->name('carreras.guardar');
Route::post('/grupos-academicos', [ControladorCarrerasGrupos::class, 'guardarGrupo'])->name('grupos-academicos.guardar');
Route::post('/docentes-carrera', [ControladorCarrerasGrupos::class, 'asignarDocenteCarrera'])->name('docentes-carrera.guardar');

Route::get('/modulos/usuarios', [ControladorUsuarios::class, 'mostrar'])->name('modulos.usuarios');
Route::post('/usuarios', [ControladorUsuarios::class, 'guardar'])->name('usuarios.guardar');
Route::post('/usuarios/docentes', [ControladorUsuarios::class, 'guardarDocentes'])->name('usuarios.docentes.guardar');
Route::post('/usuarios/alumnos/previsualizar', [ControladorUsuarios::class, 'previsualizarAlumnos'])->name('usuarios.alumnos.previsualizar');
Route::post('/usuarios/alumnos/confirmar', [ControladorUsuarios::class, 'confirmarAlumnos'])->name('usuarios.alumnos.confirmar');
Route::post('/usuarios/alumnos/importar', [ControladorUsuarios::class, 'importarAlumnos'])->name('usuarios.alumnos.importar');
Route::patch('/usuarios/docentes/carrera', [ControladorUsuarios::class, 'actualizarCarreraDocente'])->name('usuarios.docentes.carrera.actualizar');

Route::get('/modulos/jerarquia-proyectos', [ControladorJerarquiaProyectos::class, 'mostrar'])->name('modulos.jerarquia');
Route::post('/jerarquia/lideres', [ControladorJerarquiaProyectos::class, 'asignarLider'])->name('jerarquia.lideres.guardar');

Route::get('/modulos/asignaturas', [ControladorAsignaturas::class, 'mostrar'])->name('modulos.asignaturas');
Route::post('/asignaturas', [ControladorAsignaturas::class, 'guardar'])->name('asignaturas.guardar');
Route::post('/asignaturas/docentes', [ControladorAsignaturas::class, 'asignarDocente'])->name('asignaturas.docentes.guardar');
Route::delete('/asignaturas/docentes', [ControladorAsignaturas::class, 'quitarDocente'])->name('asignaturas.docentes.quitar');

Route::get('/modulos/guias', [ControladorGuias::class, 'mostrar'])->name('modulos.guias');
Route::post('/guias', [ControladorGuias::class, 'guardar'])->name('guias.guardar');
Route::post('/guias/apartados', [ControladorGuias::class, 'guardarApartado'])->name('guias.apartados.guardar');
Route::post('/guias/apartados/asignaturas', [ControladorGuias::class, 'asignarAsignaturaApartado'])->name('guias.apartados.asignaturas.guardar');
Route::post('/guias/apartados/firmas', [ControladorGuias::class, 'guardarFirmaApartado'])->name('guias.apartados.firmas.guardar');
Route::post('/guias/apartados/calificadores', [ControladorGuias::class, 'asignarDocenteCalificador'])->name('guias.apartados.calificadores.guardar');
Route::delete('/guias/apartados/calificadores', [ControladorGuias::class, 'quitarDocenteCalificador'])->name('guias.apartados.calificadores.quitar');

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

Route::get('/docente-materia/asignaciones', [ControladorDocenteMateria::class, 'asignaciones'])->name('docente-materia.asignaciones');
Route::get('/docente-materia/revisiones', [ControladorDocenteMateria::class, 'revisiones'])->name('docente-materia.revisiones');
Route::put('/docente-materia/revisiones/{entrega}', [ControladorDocenteMateria::class, 'guardarRevision'])->name('docente-materia.revisiones.guardar');
Route::post('/docente-materia/revisiones/{entrega}/comentarios', [ControladorDocenteMateria::class, 'guardarComentario'])->name('docente-materia.comentarios.guardar');
Route::get('/docente-materia/archivos/{archivo}', [ControladorDocenteMateria::class, 'descargarArchivo'])->name('docente-materia.archivos.descargar');
Route::get('/docente-materia/revision-principal', [ControladorRevisionCodigo::class, 'mostrar'])->name('docente-materia.principal');
Route::put('/docente-materia/revision-principal/{entrega}', [ControladorRevisionCodigo::class, 'guardarRevision'])->name('docente-materia.principal.revisar');
Route::post('/docente-materia/revision-principal/{entrega}/comentarios', [ControladorRevisionCodigo::class, 'guardarComentario'])->name('docente-materia.principal.comentar');
Route::get('/docente-materia/revision-principal/archivos/{archivo}', [ControladorRevisionCodigo::class, 'descargar'])->name('docente-materia.principal.archivo');
Route::get('/estudiante/proyecto', [ControladorEstudiante::class, 'proyecto'])->name('estudiante.proyecto');
Route::get('/estudiante/entregas', [ControladorEstudiante::class, 'entregas'])->name('estudiante.entregas');
Route::post('/estudiante/entregas/{apartado}', [ControladorEstudiante::class, 'guardarEntrega'])->name('estudiante.entregas.guardar');
Route::get('/estudiante/codigo', [ControladorEstudiante::class, 'codigo'])->name('estudiante.codigo');
Route::post('/estudiante/codigo', [ControladorEstudiante::class, 'guardarCodigo'])->name('estudiante.codigo.guardar');
Route::get('/estudiante/archivos/{archivo}', [ControladorEstudiante::class, 'descargar'])->name('estudiante.archivos.descargar');

Route::get('/modulos/{modulo}', [ControladorModuloGeneral::class, 'mostrar'])->name('modulos.show');
