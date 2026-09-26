<?php

namespace Database\Seeders;

use App\Http\Controllers\Modulos\ControladorDocumentos;
use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Equipo;
use App\Models\FirmaApartadoGuia;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Servicios\CierresProyectos;
use App\Servicios\DocumentosGuias;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Carga independiente: conserva los ejemplos anteriores y cualquier decisión posterior. */
class PaginacionGuiasDemostracionSeeder extends CierresDemostracionSeeder
{
    public const PERIODO = 'Historial de guías y paginación (DEMO)';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Los ejemplos solo se cargan en local o pruebas.');
        }
        DB::transaction(function () {
            if (Periodo::where('nombre', self::PERIODO)->exists()) {
                return;
            }
            $carrera = Carrera::firstOrCreate(['clave' => 'TI'], ['nombre' => 'Tecnologías de la Información', 'estado' => 'activa']);
            $lider = $this->usuario('DEMO-LIDER-01', 'Elena Rivera (DEMO)', 'docente_lider', $carrera);
            $docente = $this->usuario('DEMO-DOC-01', 'Tomás Vega (DEMO)', 'docente_materia', $carrera);
            $coordinacion = $this->usuario('DEMO-COORD', 'Coordinación de demostración', 'coordinacion', $carrera);
            foreach ([$lider, $docente] as $persona) {
                $this->firma($persona);
            }
            $periodo = Periodo::create(['nombre' => self::PERIODO, 'fecha_inicio' => today()->subDays(110), 'fecha_fin' => today()->subDays(3), 'estado' => 'cerrado']);
            $materia = Asignatura::create(['clave' => 'DEMO-PAGINACION', 'nombre' => 'Integradora - Historial (DEMO)', 'carrera_id' => $carrera->id, 'grado' => 8, 'estado' => 'activo']);
            foreach ([$lider, $docente] as $persona) {
                $materia->docentes()->attach($persona->id, ['periodo_id' => $periodo->id, 'activo' => true]);
            }
            $tipos = ['entregas' => 'Faltan entregas', 'firma' => 'Faltan firmas', 'lista_pdf' => 'Listo para emitir PDF',
                'activa' => 'Prórroga activa', 'vencida' => 'Prórroga vencida', 'pdf' => 'PDF final archivado', 'cerrado' => 'Cerrado con pendientes'];
            for ($lote = 1; $lote <= 4; $lote++) {
                $grupo = GrupoAcademico::create(['periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'grado' => 8, 'grupo' => "PAG-DEMO-$lote", 'nombre' => "8 - Historial DEMO $lote", 'lider_proyecto_id' => $lider->id, 'asignatura_lider_id' => $materia->id]);
                $guia = GuiaIntegradora::create(['periodo_id' => $periodo->id, 'periodo_fin_id' => $periodo->id, 'asignatura_id' => $materia->id, 'creado_por' => $coordinacion->id, 'nombre' => "Guía de historial $lote (DEMO)", 'cuatrimestre' => '8° cuatrimestre', 'version' => '1.0', 'estado' => 'cerrada', 'objetivo_aprendizaje' => 'DEMOSTRACIÓN: revisar entregas, aprobaciones, cierres y documentos históricos. Todas las evidencias y firmas son ficticias.', 'competencias_evaluar' => 'Diseñar y documentar un proyecto integrador.']);
                foreach (['Protocolo', 'Desarrollo y resultados', 'Código y liberación'] as $orden => $titulo) {
                    $apartado = ApartadoGuia::create(['guia_integradora_id' => $guia->id, 'orden' => $orden + 1, 'titulo' => $titulo, 'descripcion' => 'Evidencia de demostración del apartado.', 'ponderacion' => $orden === 2 ? 30 : 35, 'requiere_documento' => true, 'requiere_codigo' => $orden === 2, 'fecha_limite' => today()->subDays(5)->endOfDay()]);
                    foreach ($orden === 2 ? [$lider] : [$lider, $docente] as $indice => $persona) {
                        FirmaApartadoGuia::create(['apartado_guia_id' => $apartado->id, 'docente_id' => $persona->id, 'orden' => $indice + 1, 'etiqueta' => 'Evaluador DEMO', 'requerida' => true]);
                    }
                }
                $guia->load('apartados.firmas.docente');
                $numero = 0;
                foreach ($tipos as $tipo => $descripcion) {
                    $numero++;
                    $alumno = $this->usuario("DEMO-HIST-$lote-$numero", "Alumno historial $lote.$numero (DEMO)", 'estudiante', $carrera, $grupo);
                    $equipo = Equipo::create(['grupo_academico_id' => $grupo->id, 'lider_id' => $alumno->id, 'numero' => $numero, 'nombre' => "Equipo $lote.$numero (DEMO)", 'estado' => 'activo']);
                    $equipo->integrantes()->attach($alumno->id, ['activo' => true]);
                    $proyecto = Proyecto::create(['guia_integradora_id' => $guia->id, 'equipo_id' => $equipo->id, 'titulo' => "Historial $lote.$numero - $descripcion (DEMO)", 'descripcion' => 'Proyecto y firmas ficticias para explorar el sistema.', 'estado' => 'en_proceso']);
                    foreach ([$lider, $docente] as $persona) {
                        $proyecto->docentes()->attach($persona->id, ['activo' => true, 'tipo_participacion' => 'evaluador']);
                    }
                    $proyecto->asignaturas()->attach($materia->id, ['docente_id' => $lider->id, 'participa_evaluacion' => true]);
                    foreach ($guia->apartados as $apartado) {
                        if ($apartado->orden > 1 && in_array($tipo, ['entregas', 'activa', 'vencida', 'cerrado'], true)) {
                            continue;
                        }
                        $entrega = $this->entrega($proyecto, $apartado, $alumno);
                        if ($tipo !== 'firma' || ! $apartado->requiere_codigo) {
                            $this->aprobar($apartado, $entrega);
                        }
                    }
                    $servicio = app(CierresProyectos::class);
                    $cierre = $servicio->registrar($proyecto->fresh());
                    $seleccion = $guia->apartados->where('orden', '>', 1)->pluck('id')->all();
                    if ($tipo === 'activa') {
                        $servicio->decidir($lider, $proyecto->fresh(), ['decision' => 'prorroga', 'ronda' => $cierre->ronda, 'apartados' => $seleccion, 'fecha_limite' => now()->addDays(7)->endOfDay()->toDateTimeString(), 'motivo' => 'DEMO: siete días para completar Desarrollo y Código.']);
                    }
                    if ($tipo === 'vencida') {
                        $fecha = now()->subDay()->endOfDay()->toDateTimeString();
                        Carbon::withTestNow(now()->subDays(4), fn () => $servicio->decidir($lider, $proyecto->fresh(), ['decision' => 'prorroga', 'ronda' => $cierre->ronda, 'apartados' => $seleccion, 'fecha_limite' => $fecha, 'motivo' => 'DEMO: prórroga que terminó con entregas pendientes.']));
                        $servicio->registrar($proyecto->fresh());
                    }
                    if ($tipo === 'cerrado') {
                        $servicio->decidir($lider, $proyecto->fresh(), ['decision' => 'cerrar', 'ronda' => $cierre->ronda, 'motivo' => 'DEMO: cierre con constancia de entregas faltantes.']);
                    }
                    if ($tipo === 'pdf') {
                        $request = Request::create('/demo', 'POST');
                        $request->setUserResolver(fn () => $coordinacion);
                        app(ControladorDocumentos::class)->generar($request, $proyecto->fresh(), app(DocumentosGuias::class));
                        $proyecto->update(['estado' => 'finalizado']);
                    }
                }
            }
        });
        $this->command?->info('Historial DEMO disponible: 4 guías, 28 proyectos y 4 PDF finales; se conserva al repetir la carga.');
    }
}
