<?php

namespace App\Soporte;

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
            'direccion_coordinacion' => [
                ['clave' => 'jerarquia-proyectos', 'titulo' => 'Jerarquía de proyectos', 'ruta' => route('modulos.jerarquia'), 'icono' => '◆'],
                ['clave' => 'periodos', 'titulo' => 'Periodos', 'ruta' => route('modulos.show', 'periodos'), 'icono' => '▦'],
                ['clave' => 'carreras-grupos', 'titulo' => 'Carreras y grupos', 'ruta' => route('modulos.show', 'carreras-grupos'), 'icono' => '▤'],
                ['clave' => 'usuarios', 'titulo' => 'Usuarios', 'ruta' => route('modulos.show', 'usuarios'), 'icono' => '◎'],
                ['clave' => 'asignaturas', 'titulo' => 'Asignaturas', 'ruta' => route('modulos.show', 'asignaturas'), 'icono' => '◇'],
                ['clave' => 'guias', 'titulo' => 'Guías', 'ruta' => route('modulos.show', 'guias'), 'icono' => '▣'],
                ['clave' => 'equipos', 'titulo' => 'Equipos', 'ruta' => route('modulos.show', 'equipos'), 'icono' => '◫'],
                ['clave' => 'proyectos', 'titulo' => 'Proyectos', 'ruta' => route('modulos.show', 'proyectos'), 'icono' => '□'],
                ['clave' => 'reportes', 'titulo' => 'Reportes', 'ruta' => route('modulos.show', 'reportes'), 'icono' => '▥'],
                ['clave' => 'notificaciones', 'titulo' => 'Notificaciones', 'ruta' => route('modulos.show', 'notificaciones'), 'icono' => '○'],
                ['clave' => 'bitacora', 'titulo' => 'Bitácora', 'ruta' => route('modulos.show', 'bitacora'), 'icono' => '≡'],
                ['clave' => 'respaldos', 'titulo' => 'Respaldos', 'ruta' => route('modulos.show', 'respaldos'), 'icono' => '↥'],
            ],
            'encargado_proyectos' => [
                ['clave' => 'jerarquia-proyectos', 'titulo' => 'Líderes y grupos', 'ruta' => route('modulos.jerarquia'), 'icono' => '◆'],
                ['clave' => 'usuarios', 'titulo' => 'Docentes', 'ruta' => route('modulos.show', 'usuarios'), 'icono' => '◉'],
                ['clave' => 'asignaturas', 'titulo' => 'Materias y docentes', 'ruta' => route('modulos.show', 'asignaturas'), 'icono' => '◇'],
                ['clave' => 'proyectos', 'titulo' => 'Proyectos', 'ruta' => route('modulos.show', 'proyectos'), 'icono' => '□'],
                ['clave' => 'reportes', 'titulo' => 'Reportes', 'ruta' => route('modulos.show', 'reportes'), 'icono' => '▥'],
            ],
            'lider_proyecto' => [
                ['clave' => 'usuarios', 'titulo' => 'Lista de alumnos', 'ruta' => route('modulos.show', 'usuarios'), 'icono' => '◉'],
                ['clave' => 'equipos', 'titulo' => 'Equipos', 'ruta' => route('modulos.show', 'equipos'), 'icono' => '◫'],
                ['clave' => 'proyectos', 'titulo' => 'Contexto de proyectos', 'ruta' => route('modulos.show', 'proyectos'), 'icono' => '□'],
                ['clave' => 'reportes', 'titulo' => 'Seguimiento', 'ruta' => route('modulos.show', 'reportes'), 'icono' => '▥'],
            ],
            'docente_materia', 'docente_asesor' => [
                ['clave' => 'revision-entregables', 'titulo' => 'Revisión', 'ruta' => route('modulos.show', 'revision-entregables'), 'icono' => '✓'],
                ['clave' => 'productos-software', 'titulo' => 'Software', 'ruta' => route('modulos.show', 'productos-software'), 'icono' => '⌘'],
                ['clave' => 'historial', 'titulo' => 'Historial', 'ruta' => route('modulos.show', 'historial'), 'icono' => '↺'],
                ['clave' => 'reportes', 'titulo' => 'Reportes', 'ruta' => route('modulos.show', 'reportes'), 'icono' => '▥'],
                ['clave' => 'notificaciones', 'titulo' => 'Notificaciones', 'ruta' => route('modulos.show', 'notificaciones'), 'icono' => '○'],
            ],
            default => [
                ['clave' => 'avances', 'titulo' => 'Avances', 'ruta' => route('modulos.show', 'avances'), 'icono' => '✎'],
                ['clave' => 'documentos', 'titulo' => 'Documento final', 'ruta' => route('modulos.show', 'documentos'), 'icono' => '▧'],
                ['clave' => 'productos-software', 'titulo' => 'Software', 'ruta' => route('modulos.show', 'productos-software'), 'icono' => '⌘'],
                ['clave' => 'historial', 'titulo' => 'Historial', 'ruta' => route('modulos.show', 'historial'), 'icono' => '↺'],
                ['clave' => 'notificaciones', 'titulo' => 'Notificaciones', 'ruta' => route('modulos.show', 'notificaciones'), 'icono' => '○'],
            ],
        };

        return [...$base, ...$porRol];
    }

    /**
     * @return array<string, mixed>
     */
    public static function dashboardPara(string $rol): array
    {
        return match ($rol) {
            'direccion_coordinacion' => [
                'eyebrow' => 'Dirección y coordinación académica',
                'title' => 'Panel de dirección / coordinación',
                'description' => 'Control operativo de periodos, usuarios, equipos, guías, asignaciones, reportes y bitácora.',
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
                    ['title' => 'Equipos y proyectos', 'description' => 'Asignación automática, manual, asesores y evaluadores.', 'status' => 'Preparado', 'route' => 'equipos'],
                    ['title' => 'Reportes', 'description' => 'Avance, cumplimiento, evaluaciones y trazabilidad.', 'status' => 'En tablero', 'route' => 'reportes'],
                ],
            ],
            'encargado_proyectos' => [
                'eyebrow' => 'Administración por carrera y cuatrimestre',
                'title' => 'Panel del encargado de proyectos',
                'description' => 'Asigna líderes, grupos, docentes de materia y las partes que evaluará cada asignatura.',
                'badge' => 'Encargado de proyectos',
                'stats' => [['label' => 'Responsabilidad', 'value' => 'Por cuatrimestre'], ['label' => 'Líderes', 'value' => 'Asignables'], ['label' => 'Grupos', 'value' => 'Supervisados'], ['label' => 'Materias', 'value' => 'Evaluadoras']],
                'modules' => [['title' => 'Jerarquía', 'description' => 'Designación de líderes y grupos.', 'status' => 'Operativo', 'route' => 'jerarquia-proyectos'], ['title' => 'Materias', 'description' => 'Docentes y partes evaluables.', 'status' => 'Configurable', 'route' => 'asignaturas'], ['title' => 'Proyectos', 'description' => 'Seguimiento integral.', 'status' => 'Consulta', 'route' => 'proyectos']],
            ],
            'lider_proyecto' => [
                'eyebrow' => 'Materia líder y organización de equipos',
                'title' => 'Panel del líder de proyecto',
                'description' => 'Carga alumnos, distribuye equipos y define nombre, número y contexto de cada proyecto.',
                'badge' => 'Líder de proyecto',
                'stats' => [['label' => 'Grupos', 'value' => 'Asignados'], ['label' => 'Alumnos', 'value' => 'Por cargar'], ['label' => 'Equipos', 'value' => 'Por organizar'], ['label' => 'Proyectos', 'value' => 'En seguimiento']],
                'modules' => [['title' => 'Lista de alumnos', 'description' => 'Alta y carga por grupo.', 'status' => 'Editable', 'route' => 'usuarios'], ['title' => 'Equipos', 'description' => 'Distribución de integrantes.', 'status' => 'Editable', 'route' => 'equipos'], ['title' => 'Contexto', 'description' => 'Nombre, número y alcance.', 'status' => 'Editable', 'route' => 'proyectos']],
            ],
            'docente_materia', 'docente_asesor' => [
                'eyebrow' => 'Revisión académica',
                'title' => 'Panel de docente / asesor · Docente de materia',
                'description' => 'Revisión y calificación exclusivamente de las partes asignadas a su materia.',
                'badge' => 'Docente de materia',
                'stats' => [
                    ['label' => 'Asignaciones', 'value' => '9'],
                    ['label' => 'Por revisar', 'value' => '14'],
                    ['label' => 'Con observación', 'value' => '6'],
                    ['label' => 'Aprobadas', 'value' => '31'],
                ],
                'modules' => [
                    ['title' => 'Entregables', 'description' => 'Validación individual con checkbox, observaciones y calificación.', 'status' => 'Lista de trabajo', 'route' => 'revision-entregables'],
                    ['title' => 'Productos de software', 'description' => 'Código, repositorios, ejecutables, scripts y manuales.', 'status' => 'Validable', 'route' => 'productos-software'],
                    ['title' => 'Historial', 'description' => 'Versiones, responsables, fechas y resultados.', 'status' => 'Trazable', 'route' => 'historial'],
                    ['title' => 'Reportes', 'description' => 'Cumplimiento por grupo, equipo, apartado y asignatura.', 'status' => 'Consulta', 'route' => 'reportes'],
                ],
            ],
            default => [
                'eyebrow' => 'Trabajo por equipo',
                'title' => 'Panel de estudiante / equipo',
                'description' => 'Seguimiento de guía, captura de avances, evidencias, documento final y productos de software.',
                'badge' => 'Estudiante / equipo',
                'stats' => [
                    ['label' => 'Apartados', 'value' => '8'],
                    ['label' => 'Completados', 'value' => '5'],
                    ['label' => 'Correcciones', 'value' => '2'],
                    ['label' => 'Avance', 'value' => '62%'],
                ],
                'modules' => [
                    ['title' => 'Avances', 'description' => 'Captura por apartado y carga de evidencias.', 'status' => 'Activo', 'route' => 'avances'],
                    ['title' => 'Documento final', 'description' => 'Generación institucional en PDF y Word.', 'status' => 'Plantilla', 'route' => 'documentos'],
                    ['title' => 'Software', 'description' => 'Repositorio, código fuente, ejecutables y manuales.', 'status' => 'Entrega', 'route' => 'productos-software'],
                    ['title' => 'Historial', 'description' => 'Versiones enviadas, observaciones y resultados.', 'status' => 'Consulta', 'route' => 'historial'],
                ],
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
                'subtitulo' => 'Estructura académica usada para equipos, asignaturas y reportes.',
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
            'reportes' => [
                'titulo' => 'Reportes académicos',
                'subtitulo' => 'Avance, cumplimiento, calificaciones, entregas pendientes y participación.',
                'acciones' => ['Generar reporte', 'Exportar Excel', 'Exportar PDF'],
                'metricas' => [
                    ['label' => 'Cumplimiento', 'value' => '76%'],
                    ['label' => 'Atrasos', 'value' => '8'],
                    ['label' => 'Promedio', 'value' => '8.7'],
                ],
                'formulario' => [
                    'titulo' => 'Parámetros',
                    'campos' => [
                        ['label' => 'Periodo', 'tipo' => 'select', 'opciones' => ['Mayo - Agosto 2026', 'Septiembre - Noviembre 2026']],
                        ['label' => 'Grupo', 'tipo' => 'select', 'opciones' => ['Todos', '9B', '9A']],
                        ['label' => 'Tipo de reporte', 'tipo' => 'select', 'opciones' => ['Avance', 'Cumplimiento', 'Calificaciones', 'Pendientes']],
                        ['label' => 'Incluir observaciones', 'tipo' => 'checkbox', 'valor' => '1'],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Resumen por equipo',
                    'columnas' => ['Equipo', 'Avance', 'Pendientes', 'Calificación', 'Riesgo'],
                    'filas' => [
                        ['Equipo 1', '82%', '1', '9.1', 'Bajo'],
                        ['Equipo 2', '67%', '3', '8.4', 'Medio'],
                        ['Equipo 3', '44%', '5', '7.2', 'Alto'],
                    ],
                ],
            ],
            'notificaciones' => [
                'titulo' => 'Notificaciones',
                'subtitulo' => 'Entregas pendientes, observaciones, revisiones y cambios de estado.',
                'acciones' => ['Marcar leídas', 'Nueva notificación', 'Configurar avisos'],
                'metricas' => [
                    ['label' => 'No leídas', 'value' => '6'],
                    ['label' => 'Pendientes hoy', 'value' => '3'],
                    ['label' => 'Observaciones', 'value' => '4'],
                ],
                'formulario' => [
                    'titulo' => 'Aviso manual',
                    'campos' => [
                        ['label' => 'Destinatario', 'tipo' => 'select', 'opciones' => ['Equipo 1', 'Grupo 9B', 'Docentes / asesores']],
                        ['label' => 'Título', 'tipo' => 'text', 'valor' => 'Entrega próxima a vencer'],
                        ['label' => 'Mensaje', 'tipo' => 'textarea', 'valor' => 'Revisar fecha límite del apartado activo.'],
                        ['label' => 'Enviar correo', 'tipo' => 'checkbox', 'valor' => '1'],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Bandeja',
                    'columnas' => ['Fecha', 'Tipo', 'Mensaje', 'Destino', 'Estado'],
                    'filas' => [
                        ['Hoy', 'Entrega', 'Modelo entidad relación vence pronto', 'Equipo 1', 'No leída'],
                        ['Ayer', 'Observación', 'Se registró comentario de revisión', 'Equipo 2', 'Leída'],
                        ['10/07/2026', 'Sistema', 'Guía publicada', 'Grupo 9B', 'Leída'],
                    ],
                ],
            ],
            'bitacora' => [
                'titulo' => 'Bitácora de actividades',
                'subtitulo' => 'Registro de acciones relevantes dentro del sistema.',
                'acciones' => ['Filtrar', 'Exportar bitácora', 'Auditar usuario'],
                'metricas' => [
                    ['label' => 'Eventos hoy', 'value' => '57'],
                    ['label' => 'Usuarios activos', 'value' => '41'],
                    ['label' => 'Cambios críticos', 'value' => '2'],
                ],
                'formulario' => [
                    'titulo' => 'Búsqueda',
                    'campos' => [
                        ['label' => 'Usuario', 'tipo' => 'text', 'valor' => '20260003'],
                        ['label' => 'Módulo', 'tipo' => 'select', 'opciones' => ['Todos', 'Guías', 'Entregas', 'Usuarios']],
                        ['label' => 'Acción', 'tipo' => 'select', 'opciones' => ['Todas', 'Crear', 'Editar', 'Eliminar', 'Validar']],
                        ['label' => 'Fecha', 'tipo' => 'date', 'valor' => '2026-07-11'],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Eventos recientes',
                    'columnas' => ['Hora', 'Usuario', 'Módulo', 'Acción', 'Detalle'],
                    'filas' => [
                        ['09:15', '20260001', 'Guías', 'Editar', 'Cambió fecha límite'],
                        ['10:22', '20260003', 'Entregas', 'Crear', 'Subió evidencia'],
                        ['11:04', '20260002', 'Revisión', 'Validar', 'Aprobó apartado'],
                    ],
                ],
            ],
            'respaldos' => [
                'titulo' => 'Respaldo y recuperación',
                'subtitulo' => 'Copias de seguridad, restauración y protección de información.',
                'acciones' => ['Crear respaldo', 'Restaurar', 'Programar copia'],
                'metricas' => [
                    ['label' => 'Último respaldo', 'value' => 'Hoy'],
                    ['label' => 'Tamaño', 'value' => '248 MB'],
                    ['label' => 'Estado', 'value' => 'Correcto'],
                ],
                'formulario' => [
                    'titulo' => 'Programación',
                    'campos' => [
                        ['label' => 'Frecuencia', 'tipo' => 'select', 'opciones' => ['Diaria', 'Semanal', 'Mensual']],
                        ['label' => 'Hora', 'tipo' => 'time', 'valor' => '22:00'],
                        ['label' => 'Incluir archivos de entrega', 'tipo' => 'checkbox', 'valor' => '1'],
                        ['label' => 'Mantener versiones', 'tipo' => 'number', 'valor' => '5'],
                    ],
                ],
                'tabla' => [
                    'titulo' => 'Respaldos disponibles',
                    'columnas' => ['Fecha', 'Contenido', 'Tamaño', 'Responsable', 'Estado'],
                    'filas' => [
                        ['11/07/2026', 'Base de datos y archivos', '248 MB', 'Sistema', 'Correcto'],
                        ['10/07/2026', 'Base de datos', '91 MB', 'Sistema', 'Correcto'],
                    ],
                ],
            ],
        ];
    }
}
