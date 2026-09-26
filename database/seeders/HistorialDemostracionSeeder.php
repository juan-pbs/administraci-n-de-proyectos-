<?php

namespace Database\Seeders;

use App\Models\ApartadoGuia;
use App\Models\ArchivoEntrega;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\ComentarioRevision;
use App\Models\DocumentoFinal;
use App\Models\Entrega;
use App\Models\Equipo;
use App\Models\FirmaApartadoGuia;
use App\Models\FirmaDocente;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\ProductoCodigo;
use App\Models\Proyecto;
use App\Models\Revision;
use App\Models\Role;
use App\Models\User;
use App\Servicios\DocumentosGuias;
use App\Servicios\FirmasDocentes;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Datos ficticios aislados: no reemplaza usuarios, firmas ni proyectos existentes. */
class HistorialDemostracionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('El historial ficticio solo puede cargarse en local o en pruebas.');
        }

        $carrera = Carrera::query()->firstOrCreate(['clave' => 'TI'], ['nombre' => 'Tecnologías de la Información', 'estado' => 'activa']);
        $coordinacion = $this->usuario('DEMO-COORD', 'Coordinación de demostración', 'coordinacion', $carrera);
        $docentes = [
            $this->usuario('DEMO-LIDER-01', 'Elena Rivera (DEMO)', 'docente_lider', $carrera),
            $this->usuario('DEMO-DOC-01', 'Tomás Vega (DEMO)', 'docente_materia', $carrera),
            $this->usuario('DEMO-DOC-02', 'Lucía Montes (DEMO)', 'docente_materia', $carrera),
        ];
        foreach ($docentes as $indice => $docente) {
            $carrera->docentes()->syncWithoutDetaching([$docente->id => ['activo' => true]]);
            if (! FirmaDocente::query()->where('docente_id', $docente->id)->exists()) {
                $imagen = $this->firmaFicticia($indice);
                FirmaDocente::query()->create(['docente_id' => $docente->id, 'imagen' => $imagen, 'sha256' => hash('sha256', base64_decode($imagen, true))]);
            }
        }

        foreach ([2024, 2025] as $anio) {
            foreach ([['Enero - Abril', '01-08', '04-26'], ['Mayo - Agosto', '05-06', '08-23'], ['Septiembre - Diciembre', '09-02', '12-13']] as $indice => [$nombre, $inicio, $fin]) {
                DB::transaction(function () use ($anio, $indice, $nombre, $inicio, $fin, $carrera, $coordinacion, $docentes): void {
                    $periodo = Periodo::query()->firstOrCreate(['nombre' => "$nombre $anio (DEMO)"], [
                        'fecha_inicio' => "$anio-$inicio", 'fecha_fin' => "$anio-$fin", 'estado' => 'cerrado',
                    ]);
                    $this->ciclo($periodo, $carrera, $coordinacion, $docentes, "$anio-".($indice + 1), Carbon::parse($periodo->fecha_inicio), false);
                });
            }
        }

        // Conserva el periodo activo del usuario y agrega casos de avance al mismo.
        $activo = Periodo::query()->where('estado', 'activo')->latest('fecha_inicio')->firstOrFail();
        DB::transaction(fn () => $this->ciclo($activo, $carrera, $coordinacion, $docentes, 'ACTUAL', Carbon::today()->subDays(42), true));
        $this->command?->info('Historial DEMO cargado: 6 periodos cerrados, 7 guías, 28 proyectos y 25 PDF finales. Contraseña inicial de cuentas nuevas: password.');
    }

    private function usuario(string $matricula, string $nombre, string $rol, Carrera $carrera, ?GrupoAcademico $grupo = null): User
    {
        return User::query()->firstOrCreate(['matricula' => $matricula], [
            'nombre' => $nombre, 'correo' => strtolower($matricula).'@example.invalid',
            'rol_id' => Role::query()->where('nombre', $rol)->firstOrFail()->id,
            'carrera_id' => $carrera->id, 'grupo_academico_id' => $grupo?->id,
            'estado' => 'activo', 'contrasena' => 'password', 'debe_cambiar_contrasena' => false,
        ]);
    }

    private function ciclo(Periodo $periodo, Carrera $carrera, User $coordinacion, array $docentes, string $clave, Carbon $inicio, bool $actual): void
    {
        $nombre = "Guía TI 8 - $clave (DEMO)";
        // Cada ciclo es atómico. Al repetir la carga se conservan las revisiones del usuario.
        if (GuiaIntegradora::query()->where('nombre', $nombre)->where('periodo_id', $periodo->id)->exists()) {
            return;
        }

        Carbon::withTestNow($inicio, function () use ($periodo, $carrera, $coordinacion, $docentes, $clave, $inicio, $actual, $nombre): void {
            $materias = [];
            foreach (['Integradora', 'Ingeniería de software', 'Bases de datos'] as $indice => $materia) {
                $materias[] = $asignatura = Asignatura::query()->create([
                    'clave' => "DEMO-$clave-".($indice + 1), 'nombre' => "$materia (DEMO $clave)",
                    'carrera_id' => $carrera->id, 'grado' => 8, 'estado' => 'activo',
                ]);
                $asignatura->docentes()->attach($docentes[$indice]->id, ['periodo_id' => $periodo->id, 'activo' => true]);
            }
            $guia = GuiaIntegradora::query()->create([
                'periodo_id' => $periodo->id, 'periodo_fin_id' => $periodo->id,
                'asignatura_id' => $materias[0]->id, 'creado_por' => $coordinacion->id, 'nombre' => $nombre,
                'cuatrimestre' => '8° cuatrimestre', 'version' => '1.0', 'estado' => $actual ? 'publicada' : 'cerrada',
                'competencias_evaluar' => 'DEMOSTRACIÓN: analizar necesidades, diseñar una solución, desarrollar software y documentar su validación.',
                'objetivo_aprendizaje' => 'Ejemplo ficticio de seguimiento académico completo. Las personas, evidencias y firmas son datos de demostración.',
            ]);
            $apartados = [];
            foreach ($this->apartados() as $indice => [$titulo, $descripcion, $ponderacion]) {
                $codigo = $indice === 4;
                $apartados[] = $apartado = ApartadoGuia::query()->create([
                    'guia_integradora_id' => $guia->id, 'orden' => $indice + 1, 'titulo' => $titulo,
                    'descripcion' => $descripcion, 'fecha_limite' => $inicio->copy()->addDays(($indice + 1) * 18),
                    'ponderacion' => $ponderacion, 'requiere_documento' => true, 'requiere_codigo' => $codigo,
                ]);
                foreach ($codigo ? [0] : [0, 1, 2] as $firmante) {
                    FirmaApartadoGuia::query()->create([
                        'apartado_guia_id' => $apartado->id, 'docente_id' => $docentes[$firmante]->id,
                        'asignatura_id' => $materias[$firmante]->id, 'orden' => $firmante + 1,
                        'etiqueta' => $firmante === 0 ? 'Docente integrador' : $materias[$firmante]->nombre, 'requerida' => true,
                    ]);
                    $apartado->asignaturasContribuyentes()->attach($materias[$firmante]->id, [
                        'rol_contribucion' => $materias[$firmante]->nombre, 'requiere_firma' => true,
                    ]);
                }
            }

            foreach (['D', 'E'] as $indiceGrupo => $letra) {
                $grupo = GrupoAcademico::query()->create([
                    'periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'grado' => 8,
                    'grupo' => $letra.'-DEMO', 'nombre' => "8$letra (DEMO)",
                    'lider_proyecto_id' => $docentes[0]->id, 'asignatura_lider_id' => $materias[0]->id,
                ]);
                for ($numero = 1; $numero <= 2; $numero++) {
                    $integrantes = [];
                    foreach (['Andrea Solís', 'Diego Luna', 'Paola Torres'] as $indice => $alumno) {
                        $integrantes[] = $this->usuario("DEMO-$clave-$letra$numero-".($indice + 1), "$alumno (DEMO $clave $letra$numero)", 'estudiante', $carrera, $grupo);
                    }
                    $equipo = Equipo::query()->create([
                        'grupo_academico_id' => $grupo->id, 'lider_id' => $integrantes[0]->id, 'numero' => $numero,
                        'nombre' => "Equipo $numero (DEMO)", 'estado' => 'activo', 'contexto_proyecto' => 'Caso ficticio de aplicación académica.',
                    ]);
                    $equipo->integrantes()->attach(collect($integrantes)->pluck('id')->mapWithKeys(fn ($id) => [$id => ['activo' => true]])->all());
                    $equipo->asesores()->attach(collect($docentes)->mapWithKeys(fn ($docente, $indice) => [$docente->id => ['principal' => $indice === 0, 'activo' => true]])->all());
                    $caso = $indiceGrupo * 2 + $numero;
                    $terminado = ! $actual || $caso === 1;
                    $tema = ['Biblioteca digital', 'Control de inventario', 'Reservas de laboratorios', 'Seguimiento de tutorías'][$caso - 1];
                    $proyecto = Proyecto::query()->create([
                        'guia_integradora_id' => $guia->id, 'equipo_id' => $equipo->id, 'titulo' => "$tema - $clave (DEMO)",
                        'descripcion' => 'Proyecto ficticio para explorar entregas, correcciones, aprobaciones y archivo de PDF. Sin repositorio ni demostración real.',
                        'estado' => $terminado ? 'finalizado' : 'en_proceso',
                    ]);
                    foreach ($materias as $indice => $materia) {
                        $proyecto->asignaturas()->attach($materia->id, ['docente_id' => $docentes[$indice]->id, 'participa_evaluacion' => true]);
                        $proyecto->docentes()->attach($docentes[$indice]->id, ['tipo_participacion' => $indice === 0 ? 'asesor_evaluador' : 'evaluador', 'activo' => true]);
                    }
                    foreach ($apartados as $indice => $apartado) {
                        $fecha = $inicio->copy()->addDays(($indice + 1) * ($actual ? 7 : 18) - 2)->setTime(10, 30);
                        $firmantes = $apartado->requiere_codigo ? [$docentes[0]] : $docentes;
                        foreach ($firmantes as $docente) {
                            DB::table('asignaciones_revision')->insert([
                                'proyecto_id' => $proyecto->id, 'apartado_guia_id' => $apartado->id, 'revisor_id' => $docente->id,
                                'tipo_revisor' => 'docente_asesor', 'fecha_inicio' => $inicio->toDateString(),
                                'fecha_fin' => $apartado->fecha_limite->toDateString(), 'activo' => true,
                                'creado_en' => $inicio, 'actualizado_en' => $inicio,
                            ]);
                        }
                        if ($actual && $caso === 4 && $indice >= 3) {
                            continue;
                        }
                        $corregida = $indice === 1 && $numero === 2;
                        if ($corregida) {
                            $anterior = $this->entrega($proyecto, $apartado, $equipo, 1, $fecha->copy()->subDays(3));
                            $this->revisar($anterior, $docentes[1], 'correccion', 6.5, $fecha->copy()->subDays(2));
                        }
                        $entrega = $this->entrega($proyecto, $apartado, $equipo, $corregida ? 2 : 1, $fecha);
                        if ($actual && $caso === 2 && $indice === 4) {
                            continue;
                        }
                        if ($actual && $caso === 3 && $indice === 4) {
                            $this->revisar($entrega, $docentes[0], 'correccion', 7.2, $fecha->copy()->addDay());

                            continue;
                        }
                        foreach ($firmantes as $docente) {
                            $this->revisar($entrega, $docente, 'aprobada', 8.5 + (($caso + $indice) % 5) * 0.3, $fecha->copy()->addDay());
                        }
                    }
                    if ($terminado) {
                        $this->documentoFinal($proyecto, $coordinacion, $inicio->copy()->addDays($actual ? 36 : 91));
                    }
                }
            }
        });
        $this->command?->line("Ciclo $clave disponible.");
    }

    private function entrega(Proyecto $proyecto, ApartadoGuia $apartado, Equipo $equipo, int $version, Carbon $fecha): Entrega
    {
        return Carbon::withTestNow($fecha, function () use ($proyecto, $apartado, $equipo, $version, $fecha): Entrega {
            $entrega = Entrega::query()->create([
                'proyecto_id' => $proyecto->id, 'apartado_guia_id' => $apartado->id, 'equipo_id' => $equipo->id,
                'entregado_por_id' => $equipo->lider_id, 'version' => $version, 'estado' => 'enviada', 'entregado_en' => $fecha,
            ]);
            $contenido = "EVIDENCIA FICTICIA / DEMO\n{$proyecto->titulo}\n{$apartado->titulo}\nVersión $version\n".
                ($version === 2 ? 'Se atendieron las observaciones de la primera revisión.' : 'Contenido de muestra para explorar el sistema.')."\nNo constituye una entrega académica real.\n";
            $ruta = "demo-historial/proyecto-{$proyecto->id}/apartado-{$apartado->id}-v$version.txt";
            Storage::disk('local')->put($ruta, $contenido);
            ArchivoEntrega::query()->create([
                'entrega_id' => $entrega->id, 'nombre_original' => "evidencia-demo-v$version.txt", 'ruta' => $ruta,
                'tipo_archivo' => 'text/plain', 'tamano' => strlen($contenido), 'es_aplicacion' => false,
            ]);
            if ($apartado->requiere_codigo) {
                ProductoCodigo::query()->create([
                    'proyecto_id' => $proyecto->id, 'entrega_id' => $entrega->id, 'version' => $version,
                    'repositorio_url' => null, 'demostracion_url' => null,
                ]);
            }

            return $entrega;
        });
    }

    private function revisar(Entrega $entrega, User $docente, string $resultado, float $nota, Carbon $fecha): void
    {
        Carbon::withTestNow($fecha, function () use ($entrega, $docente, $resultado, $nota): void {
            $observaciones = $resultado === 'aprobada'
                ? 'DEMO: evidencia revisada y criterios satisfechos. Aprobación con firma ficticia.'
                : 'DEMO: completar los casos de prueba y justificar las decisiones de diseño antes de reenviar.';
            app(FirmasDocentes::class)->guardarRevision($docente, $entrega, [
                'resultado' => $resultado, 'calificacion' => $nota, 'observaciones' => $observaciones,
            ], $resultado === 'aprobada');
            $revision = Revision::query()->where('entrega_id', $entrega->id)->where('revisor_id', $docente->id)->firstOrFail();
            ComentarioRevision::query()->create([
                'revision_id' => $revision->id, 'autor_id' => $docente->id, 'comentario' => $observaciones, 'visible_estudiante' => true,
            ]);
        });
    }

    private function documentoFinal(Proyecto $proyecto, User $coordinacion, Carbon $fecha): void
    {
        Carbon::withTestNow($fecha, function () use ($proyecto, $coordinacion): void {
            $documentos = app(DocumentosGuias::class);
            $datos = $documentos->datos($proyecto);
            if ($datos['pendientes']) {
                throw new RuntimeException('El proyecto DEMO no reúne las aprobaciones necesarias: '.implode(' ', $datos['pendientes']));
            }
            $pdf = $documentos->pdf([...$datos, 'emitido_por' => $coordinacion->nombre, 'emitido_en' => now()], 'FINAL');
            DocumentoFinal::query()->create([
                'proyecto_id' => $proyecto->id, 'generado_por' => $coordinacion->id, 'huella' => $documentos->huella($datos),
                'sha256' => hash('sha256', $pdf), 'pdf' => base64_encode($pdf),
            ]);
        });
    }

    private function firmaFicticia(int $indice): string
    {
        $imagen = imagecreatetruecolor(640, 160);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 255, 255, 255));
        $tinta = imagecolorallocate($imagen, 25, 45, 90);
        imagesetthickness($imagen, 3);
        for ($x = 35; $x < 540; $x++) {
            $y = (int) (75 + 23 * sin(($x + $indice * 21) / (13 + $indice * 3)) + 12 * sin($x / 31));
            $siguiente = (int) (75 + 23 * sin(($x + 1 + $indice * 21) / (13 + $indice * 3)) + 12 * sin(($x + 1) / 31));
            imageline($imagen, $x, $y, $x + 1, $siguiente, $tinta);
        }
        imageline($imagen, 45, 108, 580 - $indice * 15, 90, $tinta);
        imagestring($imagen, 5, 110, 130, 'FIRMA FICTICIA / DEMO '.($indice + 1), $tinta);
        ob_start();
        imagepng($imagen);
        $bytes = ob_get_clean();
        imagedestroy($imagen);

        return app(FirmasDocentes::class)->normalizar($bytes);
    }

    private function apartados(): array
    {
        return [
            ['Introducción', "Portada\nÍndice\nResumen\nAbstract", 10],
            ['Capítulo I - Protocolo del proyecto', "1.1 Antecedentes\n1.2 Planteamiento del problema\n1.3 Propuesta de solución\n1.4 Objetivos\n1.5 Justificación\n1.6 Alcances y limitaciones", 20],
            ['Capítulo II - Análisis y diseño', "2.1 Contexto institucional\n2.2 Requisitos funcionales\n2.3 Casos de uso\n2.4 Modelo de datos\n2.5 Diseño de interfaces", 20],
            ['Capítulo III - Desarrollo y validación', "3.1 Arquitectura\n3.2 Desarrollo de componentes\n3.3 Pruebas y resultados\n3.4 Evidencias de validación", 20],
            ['Producto de software y cierre', "Manual de usuario\nManual técnico\nCódigo fuente (caso ficticio)\nInforme de cierre\nCarta de liberación", 30],
        ];
    }
}
