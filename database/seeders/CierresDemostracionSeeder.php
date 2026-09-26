<?php

namespace Database\Seeders;

use App\Http\Controllers\Modulos\ControladorDocumentos;
use App\Models\ApartadoGuia;
use App\Models\ArchivoEntrega;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Entrega;
use App\Models\Equipo;
use App\Models\FirmaApartadoGuia;
use App\Models\FirmaDocente;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\ProductoCodigo;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use App\Servicios\CierresProyectos;
use App\Servicios\DocumentosGuias;
use App\Servicios\FirmasDocentes;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Casos locales de guías cerradas. Repetir la carga conserva las decisiones del usuario. */
class CierresDemostracionSeeder extends Seeder
{
    public const PERIODO = 'Cierres y prórrogas - Septiembre 2026 (DEMO)';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Los cierres ficticios solo se cargan en local o pruebas.');
        }
        DB::transaction(function () {
            if (Periodo::where('nombre', self::PERIODO)->exists()) {
                $this->command?->info('Los casos ya existen; se conservan sus prórrogas, decisiones y PDF.');

                return;
            }
            $carrera = Carrera::firstOrCreate(['clave' => 'TI'], ['nombre' => 'Tecnologías de la Información', 'estado' => 'activa']);
            $coordinacion = $this->usuario('DEMO-COORD', 'Coordinación de demostración', 'coordinacion', $carrera);
            $lider = $this->usuario('DEMO-LIDER-01', 'Elena Rivera (DEMO)', 'docente_lider', $carrera);
            $docente = $this->usuario('DEMO-DOC-01', 'Tomás Vega (DEMO)', 'docente_materia', $carrera);
            foreach ([$lider, $docente] as $persona) {
                $this->firma($persona);
            }
            $periodo = Periodo::create(['nombre' => self::PERIODO, 'fecha_inicio' => today()->subDays(95), 'fecha_fin' => today()->subDays(3), 'estado' => 'cerrado']);
            $materia = Asignatura::create(['clave' => 'DEMO-CIERRES-TI', 'nombre' => 'Integradora - Cierres (DEMO)', 'carrera_id' => $carrera->id, 'grado' => 8, 'estado' => 'activo']);
            foreach ([$lider, $docente] as $persona) {
                $materia->docentes()->attach($persona->id, ['periodo_id' => $periodo->id, 'activo' => true]);
            }
            $grupo = GrupoAcademico::create(['periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'grado' => 8, 'grupo' => 'CIERRES-DEMO', 'nombre' => '8 - Cierres y prórrogas (DEMO)', 'lider_proyecto_id' => $lider->id, 'asignatura_lider_id' => $materia->id]);
            $guias = [];
            foreach (['Pendientes de cierre', 'Prórrogas', 'PDF finalizados y constancias'] as $indice => $nombre) {
                $guia = GuiaIntegradora::create(['periodo_id' => $periodo->id, 'periodo_fin_id' => $periodo->id, 'asignatura_id' => $materia->id, 'creado_por' => $coordinacion->id, 'nombre' => "Guía cerrada - $nombre (DEMO)", 'cuatrimestre' => '8° cuatrimestre', 'version' => '1.0', 'estado' => 'cerrada', 'objetivo_aprendizaje' => 'DEMOSTRACIÓN: explorar cierres de guías, prórrogas por equipo y PDF archivados. Firmas y evidencias ficticias.', 'competencias_evaluar' => 'Diseñar, desarrollar y validar un proyecto integrador.']);
                foreach ([['Protocolo del proyecto', "Antecedentes\nProblema\nObjetivos\nAlcances y limitaciones", 35], ['Desarrollo y resultados', "Arquitectura\nImplementación\nPruebas\nResultados y conclusiones", 35], ['Código y liberación', "Código fuente\nManual técnico\nManual de usuario\nCarta de liberación", 30]] as $orden => [$titulo, $descripcion, $peso]) {
                    $apartado = ApartadoGuia::create(['guia_integradora_id' => $guia->id, 'orden' => $orden + 1, 'titulo' => $titulo, 'descripcion' => $descripcion, 'ponderacion' => $peso, 'requiere_documento' => true, 'requiere_codigo' => $orden === 2, 'fecha_limite' => today()->subDays(5)->setTime(23, 59)]);
                    foreach ($orden === 2 ? [$lider] : [$lider, $docente] as $firmaOrden => $persona) {
                        FirmaApartadoGuia::create(['apartado_guia_id' => $apartado->id, 'docente_id' => $persona->id, 'orden' => $firmaOrden + 1, 'etiqueta' => $persona->hasRole('docente_lider') ? 'Docente líder' : 'Docente de materia', 'requerida' => true]);
                    }
                }
                $guias[$indice] = $guia->load('apartados');
            }
            $casos = [
                ['CIERRE-ENTREGA', '01 - Cierre pendiente: faltan entregas (DEMO)', 0, 'entregas'],
                ['CIERRE-FIRMA', '02 - Cierre pendiente: falta firma de código (DEMO)', 0, 'firma'],
                ['PRORROGA-ACTIVA', '03 - Prórroga activa: resultados y código (DEMO)', 1, 'activa'],
                ['PRORROGA-VENCIDA', '04 - Prórroga vencida: requiere nueva decisión (DEMO)', 1, 'vencida'],
                ['PDF-CERRADO', '05 - Proyecto cerrado con PDF final firmado (DEMO)', 2, 'pdf'],
                ['PDF-PRORROGA', '06 - Prórroga cumplida y PDF final archivado (DEMO)', 2, 'pdf_prorroga'],
                ['CIERRE-PARCIAL', '07 - Cerrado con constancia de pendientes (DEMO)', 2, 'cerrado'],
            ];
            foreach ($casos as $numero => [$clave, $titulo, $indiceGuia, $tipo]) {
                $alumno = $this->usuario("DEMO-$clave-1", "Alumno de $clave (DEMO)", 'estudiante', $carrera, $grupo);
                $equipo = Equipo::create(['grupo_academico_id' => $grupo->id, 'lider_id' => $alumno->id, 'numero' => $numero + 1, 'nombre' => 'Equipo '.($numero + 1).' - Cierres (DEMO)', 'estado' => 'activo']);
                $equipo->integrantes()->attach($alumno->id, ['activo' => true]);
                $guia = $guias[$indiceGuia];
                $proyecto = Proyecto::create(['guia_integradora_id' => $guia->id, 'equipo_id' => $equipo->id, 'titulo' => $titulo, 'descripcion' => 'Caso ficticio para probar cierres y prórrogas. Las firmas no pertenecen a personas reales.', 'estado' => 'en_proceso']);
                foreach ([$lider, $docente] as $persona) {
                    $proyecto->docentes()->attach($persona->id, ['activo' => true, 'tipo_participacion' => 'evaluador']);
                }
                $proyecto->asignaturas()->attach($materia->id, ['docente_id' => $lider->id, 'participa_evaluacion' => true]);
                foreach ($guia->apartados as $apartado) {
                    if (in_array($tipo, ['entregas', 'cerrado', 'vencida'], true) && $apartado->orden > 1) {
                        continue;
                    }
                    $entrega = $this->entrega($proyecto, $apartado, $alumno);
                    if ($apartado->orden > 1 && in_array($tipo, ['activa', 'pdf_prorroga'], true)) {
                        app(FirmasDocentes::class)->guardarRevision($lider, $entrega, ['resultado' => 'correccion', 'calificacion' => 7, 'observaciones' => 'DEMO: agregar pruebas y actualizar las evidencias.'], false);

                        continue;
                    }
                    if ($tipo === 'firma' && $apartado->requiere_codigo) {
                        continue;
                    }
                    $this->aprobar($apartado, $entrega);
                }
                $servicio = app(CierresProyectos::class);
                $cierre = $servicio->registrar($proyecto->fresh());
                $seleccion = $guia->apartados->where('orden', '>', 1)->pluck('id')->all();
                if (in_array($tipo, ['activa', 'pdf_prorroga'], true)) {
                    $servicio->decidir($lider, $proyecto->fresh(), ['decision' => 'prorroga', 'ronda' => $cierre->ronda, 'apartados' => $seleccion, 'fecha_limite' => now()->addDays(7)->setTime(23, 59)->toDateTimeString(), 'motivo' => 'DEMO: abrir Desarrollo y resultados y Código y liberación durante siete días.']);
                }
                if ($tipo === 'vencida') {
                    $fecha = now()->subDay()->setTime(23, 59);
                    Carbon::withTestNow(now()->subDays(4), fn () => $servicio->decidir($lider, $proyecto->fresh(), ['decision' => 'prorroga', 'ronda' => $cierre->ronda, 'apartados' => $seleccion, 'fecha_limite' => $fecha->toDateTimeString(), 'motivo' => 'DEMO: prórroga anterior que ya venció.']));
                    $servicio->registrar($proyecto->fresh());
                }
                if ($tipo === 'cerrado') {
                    $servicio->decidir($lider, $proyecto->fresh(), ['decision' => 'cerrar', 'ronda' => $cierre->ronda, 'motivo' => 'DEMO: el líder cierra en su estado actual, dejando constancia de las dos entregas faltantes.']);
                }
                if ($tipo === 'pdf_prorroga') {
                    foreach ($guia->apartados->where('orden', '>', 1) as $apartado) {
                        $nueva = $this->entrega($proyecto, $apartado, $alumno, 2);
                        $this->aprobar($apartado, $nueva);
                    }
                }
                if (in_array($tipo, ['pdf', 'pdf_prorroga'], true)) {
                    $request = Request::create('/demo', 'POST');
                    $request->setUserResolver(fn () => $coordinacion);
                    app(ControladorDocumentos::class)->generar($request, $proyecto->fresh(), app(DocumentosGuias::class));
                    $proyecto->update(['estado' => 'finalizado']);
                }
            }
        });
        $this->command?->info('Cierres DEMO listos: 3 guías cerradas, 7 proyectos, prórrogas activa/vencida y 2 PDF finales.');
    }

    protected function usuario(string $matricula, string $nombre, string $rol, Carrera $carrera, ?GrupoAcademico $grupo = null): User
    {
        $role = Role::firstOrCreate(['nombre' => $rol], ['nombre_visible' => $rol]);

        return User::firstOrCreate(['matricula' => $matricula], ['nombre' => $nombre, 'correo' => strtolower($matricula).'@example.invalid', 'rol_id' => $role->id, 'carrera_id' => $carrera->id, 'grupo_academico_id' => $grupo?->id, 'estado' => 'activo', 'contrasena' => 'password', 'debe_cambiar_contrasena' => false]);
    }

    protected function firma(User $docente): void
    {
        if (FirmaDocente::where('docente_id', $docente->id)->exists()) {
            return;
        }
        $imagen = imagecreatetruecolor(500, 120);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 255, 255, 255));
        imagestring($imagen, 5, 30, 45, 'FIRMA FICTICIA / DEMO', imagecolorallocate($imagen, 25, 45, 90));
        ob_start();
        imagepng($imagen);
        $bytes = ob_get_clean();
        imagedestroy($imagen);
        $normal = app(FirmasDocentes::class)->normalizar($bytes);
        FirmaDocente::create(['docente_id' => $docente->id, 'imagen' => $normal, 'sha256' => hash('sha256', base64_decode($normal))]);
    }

    protected function entrega(Proyecto $proyecto, ApartadoGuia $apartado, User $alumno, int $version = 1): Entrega
    {
        $entrega = Entrega::create(['proyecto_id' => $proyecto->id, 'equipo_id' => $proyecto->equipo_id, 'apartado_guia_id' => $apartado->id, 'entregado_por_id' => $alumno->id, 'version' => $version, 'estado' => 'enviada', 'entregado_en' => $version === 1 ? now()->subDays(6) : now()]);
        $contenido = "EVIDENCIA FICTICIA / DEMO\n{$proyecto->titulo}\n{$apartado->titulo}\nVersión $version\n".($version > 1 ? 'Correcciones atendidas durante la prórroga.' : 'Documento de demostración.')."\n";
        $ruta = "demo-cierres/proyecto-{$proyecto->id}/apartado-{$apartado->id}-v$version.txt";
        Storage::disk('local')->put($ruta, $contenido);
        ArchivoEntrega::create(['entrega_id' => $entrega->id, 'nombre_original' => "evidencia-cierre-demo-v$version.txt", 'ruta' => $ruta, 'tipo_archivo' => 'text/plain', 'tamano' => strlen($contenido)]);
        if ($apartado->requiere_codigo) {
            ProductoCodigo::create(['proyecto_id' => $proyecto->id, 'entrega_id' => $entrega->id, 'version' => "v$version", 'repositorio_url' => null, 'demostracion_url' => null]);
        }

        return $entrega;
    }

    protected function aprobar(ApartadoGuia $apartado, Entrega $entrega): void
    {
        foreach ($apartado->firmas as $firma) {
            app(FirmasDocentes::class)->guardarRevision($firma->docente, $entrega, ['resultado' => 'aprobada', 'calificacion' => 9.5, 'observaciones' => 'DEMO: apartado aprobado y firmado con firma ficticia.'], true);
        }
    }
}
