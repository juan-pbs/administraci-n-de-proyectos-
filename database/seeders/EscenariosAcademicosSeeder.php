<?php

namespace Database\Seeders;

use App\Http\Controllers\Modulos\ControladorDocumentos;
use App\Models\{ApartadoGuia, ArchivoEntrega, Asignatura, Carrera, ComentarioRevision, EncargoProyecto, Entrega, Equipo, FirmaApartadoGuia, FirmaDocente, GrupoAcademico, GuiaIntegradora, Periodo, ProductoCodigo, Proyecto, Revision, Role, User};
use App\Servicios\{CierresProyectos, DocumentosGuias, FirmasDocentes};
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{DB, Storage};
use RuntimeException;

/** Datos ficticios reproducibles, con nombres normales y relaciones académicas completas. */
class EscenariosAcademicosSeeder extends Seeder
{
    public const REPOSITORIOS = ['https://github.com/juan-pbs/comedor', 'https://github.com/juan-pbs/e-support-system',
        'https://github.com/juan-pbs/equipo_dinamita', 'https://github.com/juan-pbs/universidad-'];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Los datos de práctica solo se cargan en local o pruebas.');
        }
        if (User::exists()) {
            $this->command?->info('La base ya contiene usuarios; usa datos:reiniciar para reemplazarla con respaldo.');
            return;
        }
        DB::transaction(function () {
            foreach (['coordinacion' => 'Coordinación', 'docente_lider' => 'Docente líder', 'docente_materia' => 'Docente de materia', 'estudiante' => 'Alumno'] as $nombre => $visible) {
                Role::firstOrCreate(['nombre' => $nombre], ['nombre_visible' => $visible]);
            }
            $coordinacion = $this->usuario('20260001', 'Patricia Hernández Luna', 'coordinacion');
            $ciclos = [
                ['Enero - Abril 2025', today()->subMonths(20)->startOfMonth(), today()->subMonths(17)->endOfMonth(), 'cerrado', 'historico'],
                ['Mayo - Agosto 2025', today()->subMonths(16)->startOfMonth(), today()->subMonths(13)->endOfMonth(), 'cerrado', 'historico'],
                ['Mayo - Agosto 2026', today()->subMonths(4)->startOfMonth(), today()->subMonth()->endOfMonth(), 'cerrado', 'cierre'],
                ['Septiembre - Diciembre 2026', today()->startOfMonth(), today()->addMonths(3)->endOfMonth(), 'activo', 'actual'],
                ['Enero - Abril 2027', today()->addMonths(4)->startOfMonth(), today()->addMonths(7)->endOfMonth(), 'borrador', 'borrador'],
            ];
            $personas = [
                ['TI', 'Tecnologías de la Información', ['Elena Rivera Soto', 'Carlos Mendoza Ruiz'], ['Tomás Vega López', 'Lucía Montes García', 'Ana Torres Salas']],
                ['MEC', 'Mecatrónica', ['Sofía Ortega Cruz', 'Daniel Castillo Pérez'], ['Roberto Silva León', 'Mariana Ríos Vargas', 'Luis Navarro Díaz']],
            ];
            foreach ($personas as [$clave, $nombre, $nombresLideres, $nombresDocentes]) {
                $carrera = Carrera::create(['clave' => $clave, 'nombre' => $nombre, 'estado' => 'activa']);
                $lideres = collect($nombresLideres)->map(fn ($n, $i) => $this->usuario("DOC-$clave-0".($i + 1), $n, 'docente_lider', $carrera));
                $docentes = collect($nombresDocentes)->map(fn ($n, $i) => $this->usuario("DOC-$clave-0".($i + 3), $n, 'docente_materia', $carrera));
                foreach ($lideres->merge($docentes) as $docente) {
                    $carrera->docentes()->attach($docente->id, ['activo' => true]);
                }
                foreach ($lideres->merge($docentes->take(2)) as $docente) {
                    $this->firma($docente);
                }
                $inactivo = $this->usuario("DOC-$clave-06", $clave === 'TI' ? 'Jorge Martínez Flores' : 'Adriana Luna Reyes', 'docente_materia', $carrera);
                $inactivo->update(['estado' => 'inactivo']);
                $carrera->docentes()->attach($inactivo->id, ['activo' => false]);
                $materias = collect(['Integradora', 'Desarrollo de proyectos', 'Comunicación técnica'])->map(fn ($n, $i) => Asignatura::create(['carrera_id' => $carrera->id, 'clave' => "$clave-08-".($i + 1), 'nombre' => $n, 'grado' => 8, 'estado' => 'activo']));
                foreach ($ciclos as $ciclo => [$nombrePeriodo, $inicio, $fin, $estado, $tipo]) {
                    $periodo = Periodo::firstOrCreate(['nombre' => $nombrePeriodo], ['fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'estado' => $estado]);
                    foreach ($lideres as $lider) {
                        $materias[0]->docentes()->attach($lider->id, ['periodo_id' => $periodo->id, 'activo' => true]);
                    }
                    foreach ($docentes as $i => $docente) {
                        $materias[$i === 0 ? 1 : 2]->docentes()->attach($docente->id, ['periodo_id' => $periodo->id, 'activo' => true]);
                    }
                    EncargoProyecto::create(['encargado_id' => $coordinacion->id, 'periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'cuatrimestre' => 8, 'activo' => true]);
                    foreach ($lideres as $g => $lider) {
                        $docente = $docentes[$g];
                        $grupo = GrupoAcademico::create(['periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'grado' => 8, 'grupo' => $g === 0 ? 'A' : 'B', 'nombre' => '8'.($g === 0 ? 'A' : 'B'), 'lider_proyecto_id' => $lider->id, 'asignatura_lider_id' => $materias[0]->id]);
                        $guia = $this->guia($periodo, $materias, $coordinacion, $lider, $docente, $tipo, $g);
                        $tipos = match ($tipo) {
                            'historico' => ['pdf', 'pdf_corregido', 'cerrado'],
                            'cierre' => ['entregas', 'firma', 'lista_pdf', 'activa', 'vencida', 'pdf', 'cerrado'],
                            'actual' => ['sin_entregas', 'parcial', 'correccion', 'rechazada', 'sin_firma', 'lista_pdf', 'pdf'],
                            default => ['borrador'],
                        };
                        foreach ($tipos as $e => $caso) {
                            $this->proyecto($grupo, $guia, $materias, $coordinacion, $lider, $docente, $docentes[2], $caso, $ciclo, $g, $e);
                        }
                        // Alumnos disponibles para formar equipos y un alumno inactivo.
                        foreach (['activo', 'activo', 'inactivo'] as $libre => $estatus) {
                            $alumno = $this->usuario("$clave".($ciclo + 1).($g + 1).'99'.($libre + 1), $this->nombreAlumno(90 + $ciclo * 7 + $g * 3 + $libre), 'estudiante', $carrera, $grupo);
                            $alumno->update(['estado' => $estatus]);
                        }
                        Equipo::create(['grupo_academico_id' => $grupo->id, 'numero' => count($tipos) + 1, 'nombre' => 'Equipo '.(count($tipos) + 1), 'estado' => 'activo']);
                    }
                }
            }
        });
        $this->command?->info('Escenarios académicos cargados. Contraseña de práctica: password.');
    }

    private function usuario(string $matricula, string $nombre, string $rol, ?Carrera $carrera = null, ?GrupoAcademico $grupo = null): User
    {
        return User::create(['matricula' => $matricula, 'nombre' => $nombre, 'correo' => strtolower($matricula).'@example.invalid', 'rol_id' => Role::where('nombre', $rol)->value('id'), 'carrera_id' => $carrera?->id, 'grupo_academico_id' => $grupo?->id, 'estado' => 'activo', 'contrasena' => 'password', 'debe_cambiar_contrasena' => false]);
    }

    private function nombreAlumno(int $numero): string
    {
        $nombres = ['Valeria', 'Diego', 'Camila', 'Mateo', 'Ximena', 'Emiliano', 'Fernanda', 'Santiago', 'Regina', 'Leonardo', 'Daniela', 'Sebastián'];
        $apellidos = ['García', 'López', 'Hernández', 'Pérez', 'Torres', 'Ramírez', 'Flores', 'Sánchez', 'Cruz', 'Reyes', 'Morales', 'Vargas', 'Navarro'];
        return $nombres[$numero % 12].' '.$apellidos[(int) floor($numero / 12) % 13].' '.$apellidos[($numero + 4) % 13];
    }

    private function firma(User $docente): void
    {
        $image = imagecreatetruecolor(600, 150);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        $ink = imagecolorallocate($image, 30, 65, 115);
        for ($x = 30; $x < 470; $x++) {
            $y = (int) (65 + sin($x / (11 + $docente->id % 7)) * 23 + cos($x / 31) * 12);
            imageline($image, $x, $y, $x + 1, (int) (65 + sin(($x + 1) / (11 + $docente->id % 7)) * 23 + cos(($x + 1) / 31) * 12), $ink);
        }
        imageline($image, 25, 96, 540, 85, $ink);
        imagestring($image, 3, 30, 120, 'Firma de practica', $ink);
        ob_start(); imagepng($image); $bytes = ob_get_clean(); imagedestroy($image);
        $normal = app(FirmasDocentes::class)->normalizar($bytes);
        FirmaDocente::create(['docente_id' => $docente->id, 'imagen' => $normal, 'sha256' => hash('sha256', base64_decode($normal))]);
    }

    private function guia(Periodo $periodo, $materias, User $coord, User $lider, User $docente, string $tipo, int $grupo): GuiaIntegradora
    {
        $guia = GuiaIntegradora::create(['periodo_id' => $periodo->id, 'periodo_fin_id' => $periodo->id, 'asignatura_id' => $materias[0]->id, 'creado_por' => $coord->id,
            'nombre' => 'Guía integradora '.$materias[0]->carrera->clave.' 8'.($grupo === 0 ? 'A' : 'B').' - '.$periodo->nombre, 'cuatrimestre' => '8', 'version' => '1.0',
            'estado' => $tipo === 'borrador' ? 'borrador' : ($periodo->estado === 'cerrado' ? 'cerrada' : 'publicada'),
            'objetivo_aprendizaje' => 'Diseñar e implementar una solución tecnológica a una necesidad de la comunidad.', 'competencias_evaluar' => 'Análisis, diseño, implementación, pruebas y documentación de proyectos.']);
        foreach ([['Protocolo del proyecto', "Antecedentes\nProblema\nObjetivos\nJustificación\nAlcances y limitaciones", 30], ['Desarrollo y resultados', "Arquitectura\nImplementación\nPruebas\nResultados\nConclusiones", 35], ['Código y liberación', "Código fuente\nManual técnico\nManual de usuario\nCarta de liberación", 35]] as $orden => [$titulo, $descripcion, $peso]) {
            $fecha = $periodo->estado === 'cerrado' ? Carbon::parse($periodo->fecha_fin)->subDays(3)->endOfDay() : now()->addDays(($orden + 1) * 7)->endOfDay();
            $apartado = ApartadoGuia::create(['guia_integradora_id' => $guia->id, 'orden' => $orden + 1, 'titulo' => $titulo, 'descripcion' => $descripcion, 'ponderacion' => $peso, 'requiere_documento' => true, 'requiere_codigo' => $orden === 2, 'fecha_limite' => $fecha]);
            foreach ($orden === 2 ? [$lider] : [$lider, $docente] as $i => $revisor) {
                FirmaApartadoGuia::create(['apartado_guia_id' => $apartado->id, 'docente_id' => $revisor->id, 'asignatura_id' => $materias[$i]->id, 'orden' => $i + 1, 'etiqueta' => $i === 0 ? 'Docente integrador' : 'Docente asesor', 'requerida' => true]);
            }
            $apartado->asignaturasContribuyentes()->attach($materias[0]->id, ['rol_contribucion' => 'Integración', 'requiere_firma' => true]);
            if ($orden !== 2) {
                $apartado->asignaturasContribuyentes()->attach($materias[1]->id, ['rol_contribucion' => 'Desarrollo', 'requiere_firma' => true]);
            }
        }
        return $guia->load('apartados.firmas.docente');
    }

    private function proyecto(GrupoAcademico $grupo, GuiaIntegradora $guia, $materias, User $coord, User $lider, User $docente, User $sinFirma, string $caso, int $ciclo, int $g, int $e): void
    {
        $alumnos = collect();
        for ($i = 0; $i < 3; $i++) {
            $matricula = $materias[0]->carrera->clave.($ciclo + 1).($g + 1).str_pad((string) ($e + 1), 2, '0', STR_PAD_LEFT).($i + 1);
            $alumnos->push($this->usuario($matricula, $this->nombreAlumno($ciclo * 40 + $g * 21 + $e * 3 + $i), 'estudiante', $materias[0]->carrera, $grupo));
        }
        $equipo = Equipo::create(['grupo_academico_id' => $grupo->id, 'lider_id' => $alumnos[0]->id, 'numero' => $e + 1, 'nombre' => 'Equipo '.($e + 1), 'estado' => 'activo', 'contexto_proyecto' => 'Proyecto comunitario']);
        foreach ($alumnos as $alumno) { $equipo->integrantes()->attach($alumno->id, ['activo' => true]); }
        $equipo->asesores()->attach($lider->id, ['principal' => true, 'activo' => true]);
        $equipo->asesores()->attach($docente->id, ['principal' => false, 'activo' => true]);
        $titulos = ['Comedor universitario', 'Mesa de ayuda escolar', 'Gestión de laboratorios', 'Portal de servicios', 'Biblioteca digital', 'Reservas de talleres', 'Seguimiento de tutorías'];
        $proyecto = Proyecto::create(['guia_integradora_id' => $guia->id, 'equipo_id' => $equipo->id, 'titulo' => $titulos[$e % 7].' - '.$grupo->carrera->clave.' '.$grupo->nombre, 'descripcion' => 'Desarrollo de una solución para mejorar los servicios de la comunidad universitaria.', 'estado' => 'en_proceso']);
        foreach ([$lider, $docente, $sinFirma] as $revisor) { $proyecto->docentes()->attach($revisor->id, ['activo' => true, 'tipo_participacion' => 'evaluador']); }
        foreach ($materias as $i => $materia) { $proyecto->asignaturas()->attach($materia->id, ['docente_id' => [$lider, $docente, $sinFirma][$i]->id, 'participa_evaluacion' => true]); }
        if ($caso === 'sin_firma') {
            // Guía propia para no modificar los requisitos de otros equipos.
            $nueva = $this->guia($guia->periodo, $materias, $coord, $lider, $sinFirma, 'actual', $g);
            $nueva->update(['nombre' => $nueva->nombre.' - Comunicación técnica']);
            $proyecto->update(['guia_integradora_id' => $nueva->id]);
            $proyecto->asignaturas()->updateExistingPivot($materias[1]->id, ['docente_id' => $sinFirma->id]);
            $guia = $nueva;
        }
        foreach ($guia->apartados as $apartado) {
            if (in_array($caso, ['sin_entregas', 'borrador'], true) || ($apartado->orden > 1 && in_array($caso, ['entregas', 'parcial', 'activa', 'vencida', 'cerrado'], true))) { continue; }
            $entrega = $this->entrega($proyecto, $apartado, $alumnos[($apartado->orden - 1) % 3], $e);
            if ($caso === 'firma' && $apartado->requiere_codigo) { continue; }
            if (in_array($caso, ['correccion', 'rechazada', 'pdf_corregido'], true) && $apartado->orden === 2) {
                $this->revisar($docente, $entrega, $caso === 'rechazada' ? 'rechazada' : 'correccion');
                if ($caso !== 'pdf_corregido') { continue; }
                $entrega = $this->entrega($proyecto, $apartado, $alumnos[2], $e, 2);
            }
            foreach ($apartado->firmas as $slot) {
                if (FirmaDocente::where('docente_id', $slot->docente_id)->exists()) { $this->revisar($slot->docente, $entrega, 'aprobada'); }
            }
        }
        $servicio = app(CierresProyectos::class);
        $cierre = $servicio->registrar($proyecto->fresh());
        if (in_array($caso, ['activa', 'vencida'], true)) {
            $datos = ['decision' => 'prorroga', 'ronda' => $cierre->ronda, 'apartados' => $guia->apartados->where('orden', '>', 1)->pluck('id')->all(), 'fecha_limite' => now()->addDays(7)->endOfDay()->toDateTimeString(), 'motivo' => 'Completar resultados y liberar el código con las evidencias pendientes.'];
            if ($caso === 'vencida') {
                $datos['fecha_limite'] = now()->subDay()->endOfDay()->toDateTimeString();
                Carbon::withTestNow(now()->subDays(4), fn () => $servicio->decidir($lider, $proyecto->fresh(), $datos));
                $servicio->registrar($proyecto->fresh());
            } else { $servicio->decidir($lider, $proyecto->fresh(), $datos); }
        }
        if ($caso === 'cerrado') { $servicio->decidir($lider, $proyecto->fresh(), ['decision' => 'cerrar', 'ronda' => $cierre->ronda, 'motivo' => 'Cierre autorizado conservando constancia de las entregas faltantes.']); }
        if (in_array($caso, ['pdf', 'pdf_corregido'], true)) {
            $request = Request::create('/datos', 'POST'); $request->setUserResolver(fn () => $coord);
            app(ControladorDocumentos::class)->generar($request, $proyecto->fresh(), app(DocumentosGuias::class));
            $proyecto->update(['estado' => 'finalizado']);
        }
    }

    private function entrega(Proyecto $proyecto, ApartadoGuia $apartado, User $alumno, int $indice, int $version = 1): Entrega
    {
        $fecha = $proyecto->guiaIntegradora->periodo->estado === 'cerrado' ? Carbon::parse($apartado->fecha_limite)->subDay() : now()->subDays(2);
        $entrega = Entrega::create(['proyecto_id' => $proyecto->id, 'equipo_id' => $proyecto->equipo_id, 'apartado_guia_id' => $apartado->id, 'entregado_por_id' => $alumno->id, 'version' => $version, 'estado' => 'enviada', 'entregado_en' => $fecha]);
        $contenido = "# {$proyecto->titulo}\n\n{$apartado->titulo}\n\nVersión $version\n\nObjetivo: mejorar los servicios escolares.\nResultados: documentación del análisis, implementación y pruebas.\n";
        $ruta = "escenarios/proyecto-{$proyecto->id}/apartado-{$apartado->id}-v$version.md";
        Storage::disk('local')->put($ruta, $contenido);
        ArchivoEntrega::create(['entrega_id' => $entrega->id, 'nombre_original' => "informe-v$version.md", 'ruta' => $ruta, 'tipo_archivo' => 'text/markdown', 'tamano' => strlen($contenido)]);
        if ($apartado->requiere_codigo) {
            $repo = self::REPOSITORIOS[$indice % count(self::REPOSITORIOS)];
            ProductoCodigo::create(['proyecto_id' => $proyecto->id, 'entrega_id' => $entrega->id, 'version' => "v$version", 'repositorio_url' => $repo, 'demostracion_url' => null]);
            $rutaZip = "escenarios/proyecto-{$proyecto->id}/codigo-v$version.zip";
            $zip = new \ZipArchive;
            $zip->open(Storage::disk('local')->path($rutaZip), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFromString('README.md', "Paquete de documentación del proyecto.\nRepositorio de referencia: $repo\n");
            $zip->addFromString('manual.md', $contenido);
            $zip->close();
            ArchivoEntrega::create(['entrega_id' => $entrega->id, 'nombre_original' => "documentacion-codigo-v$version.zip", 'ruta' => $rutaZip, 'tipo_archivo' => 'application/zip', 'tamano' => Storage::disk('local')->size($rutaZip)]);
            if ($indice % 3 === 0) {
                $this->aplicacion($entrega);
            }
        }
        return $entrega;
    }

    /** Paquete Debian de práctica válido, sin ejecutables externos ni una aplicación atribuida al repositorio. */
    private function aplicacion(Entrega $entrega): void
    {
        $base = Storage::disk('local')->path('escenarios/proyecto-'.$entrega->proyecto_id.'/paquete-'.$entrega->id);
        $control = new \PharData($base.'-control.tar');
        $control->addFromString('control', "Package: practica-escolar\nVersion: 1.0\nArchitecture: all\nMaintainer: Escuela <escuela@example.invalid>\nDescription: Paquete de practica para revisar adjuntos escolares\n");
        $control->compress(\Phar::GZ);
        $data = new \PharData($base.'-data.tar');
        $data->addFromString('usr/share/doc/practica-escolar/README', "Paquete de práctica para explorar la revisión de archivos. No representa una versión compilada del repositorio enlazado.\n");
        $data->compress(\Phar::GZ);
        $ar = "!<arch>\n";
        foreach (['debian-binary' => "2.0\n", 'control.tar.gz' => file_get_contents($base.'-control.tar.gz'), 'data.tar.gz' => file_get_contents($base.'-data.tar.gz')] as $nombre => $bytes) {
            $ar .= str_pad($nombre.'/', 16).str_pad('0', 12).str_pad('0', 6).str_pad('0', 6).str_pad('100644', 8).str_pad((string) strlen($bytes), 10)."`\n".$bytes.(strlen($bytes) % 2 ? "\n" : '');
        }
        unset($control, $data);
        foreach (['-control.tar', '-control.tar.gz', '-data.tar', '-data.tar.gz'] as $sufijo) { unlink($base.$sufijo); }
        $ruta = 'escenarios/proyecto-'.$entrega->proyecto_id.'/practica-escolar-v'.$entrega->version.'.deb';
        Storage::disk('local')->put($ruta, $ar);
        ArchivoEntrega::create(['entrega_id' => $entrega->id, 'nombre_original' => 'practica-escolar.deb', 'ruta' => $ruta, 'tipo_archivo' => 'application/vnd.debian.binary-package', 'tamano' => strlen($ar), 'es_aplicacion' => true]);
    }

    private function revisar(User $docente, Entrega $entrega, string $resultado): void
    {
        $datos = ['resultado' => $resultado, 'calificacion' => $resultado === 'aprobada' ? 9.5 : 6, 'observaciones' => $resultado === 'aprobada' ? 'Evidencias revisadas y aprobadas.' : 'Actualizar las pruebas, explicar los resultados y atender los comentarios.'];
        Carbon::withTestNow($entrega->entregado_en->copy()->addHours(12), fn () => app(FirmasDocentes::class)->guardarRevision($docente, $entrega, $datos, $resultado === 'aprobada'));
        $revision = Revision::where('entrega_id', $entrega->id)->where('revisor_id', $docente->id)->firstOrFail();
        ComentarioRevision::create(['revision_id' => $revision->id, 'autor_id' => $docente->id, 'comentario' => $datos['observaciones'], 'visible_estudiante' => true]);
    }
}
