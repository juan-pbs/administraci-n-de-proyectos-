<?php

namespace Database\Seeders;

use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Equipo;
use App\Models\FirmaApartadoGuia;
use App\Models\GuiaIntegradora;
use App\Models\GrupoAcademico;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = $this->crearRoles();
        $periodos = $this->crearPeriodos();
        $carreras = $this->crearCarreras();
        $grupos = $this->crearGruposUniversidad($periodos['actual'], $carreras);
        $usuarios = $this->crearUsuariosUniversidad($roles, $carreras, $grupos);
        $this->asignarDocentesACarreras($carreras, $usuarios['docentesPorCarrera']);

        $asignaturas = $this->crearAsignaturas($carreras);
        $this->asignarDocentesAAsignaturas($asignaturas, $usuarios['docentesPorCarrera']);
        $guias = $this->crearGuias($periodos, $asignaturas, $usuarios['direccion']);
        $equipos = $this->crearEquiposUniversidad($grupos, $usuarios);
        $this->crearProyectosUniversidad($guias, $equipos, $asignaturas, $usuarios['docentesPorCarrera']);
    }

    /**
     * @return array<string, Role>
     */
    private function crearRoles(): array
    {
        $roles = [
            'direccion_coordinacion' => ['Dirección / Coordinación', 'Administra usuarios, periodos, grupos, carga de alumnos, guías integradoras y seguimiento general.'],
            'encargado_proyectos' => ['Encargado de proyectos', 'Administra una carrera por periodo y cuatrimestre; asigna líderes, grupos, docentes y materias evaluadoras.'],
            'lider_proyecto' => ['Líder de proyecto', 'Docente de la materia líder; carga alumnos, organiza equipos y define el contexto del proyecto.'],
            'docente_materia' => ['Docente de materia', 'Revisa y califica la parte del proyecto asignada a su materia.'],
            'docente_asesor' => ['Docente / Asesor (anterior)', 'Rol conservado para compatibilidad con datos anteriores.'],
            'estudiante' => ['Alumno', 'Trabaja con su equipo y sube entregas, evidencias y productos del proyecto.'],
        ];

        return collect($roles)
            ->mapWithKeys(fn (array $datos, string $nombre) => [
                $nombre => Role::query()->updateOrCreate(
                    ['nombre' => $nombre],
                    ['nombre_visible' => $datos[0], 'descripcion' => $datos[1]],
                ),
            ])
            ->all();
    }

    /**
     * @return array<string, Periodo>
     */
    private function crearPeriodos(): array
    {
        return [
            'actual' => Periodo::query()->updateOrCreate(
                ['nombre' => 'Mayo - Agosto 2026'],
                ['fecha_inicio' => '2026-06-24', 'fecha_fin' => '2026-08-01', 'estado' => 'activo'],
            ),
            'siguiente' => Periodo::query()->updateOrCreate(
                ['nombre' => 'Septiembre - Noviembre 2026'],
                ['fecha_inicio' => '2026-09-10', 'fecha_fin' => '2026-11-20', 'estado' => 'borrador'],
            ),
        ];
    }

    /**
     * @return array<string, Carrera>
     */
    private function crearCarreras(): array
    {
        $carreras = [
            'TI' => 'Tecnologías de la Información',
            'MECA' => 'Mecatrónica',
            'ADM' => 'Administración',
        ];

        return collect($carreras)
            ->mapWithKeys(fn (string $nombre, string $clave) => [
                $clave => Carrera::query()->updateOrCreate(
                    ['clave' => $clave],
                    ['nombre' => $nombre, 'estado' => 'activa'],
                ),
            ])
            ->all();
    }

    /**
     * @param array<string, Carrera> $carreras
     * @return array<string, GrupoAcademico>
     */
    private function crearGruposUniversidad(Periodo $periodo, array $carreras): array
    {
        $grupos = [];

        foreach (array_keys($carreras) as $claveCarrera) {
            foreach ([5, 6, 7, 8, 9] as $grado) {
                $letras = in_array($grado, [8, 9], true) ? ['A', 'B', 'C', 'D'] : ['A', 'B', 'C'];

                foreach ($letras as $letra) {
                    $claveGrupo = "{$claveCarrera}-{$grado}{$letra}";

                    $grupos[$claveGrupo] = GrupoAcademico::query()->updateOrCreate(
                        [
                            'periodo_id' => $periodo->id,
                            'carrera_id' => $carreras[$claveCarrera]->id,
                            'grado' => $grado,
                            'grupo' => $letra,
                        ],
                        ['nombre' => "{$grado}{$letra}"],
                    );
                }
            }
        }

        return $grupos;
    }

    /**
     * @param array<string, Role> $roles
     * @param array<string, Carrera> $carreras
     * @param array<string, GrupoAcademico> $grupos
     * @return array<string, mixed>
     */
    private function crearUsuariosUniversidad(array $roles, array $carreras, array $grupos): array
    {
        $direccion = User::query()->updateOrCreate(
            ['matricula' => '20260001'],
            [
                'nombre' => 'Dirección Coordinación UTVM',
                'correo' => 'coordinacion@utvm.edu.mx',
                'rol_id' => $roles['direccion_coordinacion']->id,
                'carrera_id' => null,
                'grupo_academico_id' => null,
                'estado' => 'activo',
                'contrasena' => 'password',
                'debe_cambiar_contrasena' => false,
                'contrasena_actualizada_en' => now(),
            ],
        );

        $docentesPorCarrera = $this->crearDocentes($roles['docente_asesor'], $carreras);
        $alumnos = $this->crearAlumnos($roles['estudiante'], $carreras, $grupos);

        return [
            'direccion' => $direccion,
            'docentes' => $docentesPorCarrera->flatten(1)->keyBy('matricula'),
            'docentesPorCarrera' => $docentesPorCarrera,
            'alumnos' => $alumnos,
        ];
    }

    /**
     * @param array<string, Carrera> $carreras
     * @return Collection<string, Collection<int, User>>
     */
    private function crearDocentes(Role $rolDocente, array $carreras): Collection
    {
        $docentesBase = [
            'TI' => ['Miguel Espinal Botho', 'Laura Hernández Pérez', 'Fernanda Torres Aguilar', 'Roberto Nava Salinas', 'Daniela Cruz Morales', 'Arturo Ramírez León', 'Docente Asesor UTVM'],
            'MECA' => ['Carlos Méndez Ruiz', 'Mariana Castillo Ríos', 'Jorge Alberto Sánchez Luna', 'Patricia Vargas León', 'Ernesto Salinas Torres', 'Gabriela Moreno Campos'],
            'ADM' => ['Sandra López García', 'Hugo Molina Pacheco', 'Elena Jiménez Arce', 'Mario Reyes Ortega', 'Claudia Gómez Luna', 'Rafael Pérez Díaz'],
        ];

        return collect($docentesBase)->mapWithKeys(function (array $nombres, string $claveCarrera) use ($rolDocente, $carreras) {
            $docentes = collect($nombres)->map(function (string $nombre, int $indice) use ($rolDocente, $carreras, $claveCarrera) {
                $numero = $indice + 1;
                $matricula = $claveCarrera === 'TI' && $numero === 7
                    ? '20260002'
                    : 'DOC-'.$claveCarrera.'-'.str_pad((string) $numero, 2, '0', STR_PAD_LEFT);

                return User::query()->updateOrCreate(
                    ['matricula' => $matricula],
                    [
                        'nombre' => $nombre,
                        'correo' => $this->correoDesdeNombre($nombre, 'utvm.edu.mx', 'docente'.$numero.'.'.$claveCarrera),
                        'rol_id' => $rolDocente->id,
                        'carrera_id' => $carreras[$claveCarrera]->id,
                        'grupo_academico_id' => null,
                        'estado' => 'activo',
                        'contrasena' => 'password',
                        'debe_cambiar_contrasena' => false,
                        'contrasena_actualizada_en' => now(),
                    ],
                );
            });

            return [$claveCarrera => $docentes];
        });
    }

    /**
     * @param array<string, Carrera> $carreras
     * @param array<string, GrupoAcademico> $grupos
     * @return Collection<string, User>
     */
    private function crearAlumnos(Role $rolEstudiante, array $carreras, array $grupos): Collection
    {
        $nombres = ['Ana Sofía', 'Luis Fernando', 'María Fernanda', 'José Antonio', 'Diana Paola', 'Ricardo', 'Valeria', 'Héctor Iván', 'Camila', 'Emiliano', 'Paola', 'Andrés', 'Ximena', 'Diego', 'Regina', 'Santiago', 'Montserrat', 'Leonardo', 'Renata', 'Mateo', 'Alejandra', 'Sebastián', 'Natalia', 'Ángel', 'Daniela', 'Javier', 'Andrea', 'Rodrigo', 'Fernanda', 'Mauricio'];
        $apellidos = ['Martínez', 'Pérez', 'Gómez', 'Ramírez', 'Vargas', 'Salinas', 'Moreno', 'Flores', 'Reyes', 'Castillo', 'Jiménez', 'Molina', 'Cruz', 'Hernández', 'Soto', 'Nava', 'Ortega', 'Campos', 'Luna', 'Díaz'];
        $segundosApellidos = ['Cruz', 'Díaz', 'Luna', 'Soto', 'León', 'Torres', 'Campos', 'Nava', 'Ortega', 'Ríos', 'Arce', 'Pacheco', 'Aguilar', 'Morales', 'Salinas', 'Pérez', 'García', 'Ruiz', 'Méndez', 'Vega'];

        $contador = 1;
        $alumnos = collect();

        foreach ($grupos as $claveGrupo => $grupo) {
            $claveCarrera = explode('-', $claveGrupo)[0];

            for ($indice = 1; $indice <= 30; $indice++) {
                $nombre = $nombres[($contador - 1) % count($nombres)];
                $apellido = $apellidos[($contador + $indice) % count($apellidos)];
                $segundoApellido = $segundosApellidos[($contador + $grupo->grado + $indice) % count($segundosApellidos)];
                $nombreCompleto = "{$nombre} {$apellido} {$segundoApellido}";
                $matricula = '2026'.str_pad((string) $contador, 5, '0', STR_PAD_LEFT);

                $alumnos[$matricula] = User::query()->updateOrCreate(
                    ['matricula' => $matricula],
                    [
                        'nombre' => $nombreCompleto,
                        'correo' => 'alumno'.$matricula.'@utvm.edu.mx',
                        'rol_id' => $rolEstudiante->id,
                        'carrera_id' => $carreras[$claveCarrera]->id,
                        'grupo_academico_id' => $grupo->id,
                        'estado' => 'activo',
                        'contrasena' => 'password',
                        'debe_cambiar_contrasena' => false,
                        'contrasena_actualizada_en' => now(),
                    ],
                );

                $contador++;
            }
        }

        return $alumnos;
    }

    /**
     * @param array<string, Carrera> $carreras
     * @param Collection<string, Collection<int, User>> $docentesPorCarrera
     */
    private function asignarDocentesACarreras(array $carreras, Collection $docentesPorCarrera): void
    {
        foreach ($docentesPorCarrera as $claveCarrera => $docentes) {
            $carreras[$claveCarrera]->docentes()->syncWithoutDetaching(
                $docentes->mapWithKeys(fn (User $docente) => [
                    $docente->id => ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
                ])->all(),
            );
        }
    }

    /**
     * @param array<string, Carrera> $carreras
     * @return array<string, Asignatura>
     */
    private function crearAsignaturas(array $carreras): array
    {
        $nombresPorCarrera = [
            'TI' => ['Integradora', 'Desarrollo de aplicaciones web', 'Base de datos para aplicaciones'],
            'MECA' => ['Integradora', 'Automatización industrial', 'Diseño mecánico asistido'],
            'ADM' => ['Integradora', 'Gestión de proyectos', 'Administración financiera'],
        ];

        $asignaturas = [];

        foreach ($nombresPorCarrera as $claveCarrera => $nombres) {
            foreach ([5, 6, 7, 8, 9] as $grado) {
                foreach ($nombres as $indice => $nombre) {
                    $clave = $claveCarrera.'-'.strtoupper(substr($this->sinAcentos($nombre), 0, 3)).'-'.str_pad((string) $grado, 2, '0', STR_PAD_LEFT).'-'.($indice + 1);

                    $asignaturas[$clave] = Asignatura::query()->updateOrCreate(
                        ['clave' => $clave],
                        [
                            'carrera_id' => $carreras[$claveCarrera]->id,
                            'nombre' => $nombre,
                            'grado' => $grado,
                            'estado' => 'activo',
                        ],
                    );
                }
            }
        }

        return $asignaturas;
    }

    /**
     * @param array<string, Asignatura> $asignaturas
     * @param Collection<string, Collection<int, User>> $docentesPorCarrera
     */
    private function asignarDocentesAAsignaturas(array $asignaturas, Collection $docentesPorCarrera): void
    {
        foreach ($asignaturas as $asignatura) {
            $claveCarrera = explode('-', (string) $asignatura->clave)[0] ?? null;
            $docentes = $docentesPorCarrera[$claveCarrera] ?? collect();

            if ($docentes->isEmpty()) {
                continue;
            }

            $docente = $docentes->values()[(int) $asignatura->grado % $docentes->count()];
            $asignatura->docentes()->syncWithoutDetaching([
                $docente->id => ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
            ]);
        }
    }

    /**
     * @param array<string, Periodo> $periodos
     * @param array<string, Asignatura> $asignaturas
     * @return array<string, GuiaIntegradora>
     */
    private function crearGuias(array $periodos, array $asignaturas, User $direccion): array
    {
        $guias = [];

        foreach (['TI', 'MECA', 'ADM'] as $claveCarrera) {
            foreach ([5, 6, 7, 8, 9] as $grado) {
                $asignaturasGrado = collect($asignaturas)
                    ->filter(fn (Asignatura $asignatura) => str_starts_with($asignatura->clave, $claveCarrera.'-') && (int) $asignatura->grado === $grado)
                    ->values();

                $asignaturaPrincipal = $asignaturasGrado->first();
                $claveGuia = "{$claveCarrera}-{$grado}";

                $guia = GuiaIntegradora::query()->updateOrCreate(
                    [
                        'periodo_id' => $periodos['actual']->id,
                        'asignatura_id' => $asignaturaPrincipal?->id,
                        'nombre' => "Guía de proyecto integrador {$claveCarrera} {$grado}",
                    ],
                    [
                        'periodo_fin_id' => $periodos['siguiente']->id,
                        'creado_por' => $direccion->id,
                        'cuatrimestre' => $grado.'° cuatrimestre',
                        'competencias_evaluar' => 'Construir soluciones académicas mediante metodología de proyectos, documentación técnica, evaluación multidisciplinaria y entrega de evidencias.',
                        'objetivo_aprendizaje' => 'Desarrollar un proyecto integrador con seguimiento por apartados, revisión docente y generación de entregables institucionales.',
                        'version' => '1.0',
                        'estado' => 'publicada',
                    ],
                );

                foreach ($this->apartadosBase() as $datosApartado) {
                    $apartado = ApartadoGuia::query()->updateOrCreate(
                        ['guia_integradora_id' => $guia->id, 'orden' => $datosApartado['orden']],
                        [
                            'titulo' => $datosApartado['titulo'],
                            'descripcion' => $datosApartado['descripcion'],
                            'fecha_limite' => Carbon::parse($periodos['actual']->fecha_inicio)->addDays($datosApartado['dias']),
                            'ponderacion' => $datosApartado['ponderacion'],
                            'requiere_documento' => true,
                            'requiere_codigo' => $datosApartado['requiere_codigo'],
                        ],
                    );

                    $apartado->asignaturasContribuyentes()->syncWithoutDetaching(
                        $asignaturasGrado->mapWithKeys(fn (Asignatura $asignatura) => [
                            $asignatura->id => [
                                'rol_contribucion' => $asignatura->nombre,
                                'requiere_firma' => true,
                                'creado_en' => now(),
                                'actualizado_en' => now(),
                            ],
                        ])->all(),
                    );

                    FirmaApartadoGuia::query()->updateOrCreate(
                        ['apartado_guia_id' => $apartado->id, 'orden' => 1, 'etiqueta' => 'Primer asesor'],
                        ['asignatura_id' => null, 'docente_id' => null, 'requerida' => true],
                    );

                    FirmaApartadoGuia::query()->updateOrCreate(
                        ['apartado_guia_id' => $apartado->id, 'orden' => 2, 'etiqueta' => 'Docente integrador'],
                        ['asignatura_id' => $asignaturaPrincipal?->id, 'docente_id' => null, 'requerida' => true],
                    );
                }

                $guias[$claveGuia] = $guia;
            }
        }

        return $guias;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function apartadosBase(): array
    {
        return [
            ['orden' => 1, 'titulo' => 'Introducción', 'descripcion' => "Portada\nÍndice\nResumen\nAbstract", 'dias' => 7, 'ponderacion' => 10, 'requiere_codigo' => false],
            ['orden' => 2, 'titulo' => 'Capítulo I - Protocolo del proyecto', 'descripcion' => "1.1 Antecedentes\n1.2 Planteamiento del problema\n1.3 Propuesta de solución\n1.4 Objetivo del proyecto\n1.5 Justificación\n1.6 Alcances\n1.7 Limitaciones", 'dias' => 14, 'ponderacion' => 20, 'requiere_codigo' => false],
            ['orden' => 3, 'titulo' => 'Capítulo II - Generalidades de la institución o empresa', 'descripcion' => "2.1 Antecedentes\n2.2 Nombre de la empresa\n2.3 Misión\n2.4 Visión\n2.5 Políticas\n2.6 Objetivos de calidad\n2.7 Valores\n2.8 Localización geográfica\n2.9 Organigrama", 'dias' => 21, 'ponderacion' => 15, 'requiere_codigo' => false],
            ['orden' => 4, 'titulo' => 'Capítulo III - Viabilidad del proyecto y análisis preliminar', 'descripcion' => "3.1 Estudio de factibilidad técnica\n3.2 Herramientas disponibles\n3.3 Recursos requeridos\n3.4 Análisis de riesgos", 'dias' => 28, 'ponderacion' => 20, 'requiere_codigo' => false],
            ['orden' => 5, 'titulo' => 'Producto de software y documentación final', 'descripcion' => "Ejecución de pruebas\nLecciones aprendidas\nInforme de cierre\nCarta de liberación\nRepositorio y manuales", 'dias' => 35, 'ponderacion' => 35, 'requiere_codigo' => true],
        ];
    }

    /**
     * @param array<string, GrupoAcademico> $grupos
     * @param array<string, mixed> $usuarios
     * @return array<string, Equipo>
     */
    private function crearEquiposUniversidad(array $grupos, array $usuarios): array
    {
        $equipos = [];

        foreach ($grupos as $claveGrupo => $grupo) {
            $claveCarrera = explode('-', $claveGrupo)[0];
            $alumnosGrupo = $usuarios['alumnos']
                ->filter(fn (User $alumno) => (int) $alumno->grupo_academico_id === (int) $grupo->id)
                ->values();
            $docentesCarrera = $usuarios['docentesPorCarrera'][$claveCarrera]->values();

            foreach ($alumnosGrupo->chunk(6)->values() as $indiceEquipo => $integrantes) {
                $numeroEquipo = $indiceEquipo + 1;
                $claveEquipo = "{$claveGrupo}-E{$numeroEquipo}";
                $lider = $integrantes->first();

                $equipo = Equipo::query()->updateOrCreate(
                    ['grupo_academico_id' => $grupo->id, 'nombre' => 'Equipo '.$numeroEquipo],
                    ['lider_id' => $lider?->id, 'estado' => 'activo'],
                );

                $equipo->integrantes()->syncWithoutDetaching(
                    $integrantes->mapWithKeys(fn (User $alumno) => [
                        $alumno->id => ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
                    ])->all(),
                );

                $asesorPrincipal = $docentesCarrera[$indiceEquipo % $docentesCarrera->count()];
                $asesorSecundario = $docentesCarrera[($indiceEquipo + 1) % $docentesCarrera->count()];

                $equipo->asesores()->syncWithoutDetaching([
                    $asesorPrincipal->id => ['principal' => true, 'activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
                    $asesorSecundario->id => ['principal' => false, 'activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
                ]);

                $equipos[$claveEquipo] = $equipo;
            }
        }

        return $equipos;
    }

    /**
     * @param array<string, GuiaIntegradora> $guias
     * @param array<string, Equipo> $equipos
     * @param array<string, Asignatura> $asignaturas
     * @param Collection<string, Collection<int, User>> $docentesPorCarrera
     */
    private function crearProyectosUniversidad(array $guias, array $equipos, array $asignaturas, Collection $docentesPorCarrera): void
    {
        foreach ($equipos as $claveEquipo => $equipo) {
            [$claveCarrera, $grupo] = explode('-', $claveEquipo);
            $grado = (int) substr($grupo, 0, -1);
            $claveGuia = "{$claveCarrera}-{$grado}";

            if (! isset($guias[$claveGuia])) {
                continue;
            }

            $proyecto = Proyecto::query()->updateOrCreate(
                ['guia_integradora_id' => $guias[$claveGuia]->id, 'equipo_id' => $equipo->id],
                [
                    'titulo' => 'Proyecto integrador '.$claveCarrera.' '.$grupo.' '.$equipo->nombre,
                    'descripcion' => 'Proyecto integrador de muestra para visualizar el flujo académico con equipos universitarios.',
                    'estado' => 'en_proceso',
                ],
            );

            $docentes = $docentesPorCarrera[$claveCarrera]->values();
            $docenteAsesor = $docentes[0];
            $docenteEvaluador = $docentes[1] ?? $docenteAsesor;

            $proyecto->docentes()->syncWithoutDetaching([
                $docenteAsesor->id => ['tipo_participacion' => 'asesor_evaluador', 'activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
                $docenteEvaluador->id => ['tipo_participacion' => 'evaluador', 'activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
            ]);

            $asignaturasProyecto = collect($asignaturas)
                ->filter(fn (Asignatura $asignatura) => str_starts_with($asignatura->clave, $claveCarrera.'-') && (int) $asignatura->grado === $grado)
                ->values();

            $proyecto->asignaturas()->syncWithoutDetaching(
                $asignaturasProyecto->mapWithKeys(fn (Asignatura $asignatura, int $indice) => [
                    $asignatura->id => [
                        'docente_id' => $docentes[$indice % $docentes->count()]->id,
                        'participa_evaluacion' => true,
                        'creado_en' => now(),
                        'actualizado_en' => now(),
                    ],
                ])->all(),
            );
        }
    }

    private function correoDesdeNombre(string $nombre, string $dominio, string $respaldo): string
    {
        $normalizado = strtolower($this->sinAcentos($nombre));
        $normalizado = preg_replace('/[^a-z0-9]+/', '.', $normalizado) ?: $respaldo;
        $normalizado = trim($normalizado, '.');

        return $normalizado.'@'.$dominio;
    }

    private function sinAcentos(string $texto): string
    {
        return strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ]);
    }
}
