<?php

namespace App\Soporte;

use App\Models\GrupoAcademico;

class SistemaInterfaz
{
    /**
     * @return array<int, array<string, string>>
     */
    public static function navegacionPara(string $rol): array
    {
        $base = [
            ['clave' => 'dashboard', 'titulo' => 'Dashboard', 'ruta' => route('dashboard'), 'icono' => '⌂'],
        ];

        $porRol = match ($rol) {
            'coordinacion' => [
                ['clave' => 'jerarquia-proyectos', 'titulo' => 'Jerarquía de proyectos', 'ruta' => route('modulos.jerarquia'), 'icono' => '◆'],
                ['clave' => 'periodos', 'titulo' => 'Periodos', 'ruta' => route('modulos.show', 'periodos'), 'icono' => '▦'],
                ['clave' => 'carreras-grupos', 'titulo' => 'Carreras y grupos', 'ruta' => route('modulos.show', 'carreras-grupos'), 'icono' => '▤'],
                ['clave' => 'usuarios', 'titulo' => 'Usuarios', 'ruta' => route('modulos.show', 'usuarios'), 'icono' => '◎'],
                ['clave' => 'asignaturas', 'titulo' => 'Asignaturas', 'ruta' => route('modulos.show', 'asignaturas'), 'icono' => '◇'],
                ['clave' => 'guias', 'titulo' => 'Guías', 'ruta' => route('modulos.show', 'guias'), 'icono' => '▣'],
            ],
            'docente_lider' => auth()->check() && GrupoAcademico::query()->conMateriaLiderDelDocente((int) auth()->id())->exists() ? [
                ['clave' => 'usuarios', 'titulo' => 'Lista de alumnos', 'ruta' => route('modulos.show', 'usuarios'), 'icono' => '◉'],
                ['clave' => 'equipos', 'titulo' => 'Equipos', 'ruta' => route('modulos.show', 'equipos'), 'icono' => '◫'],
                ['clave' => 'proyectos', 'titulo' => 'Proyectos', 'ruta' => route('modulos.show', 'proyectos'), 'icono' => '□'],
                ['clave' => 'estado-guias', 'titulo' => 'Estado de las guías', 'ruta' => route('docente-lider.estado-guias'), 'icono' => '▣'],
                ['clave' => 'cierres', 'titulo' => 'Cierres y prórrogas', 'ruta' => route('docente-lider.cierres'), 'icono' => '◷'],
                ['clave' => 'revision-codigo', 'titulo' => 'Revisión de código', 'ruta' => route('docente-lider.codigo'), 'icono' => '</>'],
            ] : [],
            'docente_materia' => [
                ['clave' => 'asignaciones-docente', 'titulo' => 'Mis asignaciones', 'ruta' => route('docente-materia.asignaciones'), 'icono' => '◇'],
                ['clave' => 'revisiones-docente', 'titulo' => 'Revisiones', 'ruta' => route('docente-materia.revisiones'), 'icono' => '✓'],
            ],
            default => [
                ['clave' => 'mi-proyecto', 'titulo' => 'Mi proyecto', 'ruta' => route('estudiante.proyecto'), 'icono' => '◇'],
                ['clave' => 'mis-entregas', 'titulo' => 'Entregas', 'ruta' => route('estudiante.entregas'), 'icono' => '✓'],
                ['clave' => 'codigo-estudiante', 'titulo' => 'Código y repositorio', 'ruta' => route('estudiante.codigo'), 'icono' => '</>'],
            ],
        };

        $navegacion = [
            ...$base,
            ...$porRol,
            ...($rol === 'docente_lider' ? [
                ['clave' => 'asignaciones-docente', 'titulo' => 'Mis asignaciones', 'ruta' => route('docente-materia.asignaciones'), 'icono' => '◇'],
                ['clave' => 'revisiones-docente', 'titulo' => 'Revisiones de apartados', 'ruta' => route('docente-materia.revisiones'), 'icono' => '✓'],
            ] : []),
            ...(in_array($rol, ['docente_lider', 'docente_materia'], true) ? [
                ['clave' => 'mi-firma', 'titulo' => 'Mi firma', 'ruta' => route('docente.firma'), 'icono' => '✎'],
            ] : []),
        ];
        $contexto = request()->input('proyecto_contexto');
        if ($rol === 'estudiante' && is_scalar($contexto) && filter_var($contexto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            foreach ($navegacion as &$item) {
                if (in_array($item['clave'], ['mi-proyecto', 'mis-entregas', 'codigo-estudiante'], true)) {
                    $item['ruta'] .= '?proyecto_contexto='.(int) $contexto;
                }
            }
            unset($item);
        }

        return $navegacion;
    }

    /**
     * @return array<int, array{titulo: string, ruta: string}>
     */
    public static function opcionesAyudaPara(string $rol): array
    {
        $inicio = [
            ['titulo' => 'Iniciar Sesion', 'seccion' => 'iniciar-sesion'],
        ];

        $porRol = match ($rol) {
            'coordinacion' => [
                ['titulo' => 'Panel principal', 'seccion' => 'panel-principal'],
                ['titulo' => 'Jerarquia de proyectos', 'seccion' => 'jerarquia-proyectos'],
                ['titulo' => 'Periodos', 'seccion' => 'periodos'],
                ['titulo' => 'Carreras y grupos', 'seccion' => 'carreras-grupos'],
                ['titulo' => 'Usuarios', 'seccion' => 'usuarios'],
                ['titulo' => 'Asignaturas', 'seccion' => 'asignaturas'],
                ['titulo' => 'Guias', 'seccion' => 'guias'],
            ],
            'docente_lider' => [
                ['titulo' => 'Panel principal', 'seccion' => 'panel-principal'],
                ['titulo' => 'Lista de alumnos', 'seccion' => 'lista-alumnos'],
                ['titulo' => 'Equipos', 'seccion' => 'equipos'],
                ['titulo' => 'Proyectos', 'seccion' => 'proyectos'],
                ['titulo' => 'Revision de codigo', 'seccion' => 'revision-codigo'],
            ],
            'docente_materia' => [
                ['titulo' => 'Panel principal', 'seccion' => 'panel-principal'],
                ['titulo' => 'Mis asignaciones', 'seccion' => 'mis-asignaciones'],
                ['titulo' => 'Revisiones', 'seccion' => 'revisiones'],
            ],
            default => [
                ['titulo' => 'Panel principal', 'seccion' => 'panel-principal'],
                ['titulo' => 'Mi proyecto', 'seccion' => 'mi-proyecto'],
                ['titulo' => 'Entregas', 'seccion' => 'entregas'],
                ['titulo' => 'Codigo y repositorio', 'seccion' => 'codigo-repositorio'],
            ],
        };

        $fin = [
            ['titulo' => 'Cerrar Sesion', 'seccion' => 'cerrar-sesion'],
            ['titulo' => 'Contactanos', 'seccion' => 'contactanos'],
        ];

        return collect([...$inicio, ...$porRol, ...$fin])
            ->map(fn (array $opcion): array => [
                'titulo' => $opcion['titulo'],
                'ruta' => route('ayuda', ['seccion' => $opcion['seccion']]),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function dashboardPara(string $rol): array
    {
        return match ($rol) {
            'coordinacion' => [
                'eyebrow' => 'Dirección y coordinación académica',
                'title' => 'Panel de dirección / coordinación',
                'description' => 'Control académico de periodos, carreras, grupos, usuarios, asignaturas y guías.',
                'badge' => 'Dirección / coordinación',
                'stats' => [
                    ['label' => 'Periodos activos', 'value' => '1'],
                    ['label' => 'Equipos registrados', 'value' => '18'],
                    ['label' => 'Entregas pendientes', 'value' => '42'],
                    ['label' => 'Avance general', 'value' => '68%'],
                ],
                'modules' => [
                    ['title' => 'Carga académica', 'description' => 'Periodos, grupos, alumnos, docentes y asignaturas.', 'status' => 'Configurable', 'route' => 'periodos'],
                    ['title' => 'Guías integradoras', 'description' => 'Apartados, fechas, ponderación y evidencias requeridas.', 'status' => 'Editable', 'route' => 'guias'],
                ],
            ],
            'docente_lider' => [
                'eyebrow' => 'Materia líder y organización de equipos',
                'title' => 'Panel del docente líder',
                'description' => 'Consulta sus grupos, organiza alumnos y equipos, y asigna los proyectos integradores.',
                'badge' => 'Docente líder',
                'stats' => [['label' => 'Grupos', 'value' => 'Asignados'], ['label' => 'Alumnos', 'value' => 'Por cargar'], ['label' => 'Equipos', 'value' => 'Por organizar'], ['label' => 'Proyectos', 'value' => 'En seguimiento']],
                'modules' => [['title' => 'Lista de alumnos', 'description' => 'Carga y consulta por grupo.', 'status' => 'Editable', 'route' => 'usuarios'], ['title' => 'Equipos', 'description' => 'Distribución de integrantes.', 'status' => 'Editable', 'route' => 'equipos'], ['title' => 'Proyectos', 'description' => 'Asignación y participación docente.', 'status' => 'Editable', 'route' => 'proyectos']],
            ],
            'docente_materia' => [
                'eyebrow' => 'Evaluación académica',
                'title' => 'Panel del docente de materia',
                'description' => 'Consulta los apartados asignados por Coordinación y da seguimiento a las entregas de cada equipo.',
                'badge' => 'Docente de materia',
                'stats' => [],
                'modules' => [],
            ],
            default => [
                'eyebrow' => 'Trabajo del equipo',
                'title' => 'Panel del estudiante',
                'description' => 'Consulta tu proyecto, entrega avances de la guía y da seguimiento a calificaciones y correcciones.',
                'badge' => 'Estudiante',
                'stats' => [],
                'modules' => [],
            ],
        };
    }

    public static function puedeVer(string $rol, string $clave): bool
    {
        return collect(self::navegacionPara($rol))->contains('clave', $clave);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function pagina(string $clave): ?array
    {
        return self::paginas()[$clave] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function paginas(): array
    {
        return [
            'periodos' => [
                'titulo' => 'Gestión de periodos académicos',
                'subtitulo' => 'Calendario operativo para guías, entregas y evaluaciones.',
                'acciones' => ['Nuevo periodo', 'Cerrar periodo', 'Exportar calendario'],
                'metricas' => [
                    ['label' => 'Activo', 'value' => 'Mayo - Agosto 2026'],
                    ['label' => 'Guías publicadas', 'value' => '4'],
                    ['label' => 'Cierre', 'value' => '01/08/2026'],
                ],
                'formulario' => [
                    'titulo' => 'Periodo académico',
                    'campos' => [
                        ['label' => 'Nombre del periodo', 'tipo' => 'text', 'valor' => 'Septiembre - Diciembre 2026'],
                        ['label' => 'Fecha de inicio', 'tipo' => 'date', 'valor' => '2026-09-10'],
                        ['label' => 'Fecha de cierre', 'tipo' => 'date', 'valor' => '2026-11-20'],
                        ['label' => 'Estado', 'tipo' => 'select', 'opciones' => ['Borrador', 'Activo', 'Cerrado']],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Periodos registrados',
                    'columnas' => ['Periodo', 'Inicio', 'Cierre', 'Estado', 'Avance'],
                    'filas' => [
                        ['Mayo - Agosto 2026', '24/06/2026', '01/08/2026', 'Activo', '70%'],
                        ['Septiembre - Noviembre 2026', '10/09/2026', '20/11/2026', 'Borrador', '0%'],
                    ],
                ],
            ],
            'carreras-grupos' => [
                'titulo' => 'Gestión de carreras y grupos',
                'subtitulo' => 'Estructura académica usada para organizar grupos, equipos y asignaturas.',
                'acciones' => ['Nueva carrera', 'Nuevo grupo', 'Importar grupos'],
                'metricas' => [
                    ['label' => 'Carreras', 'value' => '3'],
                    ['label' => 'Grupos', 'value' => '7'],
                    ['label' => 'Alumnos', 'value' => '212'],
                ],
                'formulario' => [
                    'titulo' => 'Grupo académico',
                    'campos' => [
                        ['label' => 'Carrera', 'tipo' => 'select', 'opciones' => ['TI', 'Mecatrónica', 'Administración']],
                        ['label' => 'Grupo', 'tipo' => 'text', 'valor' => '9B'],
                        ['label' => 'Cuatrimestre', 'tipo' => 'number', 'valor' => '9'],
                        ['label' => 'Periodo', 'tipo' => 'select', 'opciones' => ['Mayo - Agosto 2026', 'Septiembre - Noviembre 2026']],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Grupos por carrera',
                    'columnas' => ['Carrera', 'Grupo', 'Cuatrimestre', 'Alumnos', 'Equipos'],
                    'filas' => [
                        ['TI', '9B', '9', '31', '6'],
                        ['TI', '9A', '9', '28', '5'],
                        ['Administración', '8A', '8', '34', '7'],
                    ],
                ],
            ],
            'usuarios' => [
                'titulo' => 'Administración de usuarios',
                'subtitulo' => 'Estudiantes, docentes, asesores y coordinación con acceso por matrícula.',
                'acciones' => ['Nuevo alumno', 'Nuevo docente', 'Importar lista de alumnos'],
                'metricas' => [
                    ['label' => 'Estudiantes', 'value' => '212'],
                    ['label' => 'Docentes / asesores', 'value' => '18'],
                    ['label' => 'Inactivos', 'value' => '3'],
                ],
                'formulario' => [
                    'titulo' => 'Alta rápida',
                    'campos' => [
                        ['label' => 'Matrícula', 'tipo' => 'text', 'valor' => '20260000'],
                        ['label' => 'Nombre completo', 'tipo' => 'text', 'valor' => 'Nombre Apellido'],
                        ['label' => 'Correo de recuperación', 'tipo' => 'email', 'valor' => 'usuario@utvm.edu.mx'],
                        ['label' => 'Rol', 'tipo' => 'select', 'opciones' => ['Dirección / Coordinación', 'Docente / Asesor', 'Estudiante / Equipo']],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Usuarios recientes',
                    'columnas' => ['Matrícula', 'Nombre', 'Rol', 'Grupo', 'Estado'],
                    'filas' => [
                        ['20260001', 'Dirección Coordinación UTVM', 'Dirección / Coordinación', '-', 'Activo'],
                        ['20260002', 'Docente Asesor UTVM', 'Docente / Asesor', '-', 'Activo'],
                        ['20260003', 'Estudiante UTVM', 'Estudiante / Equipo', '9B', 'Activo'],
                    ],
                ],
            ],
            'asignaturas' => [
                'titulo' => 'Asignaturas participantes',
                'subtitulo' => 'Materias asociadas al proyecto integrador y a sus evaluaciones.',
                'acciones' => ['Nueva asignatura', 'Asignar docente', 'Sincronizar grupos'],
                'metricas' => [
                    ['label' => 'Asignaturas', 'value' => '6'],
                    ['label' => 'Docentes', 'value' => '9'],
                    ['label' => 'Proyectos vinculados', 'value' => '18'],
                ],
                'formulario' => [
                    'titulo' => 'Asignatura',
                    'campos' => [
                        ['label' => 'Nombre', 'tipo' => 'text', 'valor' => 'Desarrollo de aplicaciones web'],
                        ['label' => 'Clave', 'tipo' => 'text', 'valor' => 'TI-DAW-09'],
                        ['label' => 'Docente / asesor', 'tipo' => 'select', 'opciones' => ['Docente Asesor UTVM', 'Docente Evaluador 2']],
                        ['label' => 'Participa en evaluación', 'tipo' => 'checkbox', 'valor' => '1'],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Materias vinculadas',
                    'columnas' => ['Asignatura', 'Clave', 'Docente', 'Tipo', 'Estado'],
                    'filas' => [
                        ['Desarrollo web', 'TI-DAW-09', 'Docente Asesor UTVM', 'Evaluadora', 'Activa'],
                        ['Base de datos', 'TI-BD-09', 'Docente Evaluador 2', 'Participante', 'Activa'],
                        ['Integradora', 'TI-INT-09', 'Coordinación', 'Principal', 'Activa'],
                    ],
                ],
            ],
            'guias' => [
                'titulo' => 'Guías de proyectos integradores',
                'subtitulo' => 'Estructura dinámica de apartados, fechas, ponderaciones y evidencias.',
                'acciones' => ['Nueva guía', 'Agregar apartado', 'Publicar guía'],
                'metricas' => [
                    ['label' => 'Apartados', 'value' => '8'],
                    ['label' => 'Ponderación', 'value' => '100%'],
                    ['label' => 'Versión', 'value' => '1.2'],
                ],
                'formulario' => [
                    'titulo' => 'Apartado de guía',
                    'campos' => [
                        ['label' => 'Título', 'tipo' => 'text', 'valor' => 'Modelo entidad relación'],
                        ['label' => 'Fecha límite', 'tipo' => 'datetime-local', 'valor' => '2026-07-25T18:00'],
                        ['label' => 'Ponderación', 'tipo' => 'number', 'valor' => '15'],
                        ['label' => 'Tipo de evidencia', 'tipo' => 'select', 'opciones' => ['Captura en plataforma', 'Archivo', 'Archivo y repositorio']],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Apartados configurados',
                    'columnas' => ['Orden', 'Apartado', 'Fecha límite', 'Ponderación', 'Evidencia'],
                    'filas' => [
                        ['1', 'Project Charter', '28/06/2026', '10%', 'Documento'],
                        ['2', 'Modelo entidad relación', '12/07/2026', '15%', 'Archivo'],
                        ['3', 'Módulos funcionales', '01/08/2026', '20%', 'Captura y archivo'],
                    ],
                ],
            ],
            'equipos' => [
                'titulo' => 'Equipos de trabajo',
                'subtitulo' => 'Generación automática, asignación manual y selección de líder.',
                'acciones' => ['Generar equipos', 'Asignar manualmente', 'Recalcular'],
                'metricas' => [
                    ['label' => 'Equipos', 'value' => '18'],
                    ['label' => 'Sin líder', 'value' => '2'],
                    ['label' => 'Sin proyecto', 'value' => '1'],
                ],
                'formulario' => [
                    'titulo' => 'Reglas de generación',
                    'campos' => [
                        ['label' => 'Grupo', 'tipo' => 'select', 'opciones' => ['9B', '9A', '8A']],
                        ['label' => 'Integrantes por equipo', 'tipo' => 'number', 'valor' => '5'],
                        ['label' => 'Respetar equipo desde Excel', 'tipo' => 'checkbox', 'valor' => '1'],
                        ['label' => 'Asignar líder automáticamente', 'tipo' => 'checkbox', 'valor' => '1'],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Equipos generados',
                    'columnas' => ['Equipo', 'Grupo', 'Integrantes', 'Líder', 'Proyecto'],
                    'filas' => [
                        ['Equipo 1', '9B', '5', 'Ana López', 'Sistema de inventario'],
                        ['Equipo 2', '9B', '5', 'Luis Pérez', 'Gestor de citas'],
                        ['Equipo 3', '9A', '4', 'Pendiente', 'Sin asignar'],
                    ],
                ],
            ],
            'proyectos' => [
                'titulo' => 'Proyectos integradores',
                'subtitulo' => 'Relación entre equipo, guía, asesores, docentes y asignaturas.',
                'acciones' => ['Nuevo proyecto', 'Asignar asesores', 'Vincular materias'],
                'metricas' => [
                    ['label' => 'Proyectos', 'value' => '18'],
                    ['label' => 'Multidisciplinarios', 'value' => '12'],
                    ['label' => 'Sin asesor', 'value' => '1'],
                ],
                'formulario' => [
                    'titulo' => 'Asignación del proyecto',
                    'campos' => [
                        ['label' => 'Equipo', 'tipo' => 'select', 'opciones' => ['Equipo 1', 'Equipo 2', 'Equipo 3']],
                        ['label' => 'Título del proyecto', 'tipo' => 'text', 'valor' => 'Sistema web de administración'],
                        ['label' => 'Asesor principal', 'tipo' => 'select', 'opciones' => ['Docente Asesor UTVM', 'Docente Evaluador 2']],
                        ['label' => 'Asignaturas participantes', 'tipo' => 'text', 'valor' => 'Integradora, Desarrollo web, Base de datos'],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Proyectos activos',
                    'columnas' => ['Proyecto', 'Equipo', 'Asesor', 'Docentes', 'Estado'],
                    'filas' => [
                        ['Sistema web de administración', 'Equipo 1', 'Docente Asesor UTVM', '3', 'En proceso'],
                        ['Gestor de citas', 'Equipo 2', 'Docente Evaluador 2', '2', 'En revisión'],
                    ],
                ],
            ],
            'avances' => [
                'titulo' => 'Registro de avances',
                'subtitulo' => 'Captura por apartado y carga de evidencias solicitadas.',
                'acciones' => ['Guardar avance', 'Enviar entrega', 'Ver comentarios'],
                'metricas' => [
                    ['label' => 'Apartado actual', 'value' => '3'],
                    ['label' => 'Evidencias', 'value' => '7'],
                    ['label' => 'Correcciones', 'value' => '2'],
                ],
                'formulario' => [
                    'titulo' => 'Captura del apartado',
                    'campos' => [
                        ['label' => 'Apartado', 'tipo' => 'select', 'opciones' => ['Project Charter', 'Modelo entidad relación', 'Módulos funcionales']],
                        ['label' => 'Contenido capturado', 'tipo' => 'textarea', 'valor' => 'Desarrollo del apartado conforme a la guía.'],
                        ['label' => 'Evidencia', 'tipo' => 'file'],
                        ['label' => 'Repositorio relacionado', 'tipo' => 'url', 'valor' => 'https://github.com/equipo/proyecto'],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Avances enviados',
                    'columnas' => ['Apartado', 'Versión', 'Fecha', 'Estado', 'Resultado'],
                    'filas' => [
                        ['Project Charter', '1', '28/06/2026', 'Revisada', 'Aprobada'],
                        ['Modelo entidad relación', '2', '12/07/2026', 'En revisión', 'Pendiente'],
                        ['Módulos funcionales', '1', '20/07/2026', 'Corrección', 'Requiere ajuste'],
                    ],
                ],
            ],
            'revision-entregables' => [
                'titulo' => 'Revisión de entregables',
                'subtitulo' => 'Validación por apartado, observaciones y calificación individual.',
                'acciones' => ['Guardar revisión', 'Solicitar corrección', 'Marcar aprobada'],
                'metricas' => [
                    ['label' => 'Pendientes', 'value' => '14'],
                    ['label' => 'En revisión', 'value' => '5'],
                    ['label' => 'Aprobadas', 'value' => '31'],
                ],
                'formulario' => [
                    'titulo' => 'Evaluación del apartado',
                    'campos' => [
                        ['label' => 'Validar entregable', 'tipo' => 'checkbox', 'valor' => '1'],
                        ['label' => 'Calificación', 'tipo' => 'number', 'valor' => '9.5'],
                        ['label' => 'Resultado', 'tipo' => 'select', 'opciones' => ['Aprobada', 'Requiere corrección', 'Rechazada']],
                        ['label' => 'Observaciones', 'tipo' => 'textarea', 'valor' => 'Agregar evidencia del proceso de validación.'],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Bandeja de revisión',
                    'columnas' => ['Equipo', 'Apartado', 'Versión', 'Entrega', 'Estado'],
                    'filas' => [
                        ['Equipo 1', 'Modelo entidad relación', '2', '12/07/2026', 'En revisión'],
                        ['Equipo 2', 'Project Charter', '1', '11/07/2026', 'Pendiente'],
                        ['Equipo 3', 'Producto de código', '3', '10/07/2026', 'Corrección'],
                    ],
                ],
            ],
            'documentos' => [
                'titulo' => 'Generación del documento final',
                'subtitulo' => 'Plantilla institucional integrada con los apartados completados.',
                'acciones' => ['Vista previa', 'Generar PDF', 'Generar Word'],
                'metricas' => [
                    ['label' => 'Apartados integrados', 'value' => '5/8'],
                    ['label' => 'Formato', 'value' => 'Institucional'],
                    ['label' => 'Última generación', 'value' => 'Hoy'],
                ],
                'formulario' => [
                    'titulo' => 'Documento institucional',
                    'campos' => [
                        ['label' => 'Plantilla', 'tipo' => 'select', 'opciones' => ['Reporte final UTVM', 'Avance parcial', 'Anexo técnico']],
                        ['label' => 'Incluir portada', 'tipo' => 'checkbox', 'valor' => '1'],
                        ['label' => 'Incluir historial de cambios', 'tipo' => 'checkbox', 'valor' => '1'],
                        ['label' => 'Formato de salida', 'tipo' => 'select', 'opciones' => ['PDF', 'Word (.docx)', 'PDF y Word']],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Apartados del reporte',
                    'columnas' => ['Apartado', 'Origen', 'Estado', 'Incluido', 'Actualizado'],
                    'filas' => [
                        ['Project Charter', 'Captura', 'Aprobado', 'Sí', '28/06/2026'],
                        ['Modelo entidad relación', 'Archivo', 'En revisión', 'Sí', '12/07/2026'],
                        ['Manual técnico', 'Producto software', 'Pendiente', 'No', '-'],
                    ],
                ],
            ],
            'productos-software' => [
                'titulo' => 'Productos de software',
                'subtitulo' => 'Código, ejecutables, bases de datos, scripts, manuales y repositorios.',
                'acciones' => ['Subir producto', 'Validar producto', 'Abrir repositorio'],
                'metricas' => [
                    ['label' => 'Repositorios', 'value' => '12'],
                    ['label' => 'Productos cargados', 'value' => '34'],
                    ['label' => 'Validados', 'value' => '21'],
                ],
                'formulario' => [
                    'titulo' => 'Entrega técnica',
                    'campos' => [
                        ['label' => 'Tipo de producto', 'tipo' => 'select', 'opciones' => ['Código fuente', 'Ejecutable', 'Base de datos', 'Script', 'Manual técnico', 'Manual de usuario']],
                        ['label' => 'Archivo', 'tipo' => 'file'],
                        ['label' => 'Repositorio GitHub/GitLab', 'tipo' => 'url', 'valor' => 'https://github.com/equipo/proyecto'],
                        ['label' => 'Versión', 'tipo' => 'text', 'valor' => 'v1.0.0'],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Productos registrados',
                    'columnas' => ['Producto', 'Equipo', 'Versión', 'Repositorio', 'Estado'],
                    'filas' => [
                        ['Código fuente', 'Equipo 1', 'v1.0.0', 'GitHub', 'Validado'],
                        ['Base de datos', 'Equipo 1', 'v1.0.0', 'Archivo SQL', 'Pendiente'],
                        ['Manual de usuario', 'Equipo 2', 'v0.9.0', 'PDF', 'Corrección'],
                    ],
                ],
            ],
            'historial' => [
                'titulo' => 'Historial de versiones y evaluaciones',
                'subtitulo' => 'Trazabilidad de entregas, responsables, observaciones y resultados.',
                'acciones' => ['Filtrar historial', 'Exportar', 'Ver detalle'],
                'metricas' => [
                    ['label' => 'Versiones', 'value' => '48'],
                    ['label' => 'Observaciones', 'value' => '27'],
                    ['label' => 'Evaluaciones', 'value' => '36'],
                ],
                'formulario' => [
                    'titulo' => 'Filtros',
                    'campos' => [
                        ['label' => 'Proyecto', 'tipo' => 'select', 'opciones' => ['Sistema web de administración', 'Gestor de citas']],
                        ['label' => 'Apartado', 'tipo' => 'select', 'opciones' => ['Todos', 'Project Charter', 'Producto de código']],
                        ['label' => 'Desde', 'tipo' => 'date', 'valor' => '2026-06-24'],
                        ['label' => 'Hasta', 'tipo' => 'date', 'valor' => '2026-08-01'],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Movimientos',
                    'columnas' => ['Fecha', 'Responsable', 'Acción', 'Resultado', 'Observación'],
                    'filas' => [
                        ['12/07/2026', 'Equipo 1', 'Subió versión 2', 'En revisión', 'Se agregó diagrama corregido'],
                        ['13/07/2026', 'Docente Asesor', 'Validó apartado', 'Aprobada', 'Cumple estructura'],
                        ['14/07/2026', 'Docente Asesor', 'Solicitó corrección', 'Corrección', 'Falta evidencia técnica'],
                    ],
                ],
            ],
        ];
    }
}
