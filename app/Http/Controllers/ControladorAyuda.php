<?php

namespace App\Http\Controllers;

use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ControladorAyuda extends Controller
{
    public function __invoke(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';
        $navegacion = SistemaInterfaz::navegacionPara($rol);
        $ayuda = $this->ayudaPorRol($rol, $navegacion);
        $seccionSolicitada = $request->query('seccion', 'iniciar-sesion');
        $seccion = collect($ayuda['secciones'])->firstWhere('id', $seccionSolicitada)
            ?? $ayuda['secciones'][0];

        return view('ayuda', [
            'ayuda' => $ayuda,
            'seccion' => $seccion,
        ]);
    }

    public function acceso(Request $request): View
    {
        $ayuda = $this->ayudaAcceso();
        $seccionSolicitada = $request->query('seccion', 'iniciar-sesion');
        $seccion = collect($ayuda['secciones'])->firstWhere('id', $seccionSolicitada)
            ?? $ayuda['secciones'][0];

        return view('ayuda', [
            'ayuda' => $ayuda,
            'seccion' => $seccion,
        ]);
    }

    /**
     * @param  array<int, array<string, string>>  $navegacion
     * @return array<string, mixed>
     */
    private function ayudaPorRol(string $rol, array $navegacion): array
    {
        $rutas = collect($navegacion)
            ->mapWithKeys(fn (array $item): array => [$item['clave'] => $item['ruta']])
            ->all();

        $contenidoRol = match ($rol) {
            'coordinacion' => $this->ayudaCoordinacion($rutas),
            'docente_lider' => $this->ayudaDocenteLider($rutas),
            'docente_materia' => $this->ayudaDocenteMateria($rutas),
            default => $this->ayudaEstudiante($rutas),
        };

        $secciones = [
            $this->seccionAcceso(),
            ...$contenidoRol['secciones'],
            $this->seccionCerrarSesion(),
            $this->seccionContacto(),
        ];

        return [
            'eyebrow' => $contenidoRol['eyebrow'],
            'titulo' => $contenidoRol['titulo'],
            'descripcion' => $contenidoRol['descripcion'],
            'secciones' => $secciones,
            'faqs' => $this->faqs($rol),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ayudaAcceso(): array
    {
        return [
            'eyebrow' => 'Acceso al sistema',
            'titulo' => 'Ayuda de acceso',
            'descripcion' => 'Guía visual para entrar al sistema, recuperar el acceso y cambiar la contraseña de tu cuenta.',
            'secciones' => [
                $this->seccionAcceso(),
                $this->seccionRecuperarContrasena(),
                $this->seccionActualizarContrasena(),
            ],
            'faqs' => [
                ['pregunta' => 'No puedo entrar al sistema', 'respuesta' => 'Verifica matrícula y contraseña. Si el problema continúa, usa la recuperación de acceso o reporta el mensaje que aparece.'],
                ['pregunta' => 'No recuerdo mi contraseña', 'respuesta' => 'Solicita un código al correo registrado, escríbelo en el sistema y define una contraseña nueva. El código vence a los 10 minutos y puedes pedir otro después de 3 minutos.'],
            ],
        ];
    }

    /**
     * @param  array<string, string>  $rutas
     * @return array<string, mixed>
     */
    private function ayudaCoordinacion(array $rutas): array
    {
        return [
            'eyebrow' => 'Gestión académica',
            'titulo' => 'Ayuda de tu panel',
            'descripcion' => 'Guía visual para configurar periodos, usuarios, grupos, asignaturas y guías integradoras.',
            'secciones' => [
                $this->seccionModulo(
                    id: 'panel-principal',
                    menu: 'Panel principal',
                    titulo: 'Panel principal',
                    descripcion: 'Esta pantalla muestra indicadores del periodo activo y avisos que conviene atender antes de capturar nueva información.',
                    pasos: [
                        'Revisa las métricas generales para saber si ya existen carreras, grupos, alumnos y guías configuradas.',
                        'Lee los avisos de atención operativa para ubicar pendientes antes de continuar.',
                        'Usa la tabla del periodo para confirmar grupo, responsable y cantidad de equipos registrados.',
                    ],
                    imagen: 'assets/ayuda/coordinacion/01-panel-principal.png',
                    ruta: $rutas['dashboard'] ?? null,
                    notas: ['Métricas del periodo', 'Alertas operativas', 'Resumen de grupos'],
                ),
                $this->seccionModulo(
                    id: 'jerarquia-proyectos',
                    menu: 'Jerarquía de proyectos',
                    titulo: 'Jerarquía de proyectos',
                    descripcion: 'Aquí se indica quién queda como responsable de cada grupo y qué asignatura organiza el proyecto integrador.',
                    pasos: [
                        'Filtra por periodo o carrera cuando necesites trabajar con un bloque específico.',
                        'Selecciona el responsable del grupo y la asignatura principal en la fila correspondiente.',
                        'Presiona Guardar asignación en cada fila antes de pasar a equipos o proyectos.',
                    ],
                    imagen: 'assets/ayuda/coordinacion/02-jerarquia-proyectos.png',
                    ruta: $rutas['jerarquia-proyectos'] ?? null,
                    notas: ['Filtros superiores', 'Asignación vigente', 'Acción de guardado'],
                ),
                $this->seccionModulo(
                    id: 'periodos',
                    menu: 'Periodos',
                    titulo: 'Periodos académicos',
                    descripcion: 'Los periodos controlan las fechas de trabajo, entregas y configuración académica que usarán las demás pantallas.',
                    pasos: [
                        'Usa Nuevo periodo para abrir el formulario de captura.',
                        'Captura nombre y fechas; el periodo nuevo se guarda como borrador.',
                        'Actívalo cuando no exista otro periodo activo. Para cerrarlo, revisa los pendientes y confirma el cierre; el historial queda disponible para consulta.',
                    ],
                    imagen: 'assets/ayuda/actualizadas/coordinacion-periodos.png',
                    ruta: $rutas['periodos'] ?? null,
                    notas: ['Nuevo: borrador', 'Un solo periodo activo', 'Cierre con revisión de pendientes'],
                ),
                $this->seccionModulo(
                    id: 'carreras-grupos',
                    menu: 'Carreras y grupos',
                    titulo: 'Carreras y grupos',
                    descripcion: 'Esta vista organiza carreras y grupos para que después puedan relacionarse alumnos, docentes, equipos y proyectos.',
                    pasos: [
                        'Crea primero la carrera con su nombre y clave.',
                        'Agrega grupos indicando carrera, periodo y grado. El grupo debe ser una sola letra de la A a la L.',
                        'Usa los filtros para revisar que la estructura quedó guardada en el periodo correcto.',
                    ],
                    imagen: 'assets/ayuda/coordinacion/04-carreras-grupos.png',
                    ruta: $rutas['carreras-grupos'] ?? null,
                    notas: ['Alta de estructura', 'Filtros por periodo', 'Grupos por carrera'],
                ),
                $this->seccionModulo(
                    id: 'usuarios',
                    menu: 'Usuarios',
                    titulo: 'Usuarios',
                    descripcion: 'Permite consultar listas de alumnos, registrar cuentas y revisar datos de acceso antes de guardar.',
                    pasos: [
                        'Usa las pestañas para cambiar entre lista de alumnos y registro de personal académico.',
                        'Descarga la plantilla de alumnos en formato Excel, llena una fila por alumno y selecciónala al cargar la lista del periodo.',
                        'Revisa la vista previa y valida matrícula, nombre y correo. Selecciona carrera, grado y grupo para cada archivo antes de confirmar la carga.',
                    ],
                    imagen: 'assets/ayuda/coordinacion/05-usuarios.png',
                    ruta: $rutas['usuarios'] ?? null,
                    notas: ['Descarga de plantilla', 'Vista previa de alumnos', 'Filtros y listados'],
                ),
                $this->seccionModulo(
                    id: 'asignaturas',
                    menu: 'Asignaturas',
                    titulo: 'Asignaturas',
                    descripcion: 'Concentra el catálogo de materias y permite relacionarlas con las personas que las atenderán en el periodo.',
                    pasos: [
                        'Registra asignaturas con carrera, clave, grado y nombre.',
                        'Vincula responsables a las asignaturas que participarán en la guía integradora.',
                        'Filtra por carrera y cuatrimestre para validar la carga académica.',
                    ],
                    imagen: 'assets/ayuda/coordinacion/06-asignaturas.png',
                    ruta: $rutas['asignaturas'] ?? null,
                    notas: ['Catálogo', 'Asignación docente', 'Vista operativa'],
                ),
                $this->seccionModulo(
                    id: 'guias',
                    menu: 'Guías',
                    titulo: 'Guías integradoras',
                    descripcion: 'Las guías definen apartados, ponderaciones, fechas límite y responsables de revisión.',
                    pasos: [
                        'Crea la guía como borrador y agrega apartados con orden, ponderación, fecha límite y tipo de evidencia.',
                        'Usa Actualizar vista previa para ver cambios sin guardar y abre el PDF de la guía antes de publicarla.',
                        'Asigna evaluadores y publica la guía cuando esté completa. Se cierra al terminar el periodo.',
                    ],
                    imagen: 'assets/ayuda/actualizadas/coordinacion-guias.png',
                    ruta: $rutas['guias'] ?? null,
                    notas: ['Borrador y publicación', 'Vista previa sin guardar', 'Apartados y evaluadores'],
                ),
                $this->seccionModulo(
                    id: 'busqueda-panel',
                    menu: 'Búsqueda del panel',
                    titulo: 'Buscar acciones del sistema',
                    descripcion: 'El buscador del panel relaciona palabras, sinónimos y errores leves con acciones disponibles para coordinación.',
                    pasos: [
                        'Escribe la tarea que quieres hacer aunque no conozcas el nombre exacto de la pantalla.',
                        'Elige una sugerencia para abrir la vista correspondiente.',
                        'Solo aparecen acciones permitidas para tu cuenta.',
                    ],
                    imagen: 'assets/ayuda/actualizadas/coordinacion-busqueda.png',
                    ruta: $rutas['dashboard'] ?? null,
                    notas: ['Acciones permitidas', 'Sinónimos y fragmentos'],
                ),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $rutas
     * @return array<string, mixed>
     */
    private function ayudaDocenteLider(array $rutas): array
    {
        return [
            'eyebrow' => 'Gestión de grupos',
            'titulo' => 'Ayuda de tu panel',
            'descripcion' => 'Guía para consultar alumnos, formar equipos, revisar código, enviar recordatorios y decidir cierres o prórrogas.',
            'secciones' => [
                $this->seccionModulo(
                    id: 'panel-principal',
                    menu: 'Panel principal',
                    titulo: 'Panel principal',
                    descripcion: 'Esta pantalla muestra tus grupos de trabajo y el avance general de equipos y proyectos.',
                    pasos: [
                        'Confirma que aparezcan los grupos activos del periodo.',
                        'Revisa alumnos y equipos antes de asignar proyectos.',
                        'Usa este panel como punto de regreso cuando termines una actividad.',
                    ],
                    imagen: 'assets/ayuda/docente-lider/01-panel-principal.png',
                    ruta: $rutas['dashboard'] ?? null,
                    notas: ['Alcance del docente', 'Resumen de grupos', 'Navegación lateral'],
                ),
                $this->seccionModulo(
                    id: 'lista-alumnos',
                    menu: 'Lista de alumnos',
                    titulo: 'Lista de alumnos',
                    descripcion: 'Consulta los alumnos de tus grupos para validar que la carga académica sea correcta antes de formar equipos.',
                    pasos: [
                        'Filtra por periodo, carrera, grupo o búsqueda.',
                        'Revisa matrícula, nombre, correo y grupo de cada alumno.',
                        'Si falta un alumno, reporta la corrección con el responsable del sistema.',
                    ],
                    imagen: 'assets/ayuda/docente-lider/02-lista-alumnos.png',
                    ruta: $rutas['usuarios'] ?? null,
                    notas: ['Filtros', 'Grupos visibles', 'Listado de alumnos'],
                ),
                $this->seccionModulo(
                    id: 'equipos',
                    menu: 'Equipos',
                    titulo: 'Equipos',
                    descripcion: 'Organiza integrantes, responsable del equipo, asesores y relación con proyectos.',
                    pasos: [
                        'Selecciona el grupo que deseas revisar.',
                        'Usa Nuevo equipo para crear un equipo o ajusta integrantes desde el detalle.',
                        'Verifica que cada equipo tenga proyecto asignado antes de continuar.',
                    ],
                    imagen: 'assets/ayuda/docente-lider/03-equipos.png',
                    ruta: $rutas['equipos'] ?? null,
                    notas: ['Grupos asignados', 'Tabla de equipos', 'Detalle de información'],
                ),
                $this->seccionModulo(
                    id: 'proyectos',
                    menu: 'Proyectos',
                    titulo: 'Proyectos',
                    descripcion: 'Relaciona equipos con proyectos, asesores y asignaturas participantes para dar seguimiento.',
                    pasos: [
                        'Filtra por grupo para ver sus proyectos.',
                        'Revisa asesor principal, docentes vinculados y estado del proyecto.',
                        'Completa las asignaciones que falten desde las acciones disponibles.',
                    ],
                    imagen: 'assets/ayuda/docente-lider/04-proyectos.png',
                    ruta: $rutas['proyectos'] ?? null,
                    notas: ['Filtro por grupo', 'Proyecto por equipo', 'Docentes y asignaturas'],
                ),
                $this->seccionModulo(
                    id: 'recordatorios',
                    menu: 'Recordatorios',
                    titulo: 'Recordatorios de entrega',
                    descripcion: 'Como docente líder puedes recordar entregas pendientes a alumnos y revisiones pendientes a docentes de tus equipos, y editar el texto de cada tipo de correo.',
                    pasos: [
                        'Abre Recordatorios. Enviar recordatorios a todos mis proyectos incluye todos tus proyectos en proceso, aunque no aparezcan en la página actual. Enviar recordatorios ahora actúa solo sobre el proyecto de esa fila.',
                        'En Formatos de correo, edita el texto de 72 horas, 24 horas, envío manual y revisión pendiente de docentes. Usa las variables indicadas en la vista y pulsa Guardar formato en cada bloque.',
                        'La cola envía los correos. Antes de mandar un aviso, comprueba que el alumno aún tenga la entrega pendiente o que el docente siga asignado y no haya revisado la versión actual.',
                    ],
                    imagen: 'assets/ayuda/actualizadas/lider-recordatorios.png',
                    ruta: $rutas['recordatorios'] ?? null,
                    notas: ['Solo tus proyectos', 'Alumnos: entregas pendientes', 'Docentes: revisiones pendientes'],
                ),
                $this->seccionModulo(
                    id: 'revision-codigo',
                    menu: 'Revisión de código',
                    titulo: 'Revisión de código',
                    descripcion: 'Permite revisar repositorios, URL de demostración opcional, archivos técnicos y comentarios del producto final.',
                    pasos: [
                        'Si recibes un aviso de revisión pendiente de código, abre el enlace o entra a Revisión de código. Filtra por grupo para ubicar la versión actual.',
                        'Abre el repositorio o la URL de demostración; descarga los archivos o el instalador para revisar el trabajo. Solo los docentes autorizados ven estos recursos.',
                        'Guarda resultado, calificación y comentarios técnicos cuando corresponda.',
                    ],
                    imagen: 'assets/ayuda/actualizadas/lider-revision-codigo.png',
                    ruta: $rutas['revision-codigo'] ?? null,
                    notas: ['Filtro de grupo', 'URL opcional y archivos privados', 'Registro de revisión'],
                ),
                $this->seccionModulo(
                    id: 'estado-guias',
                    menu: 'Estado de las guías',
                    titulo: 'Estado de las guías',
                    descripcion: 'Reúne el avance de los proyectos de tus grupos, incluidos periodos anteriores, PDF finales y pendientes de cierre.',
                    pasos: [
                        'Filtra por periodo, grupo, estado o texto. El resumen superior considera todos los resultados filtrados, aunque la lista tenga varias páginas.',
                        'Cambia entre 10, 20 o 40 proyectos por página y abre cada apartado para revisar entregas, firmas y motivos pendientes.',
                        'Usa Ver formato y firmas para consultar el documento; Volver conserva filtros y página. Si corresponde, abre Revisar cierre y prórroga.',
                    ],
                    imagen: 'assets/ayuda/actualizadas/lider-estado-guias.png',
                    ruta: $rutas['estado-guias'] ?? null,
                    notas: ['Resumen global', 'Paginación', 'Firmas y PDF'],
                ),
                $this->seccionModulo(
                    id: 'cierres',
                    menu: 'Cierres y prórrogas',
                    titulo: 'Cierres y prórrogas',
                    descripcion: 'Cuando termina el plazo con pendientes, el docente líder decide el seguimiento de cada proyecto de su grupo.',
                    pasos: [
                        'Revisa si faltan entregas, correcciones, firmas o el PDF y consulta el historial de avisos y decisiones.',
                        'Puedes cerrar con constancia de pendientes o dar una prórroga al equipo con nueva fecha y apartados concretos.',
                        'La prórroga abre solo esos apartados para ese equipo; si vence con pendientes, se requiere una decisión nueva.',
                    ],
                    imagen: 'assets/ayuda/actualizadas/lider-cierres.png',
                    ruta: $rutas['cierres'] ?? null,
                    notas: ['Pendientes identificados', 'Apartados seleccionados', 'Historial de decisiones'],
                ),
                $this->seccionFirma($rutas),
                $this->seccionDocumento(),
                $this->seccionModulo(
                    id: 'busqueda-panel',
                    menu: 'Búsqueda del panel',
                    titulo: 'Buscar acciones del sistema',
                    descripcion: 'Escribe la acción que buscas; el panel relaciona sinónimos, fragmentos y errores leves con tus opciones disponibles.',
                    pasos: [
                        'Escribe una tarea, por ejemplo revisar firmas o consultar PDF.',
                        'Selecciona una sugerencia para abrir la vista.',
                        'Solo se muestran acciones permitidas para tu cuenta.',
                    ],
                    imagen: 'assets/ayuda/actualizadas/lider-busqueda.png',
                    ruta: $rutas['dashboard'] ?? null,
                    notas: ['Acciones permitidas', 'Sinónimos y fragmentos'],
                ),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $rutas
     * @return array<string, mixed>
     */
    private function ayudaDocenteMateria(array $rutas): array
    {
        return [
            'eyebrow' => 'Revisión académica',
            'titulo' => 'Ayuda de tu panel',
            'descripcion' => 'Guía visual para consultar apartados asignados y registrar revisiones de entregas.',
            'secciones' => [
                $this->seccionModulo(
                    id: 'panel-principal',
                    menu: 'Panel principal',
                    titulo: 'Panel principal',
                    descripcion: 'Esta pantalla resume entregas recibidas, pendientes de revisión y próximos apartados por atender.',
                    pasos: [
                        'Revisa apartados asignados y entregas por revisar.',
                        'Atiende primero los avisos de vencidas sin entrega o recibidas sin revisar.',
                        'Usa el bloque de próximos apartados para preparar tus evaluaciones.',
                    ],
                    imagen: 'assets/ayuda/docente-materia/01-panel-principal.png',
                    ruta: $rutas['dashboard'] ?? null,
                    notas: ['Carga asignada', 'Alertas de atención', 'Próximos apartados'],
                ),
                $this->seccionModulo(
                    id: 'mis-asignaciones',
                    menu: 'Mis asignaciones',
                    titulo: 'Mis asignaciones',
                    descripcion: 'Muestra los apartados, fechas límite y materias que debes revisar en el periodo activo.',
                    pasos: [
                        'Consulta orden, título, fecha límite y ponderación.',
                        'Verifica la materia o etiqueta con la que participas.',
                        'Si falta un apartado, reporta la corrección con el responsable del sistema.',
                    ],
                    imagen: 'assets/ayuda/docente-materia/02-mis-asignaciones.png',
                    ruta: $rutas['asignaciones-docente'] ?? null,
                    notas: ['Apartados', 'Materia vinculada', 'Fecha límite'],
                ),
                $this->seccionModulo(
                    id: 'revisiones',
                    menu: 'Revisiones',
                    titulo: 'Revisión de entregas',
                    descripcion: 'Bandeja donde revisas evidencias, registras resultado y agregas comentarios.',
                    pasos: [
                        'Si recibes un aviso de revisión pendiente, abre su enlace a Revisiones. Filtra por equipo, proyecto, apartado, grupo o estado para ubicar la versión actual.',
                        'Abre los archivos de evidencia antes de calificar.',
                        'Registra resultado, calificación y observaciones. Para aprobar y autorizar tu firma debes tenerla registrada; una nueva versión requiere revisión nueva.',
                    ],
                    imagen: 'assets/ayuda/actualizadas/materia-revisiones.png',
                    ruta: $rutas['revisiones-docente'] ?? null,
                    notas: ['Filtros de revisión', 'Versión de la entrega', 'Firma autorizada al aprobar'],
                ),
                $this->seccionFirma($rutas),
                $this->seccionDocumento(),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $rutas
     * @return array<string, mixed>
     */
    private function ayudaEstudiante(array $rutas): array
    {
        return [
            'eyebrow' => 'Trabajo del equipo',
            'titulo' => 'Ayuda de tu panel',
            'descripcion' => 'Guía visual para consultar el proyecto, enviar entregas y registrar código o repositorio.',
            'secciones' => [
                $this->seccionModulo(
                    id: 'panel-principal',
                    menu: 'Panel principal',
                    titulo: 'Panel principal',
                    descripcion: 'Esta pantalla muestra el avance del equipo y el estado de cada apartado de la guía.',
                    pasos: [
                        'Revisa el porcentaje validado y la distribución de estados.',
                        'Ubica apartados sin entregar, pendientes, rechazados o validados.',
                        'Usa el resumen para decidir qué evidencia debes atender primero.',
                    ],
                    imagen: 'assets/ayuda/estudiante/01-panel-principal.png',
                    ruta: $rutas['dashboard'] ?? null,
                    notas: ['Progreso del equipo', 'Estados de entrega', 'Resumen de la guía'],
                ),
                $this->seccionModulo(
                    id: 'mi-proyecto',
                    menu: 'Mi proyecto',
                    titulo: 'Mi proyecto',
                    descripcion: 'Consulta información del equipo, proyecto, asesores, asignaturas e integrantes.',
                    pasos: [
                        'Confirma que aparezcas dentro del equipo correcto.',
                        'Revisa asesor, participantes académicos y guía integradora asignada.',
                        'Si hubo una prórroga, usa el selector de proyecto y periodo para volver al trabajo anterior. Si algún dato no coincide, repórtalo.',
                    ],
                    imagen: 'assets/ayuda/estudiante/02-mi-proyecto.png',
                    ruta: $rutas['mi-proyecto'] ?? null,
                    notas: ['Datos del equipo', 'Docentes vinculados', 'Integrantes'],
                ),
                $this->seccionModulo(
                    id: 'entregas',
                    menu: 'Entregas',
                    titulo: 'Entregas',
                    descripcion: 'Aquí se cargan evidencias por apartado y se consulta el estado de cada versión enviada.',
                    pasos: [
                        'Selecciona el apartado que corresponde a tu evidencia.',
                        'Carga los archivos permitidos antes de la fecha límite o dentro de una prórroga de este equipo y apartado.',
                        'Revisa comentarios y calificación cuando el docente concluya la revisión.',
                    ],
                    imagen: 'assets/ayuda/actualizadas/estudiante-entregas.png',
                    ruta: $rutas['mis-entregas'] ?? null,
                    notas: ['Apartados', 'Fecha límite', 'Estado de revisión'],
                ),
                $this->seccionModulo(
                    id: 'codigo-repositorio',
                    menu: 'Código y repositorio',
                    titulo: 'Código y repositorio',
                    descripcion: 'Registra el repositorio, una URL HTTPS de demostración opcional y archivos técnicos o una aplicación para revisión.',
                    pasos: [
                        'Captura la URL del repositorio del equipo.',
                        'La URL de demostración no es obligatoria. Puedes adjuntar APK, EXE, MSI, DMG, AppImage o DEB; el sistema no ejecuta instaladores.',
                        'Indica la versión enviada. Si termina el plazo, puedes consultar la entrega y enviar otra solo cuando el líder abra una prórroga de Código para tu equipo.',
                    ],
                    imagen: 'assets/ayuda/actualizadas/estudiante-codigo-formulario.png',
                    ruta: $rutas['codigo-estudiante'] ?? null,
                    notas: ['Repositorio', 'URL opcional', 'Aplicación y archivos privados'],
                ),
                $this->seccionDocumento(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seccionAcceso(): array
    {
        return $this->seccionModulo(
            id: 'iniciar-sesion',
            menu: 'Iniciar sesión',
            titulo: 'Iniciar sesión',
            descripcion: 'La pantalla de acceso solicita matrícula y contraseña para abrir el panel asignado a tu cuenta.',
            pasos: [
                'Captura la matrícula institucional.',
                'Escribe tu contraseña y presiona Ingresar.',
                'La contraseña asignada al registrar tu cuenta permite ingresar directamente.',
            ],
            imagen: 'assets/ayuda/acceso/01-iniciar-sesion.png',
            ruta: route('login'),
            notas: ['Matrícula', 'Contraseña', 'Recuperación de acceso'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function seccionRecuperarContrasena(): array
    {
        return $this->seccionModulo(
            id: 'recuperar-contrasena',
            menu: 'Recuperar contraseña',
            titulo: 'Recuperar contraseña',
            descripcion: 'Solicita un código a tu correo registrado y completa el cambio de contraseña dentro del sistema.',
            pasos: [
                'Presiona ¿Olvidaste tu contraseña? desde el inicio de sesión.',
                'Escribe el correo registrado en tu cuenta.',
                'Presiona Enviar código, revisa tu correo y escribe el código de 8 dígitos en el sistema.',
                'Si el código es correcto, elige y confirma una nueva contraseña; después vuelve a iniciar sesión.',
                'El código vence a los 10 minutos. Para reenviar espera la cuenta regresiva de 3 minutos.',
            ],
            imagen: 'assets/ayuda/actualizadas/acceso-recuperacion.png',
            ruta: route('password.request'),
            notas: ['Correo registrado', 'Enviar código', 'Volver al inicio'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function seccionActualizarContrasena(): array
    {
        return $this->seccionModulo(
            id: 'actualizar-contrasena',
            menu: 'Actualizar contraseña',
            titulo: 'Actualizar contraseña',
            descripcion: 'Permite cambiar tu contraseña actual cuando lo necesites.',
            pasos: [
                'Escribe tu contraseña actual.',
                'Captura una nueva contraseña de al menos 8 caracteres.',
                'Confirma la nueva contraseña y presiona Actualizar contraseña.',
            ],
            imagen: 'assets/ayuda/acceso/03-actualizar-contrasena.png',
            ruta: null,
            notas: ['Contraseña actual', 'Nueva contraseña', 'Confirmación'],
        );
    }

    private function seccionFirma(array $rutas): array
    {
        return $this->seccionModulo(
            id: 'mi-firma',
            menu: 'Mi firma',
            titulo: 'Registrar mi firma',
            descripcion: 'Tu firma se guarda de forma privada y se incorpora al formato final solo cuando autorizas una aprobación concreta.',
            pasos: [
                'En Mi firma, sube una imagen PNG o JPG o dibuja la firma en el recuadro del sistema.',
                'Confirma el registro con tu contraseña. En la revisión, comprueba la versión recibida y autoriza la firma al aprobar.',
                'La autorización queda vinculada a esa entrega y contexto; una entrega nueva necesita otra revisión. Ninguna web puede impedir completamente una captura de pantalla.',
            ],
            imagen: 'assets/ayuda/actualizadas/docente-firma.png',
            ruta: $rutas['mi-firma'] ?? null,
            notas: ['Imagen o dibujo', 'Confirmación con contraseña', 'Firma por versión aprobada'],
        );
    }

    private function seccionDocumento(): array
    {
        return $this->seccionModulo(
            id: 'formato-pdf',
            menu: 'Formato y PDF',
            titulo: 'Formato, firmas y PDF final',
            descripcion: 'Cada proyecto muestra su formato y firmas de acuerdo con las aprobaciones vigentes.',
            pasos: [
                'Abre Ver formato y firmas desde tu proyecto o la pantalla de revisión correspondiente. Usa Volver para regresar a la lista y conservar los filtros.',
                'Si faltan entregas o firmas, revisa los motivos pendientes. El PDF final se genera cuando el proyecto cumple las condiciones.',
                'Descarga el PDF archivado para consultar exactamente el archivo emitido, aunque cambien después otros datos del sistema.',
            ],
            imagen: 'assets/ayuda/actualizadas/formato-firmas-pdf.png',
            ruta: null,
            notas: ['Aprobaciones vigentes', 'PDF archivado', 'Regreso a la lista'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function seccionCerrarSesion(): array
    {
        $capturas = $this->capturasDeSeccion('assets/ayuda/comun/01-botones-superiores.png', 'Cerrar sesión');

        return [
            'id' => 'cerrar-sesion',
            'menu' => 'Cerrar sesión',
            'titulo' => 'Cerrar sesión',
            'descripcion' => 'Usa esta opción al terminar para proteger tu cuenta, especialmente en equipos compartidos.',
            'pasos' => [
                'Presiona el botón Salir que aparece en la esquina superior derecha.',
                'Confirma que vuelvas a la pantalla de inicio de sesión.',
                'Evita dejar sesiones abiertas en computadoras de laboratorio o compartidas.',
            ],
            'imagen' => $capturas[0]['imagen'] ?? null,
            'caption' => $capturas[0]['caption'] ?? null,
            'ruta' => null,
            'rutaEtiqueta' => null,
            'notas' => [],
            'capturas' => $capturas,
            'marcas' => $capturas[0]['marcas'] ?? [],
            'resaltados' => $capturas[0]['resaltados'] ?? [],
            'detalles' => [
                $this->detalle('Botón Salir', 'Cierra la sesión actual y te regresa a la pantalla de inicio de sesión.'),
                $this->detalle('Cuándo usarlo', 'Úsalo al terminar de trabajar, especialmente si la computadora es compartida.'),
            ],
            'tipo' => 'logout',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seccionContacto(): array
    {
        $capturas = $this->capturasDeSeccion('assets/ayuda/actualizadas/ayuda-contactanos.png', 'Contáctanos');

        return [
            'id' => 'contactanos',
            'menu' => 'Contáctanos',
            'titulo' => 'Contáctanos',
            'descripcion' => 'Si el problema continúa, repórtalo con datos suficientes para que el responsable pueda reproducirlo.',
            'pasos' => [
                'Nombre completo y matrícula con la que ingresaste.',
                'Módulo, pantalla y acción que intentabas realizar.',
                'Captura de pantalla, mensaje de error y hora aproximada del incidente.',
            ],
            'imagen' => $capturas[0]['imagen'] ?? null,
            'caption' => $capturas[0]['caption'] ?? null,
            'ruta' => null,
            'rutaEtiqueta' => null,
            'notas' => [],
            'capturas' => $capturas,
            'marcas' => $capturas[0]['marcas'] ?? [],
            'resaltados' => $capturas[0]['resaltados'] ?? [],
            'detalles' => [
                $this->detalle('Datos necesarios', 'Incluye matrícula, pantalla, acción realizada y una captura del mensaje o comportamiento observado.'),
                $this->detalle('Cuándo reportar', 'Reporta cuando no puedas guardar, falten datos, no aparezca una opción esperada o veas información incorrecta.'),
            ],
            'tipo' => 'contacto',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seccionModulo(
        string $id,
        string $menu,
        string $titulo,
        string $descripcion,
        array $pasos,
        ?string $imagen,
        ?string $ruta,
        array $notas,
    ): array {
        $capturas = $imagen === null ? [] : $this->capturasDeSeccion($imagen, $titulo);

        return [
            'id' => $id,
            'menu' => $menu,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'pasos' => $pasos,
            'imagen' => $capturas[0]['imagen'] ?? null,
            'caption' => $capturas[0]['caption'] ?? null,
            'ruta' => $ruta,
            'rutaEtiqueta' => 'Abrir vista',
            'notas' => $notas,
            'capturas' => $capturas,
            'marcas' => $capturas[0]['marcas'] ?? [],
            'resaltados' => $capturas[0]['resaltados'] ?? [],
            'detalles' => $this->detallesPorCapturas($capturas),
            'tipo' => 'modulo',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function capturasDeSeccion(string $imagen, string $titulo): array
    {
        $capturas = [
            [
                'imagen' => $imagen,
                'titulo_captura' => 'Vista general',
                'caption' => 'Captura de referencia: '.$titulo.'.',
            ],
            ...$this->capturasAdicionales($imagen),
        ];

        return collect($capturas)
            ->map(function (array $captura, int $indice) use ($titulo): ?array {
                $ruta = $captura['imagen'];
                $imagen = $this->imagen($ruta);

                if ($imagen === null) {
                    return null;
                }

                return [
                    'id' => 'captura-'.$indice.'-'.md5($ruta),
                    'titulo' => $titulo,
                    'titulo_captura' => $captura['titulo_captura'] ?? 'Captura '.($indice + 1),
                    'imagen' => $imagen,
                    'ruta_original' => $ruta,
                    'caption' => $captura['caption'] ?? 'Captura de contexto: '.$titulo.'.',
                    'marcas' => $this->marcasPorImagen($ruta),
                    'resaltados' => $this->resaltadosPorImagen($ruta),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{imagen: string, titulo_captura: string, caption: string}>
     */
    private function capturasAdicionales(string $imagen): array
    {
        return [
            'assets/ayuda/actualizadas/acceso-recuperacion.png' => [
                $this->capturaContexto('assets/ayuda/actualizadas/acceso-codigo.png', 'Paso 2: verificar código', 'Pantalla del código de ocho dígitos y la espera de tres minutos para reenviarlo.'),
                $this->capturaContexto('assets/ayuda/actualizadas/acceso-nueva-contrasena.png', 'Paso 3: nueva contraseña', 'Pantalla para definir y confirmar la nueva contraseña antes de volver al inicio de sesión.'),
            ],
            'assets/ayuda/actualizadas/coordinacion-periodos.png' => [
                $this->capturaContexto('assets/ayuda/actualizadas/coordinacion-periodo-nuevo.png', 'Nuevo periodo en borrador', 'Formulario actual: el estado inicial es borrador y la activación se hace después desde el historial.'),
            ],
            'assets/ayuda/actualizadas/coordinacion-guias.png' => [
                $this->capturaContexto('assets/ayuda/actualizadas/coordinacion-guia-nueva.png', 'Crear guía en borrador', 'Formulario actual de alta: la guía inicia como borrador.'),
                $this->capturaContexto('assets/ayuda/actualizadas/coordinacion-guia-apartados.png', 'Apartados y vista previa', 'Formulario actual para editar un apartado y consultar los cambios antes de guardarlos.'),
            ],
            'assets/ayuda/actualizadas/lider-cierres.png' => [
                $this->capturaContexto('assets/ayuda/actualizadas/lider-prorroga.png', 'Decidir una prórroga', 'Detalle de un proyecto con pendientes y apartados abiertos por una prórroga.'),
            ],
            'assets/ayuda/actualizadas/lider-recordatorios.png' => [
                $this->capturaContexto('assets/ayuda/actualizadas/lider-recordatorios-formato-72.png', 'Formato de 72 horas', 'Texto editable del recordatorio automático de 72 horas.'),
                $this->capturaContexto('assets/ayuda/actualizadas/lider-recordatorios-formato-24.png', 'Formato de 24 horas', 'Texto editable del recordatorio automático de 24 horas.'),
                $this->capturaContexto('assets/ayuda/actualizadas/lider-recordatorios-formato-manual.png', 'Formato manual', 'Texto editable de los recordatorios enviados desde los botones del docente líder.'),
                $this->capturaContexto('assets/ayuda/actualizadas/lider-recordatorios-formato-revision.png', 'Formato para docentes', 'Texto editable de los avisos de revisión pendiente que reciben docentes de los equipos.'),
            ],
            'assets/ayuda/actualizadas/materia-revisiones.png' => [
                $this->capturaContexto('assets/ayuda/actualizadas/materia-revision-detalle.png', 'Revisar una versión', 'Detalle de evidencia, resultado y autorización de firma para la versión recibida.'),
            ],
            'assets/ayuda/actualizadas/estudiante-codigo-formulario.png' => [
                $this->capturaContexto('assets/ayuda/actualizadas/estudiante-codigo.png', 'Producto registrado', 'Ejemplo de una entrega de código con repositorio y archivos privados para descarga.'),
            ],
            'assets/ayuda/actualizadas/formato-firmas-pdf.png' => [
                $this->capturaContexto('assets/ayuda/actualizadas/formato-pdf-emitido.png', 'Documento emitido', 'El PDF archivado se descarga desde Documentos emitidos; Volver conserva el origen.'),
            ],
            'assets/ayuda/coordinacion/01-panel-principal.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/01-panel-principal-detalle.png', 'Acercamiento: indicadores y avisos', 'Captura de contexto: métricas, avisos y resumen del periodo.'),
            ],
            'assets/ayuda/coordinacion/02-jerarquia-proyectos.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/02-jerarquia-proyectos-detalle.png', 'Acercamiento: asignaciones por grupo', 'Captura de contexto: filtros, responsables, asignatura principal y botón de guardado.'),
            ],
            'assets/ayuda/coordinacion/03-periodos.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/03-periodos-detalle.png', 'Acercamiento: registro e historial', 'Captura de contexto: botón para crear periodo, tarjetas de estado e historial.'),
                $this->capturaContexto('assets/ayuda/coordinacion/03-periodos-formulario.png', 'Estado abierto: nuevo periodo', 'Captura de contexto: formulario que aparece al abrir Nuevo periodo.'),
            ],
            'assets/ayuda/coordinacion/04-carreras-grupos.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/04-carreras-grupos-detalle.png', 'Acercamiento: carreras, grupos y docentes', 'Captura de contexto: formularios para registrar estructura académica y responsables.'),
            ],
            'assets/ayuda/coordinacion/05-usuarios.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/05-usuarios-detalle.png', 'Acercamiento: alumnos y carga masiva', 'Captura de contexto: pestañas, filtros, plantilla y acciones de importación.'),
                $this->capturaContexto('assets/ayuda/coordinacion/05-usuarios-docentes-formulario.png', 'Estado abierto: registro de docentes', 'Captura de contexto: pestaña de docentes con filas de captura y botones de alta.'),
            ],
            'assets/ayuda/coordinacion/06-asignaturas.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/06-asignaturas-detalle.png', 'Acercamiento: catálogo y asignación', 'Captura de contexto: registro de asignaturas, asignación por periodo y carga operativa.'),
                $this->capturaContexto('assets/ayuda/coordinacion/06-asignaturas-formularios.png', 'Estado abierto: formularios de asignatura', 'Captura de contexto: paneles abiertos para crear asignatura y asignar docente.'),
            ],
            'assets/ayuda/coordinacion/07-guias.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/07-guias-detalle.png', 'Acercamiento: guía y apartados', 'Captura de contexto: alta de guía, apartados, ponderaciones y configuración.'),
                $this->capturaContexto('assets/ayuda/coordinacion/07-guias-formularios.png', 'Estado abierto: guía y apartados', 'Captura de contexto: paneles abiertos para crear guía y capturar apartados.'),
                $this->capturaContexto('assets/ayuda/coordinacion/07-guias-apartados-lista.png', 'Estado abierto: detalle de guía', 'Captura de contexto: detalle que aparece al abrir una guía publicada o en configuración.'),
            ],
            'assets/ayuda/docente-lider/01-panel-principal.png' => [
                $this->capturaContexto('assets/ayuda/docente-lider/01-panel-principal-detalle.png', 'Acercamiento: grupos de trabajo', 'Captura de contexto: indicadores y listado de grupos activos.'),
            ],
            'assets/ayuda/docente-lider/02-lista-alumnos.png' => [
                $this->capturaContexto('assets/ayuda/docente-lider/02-lista-alumnos-detalle.png', 'Acercamiento: filtros y alumnos', 'Captura de contexto: selector de grupo, búsqueda y tabla de alumnos.'),
            ],
            'assets/ayuda/docente-lider/03-equipos.png' => [
                $this->capturaContexto('assets/ayuda/docente-lider/03-equipos-detalle.png', 'Acercamiento: equipos por grupo', 'Captura de contexto: botón para crear equipo, selección de grupo y tabla de equipos.'),
                $this->capturaContexto('assets/ayuda/docente-lider/03-equipos-nuevo-equipo.png', 'Estado abierto: nuevo equipo', 'Captura de contexto: formulario que aparece al abrir Nuevo equipo.'),
                $this->capturaContexto('assets/ayuda/docente-lider/03-equipos-integrantes.png', 'Estado abierto: integrantes del equipo', 'Captura de contexto: panel que aparece al abrir Ver información en un equipo.'),
            ],
            'assets/ayuda/docente-lider/04-proyectos.png' => [
                $this->capturaContexto('assets/ayuda/docente-lider/04-proyectos-detalle.png', 'Acercamiento: proyectos y acciones', 'Captura de contexto: filtros, proyectos por equipo, estado y acciones de administración.'),
                $this->capturaContexto('assets/ayuda/docente-lider/04-proyectos-administrar.png', 'Estado abierto: administrar proyecto', 'Captura de contexto: panel que aparece al abrir Administrar en un proyecto.'),
            ],
            'assets/ayuda/docente-lider/05-revision-codigo.png' => [
                $this->capturaContexto('assets/ayuda/docente-lider/05-revision-codigo-detalle.png', 'Acercamiento: bandeja técnica', 'Captura de contexto: periodo visible y zona donde aparecen productos de código.'),
            ],
            'assets/ayuda/docente-materia/01-panel-principal.png' => [
                $this->capturaContexto('assets/ayuda/docente-materia/01-panel-principal-detalle.png', 'Acercamiento: pendientes y avisos', 'Captura de contexto: indicadores, entregas por revisar y alertas de atención.'),
            ],
            'assets/ayuda/docente-materia/02-mis-asignaciones.png' => [
                $this->capturaContexto('assets/ayuda/docente-materia/02-mis-asignaciones-detalle.png', 'Acercamiento: apartados asignados', 'Captura de contexto: periodo, materias, ponderación y apartados por revisar.'),
            ],
            'assets/ayuda/docente-materia/03-revisiones.png' => [
                $this->capturaContexto('assets/ayuda/docente-materia/03-revisiones-detalle.png', 'Acercamiento: filtros y bandeja', 'Captura de contexto: búsqueda, filtros, apartado seleccionado y entregas disponibles.'),
                $this->capturaContexto('assets/ayuda/docente-materia/03-revisiones-entrega-abierta.png', 'Estado abierto: entrega recibida', 'Captura de contexto: entrega desplegada desde la bandeja de revisión.'),
                $this->capturaContexto('assets/ayuda/docente-materia/03-revisiones-entrega-formulario.png', 'Estado abierto: formulario de revisión', 'Captura de contexto: evidencias, resultado, calificación, observaciones y comentarios.'),
            ],
            'assets/ayuda/estudiante/01-panel-principal.png' => [
                $this->capturaContexto('assets/ayuda/estudiante/01-panel-principal-detalle.png', 'Acercamiento: progreso y guía', 'Captura de contexto: porcentaje validado, estados y resumen de apartados.'),
            ],
            'assets/ayuda/estudiante/02-mi-proyecto.png' => [
                $this->capturaContexto('assets/ayuda/estudiante/02-mi-proyecto-detalle.png', 'Acercamiento: datos del proyecto', 'Captura de contexto: equipo, asesor, guía e integrantes registrados.'),
            ],
            'assets/ayuda/estudiante/03-entregas.png' => [
                $this->capturaContexto('assets/ayuda/estudiante/03-entregas-detalle.png', 'Acercamiento: apartados y estados', 'Captura de contexto: lista de apartados, fecha límite, último envío y estado.'),
                $this->capturaContexto('assets/ayuda/estudiante/03-entregas-detalle-apartado.png', 'Estado abierto: detalle de apartado', 'Captura de contexto: información que aparece al abrir un apartado de entrega.'),
            ],
            'assets/ayuda/estudiante/04-codigo-repositorio.png' => [
                $this->capturaContexto('assets/ayuda/estudiante/04-codigo-repositorio-detalle.png', 'Acercamiento: producto final', 'Captura de contexto: aviso, repositorio y archivos técnicos del producto.'),
            ],
        ][$imagen] ?? [];
    }

    /**
     * @return array{imagen: string, titulo_captura: string, caption: string}
     */
    private function capturaContexto(string $imagen, string $tituloCaptura, string $caption): array
    {
        return [
            'imagen' => $imagen,
            'titulo_captura' => $tituloCaptura,
            'caption' => $caption,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $capturas
     * @return array<int, array{titulo: string, texto: string}>
     */
    private function detallesPorCapturas(array $capturas): array
    {
        return collect($capturas)
            ->flatMap(fn (array $captura): array => $this->detallesPorImagen($captura['ruta_original']))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{titulo: string, texto: string}>
     */
    private function detallesPorImagen(string $imagen): array
    {
        $detalles = [
            'assets/ayuda/acceso/01-iniciar-sesion.png' => [
                $this->detalle('Matrícula', 'Captura la matrícula tal como fue registrada, sin espacios adicionales.'),
                $this->detalle('Contraseña', 'Escribe la contraseña asignada a tu cuenta o la que hayas elegido.'),
                $this->detalle('Iniciar sesión', 'Valida los datos y abre el panel asignado a tu cuenta.'),
                $this->detalle('Olvidaste tu contraseña', 'Usa ese enlace cuando no recuerdes tu clave o no puedas entrar.'),
                $this->detalle('Ayuda', 'Abre esta guía de acceso en otra ventana sin cerrar la pantalla actual.'),
            ],
            'assets/ayuda/acceso/02-recuperar-contrasena.png' => [
                $this->detalle('Correo registrado', 'Escribe el correo asociado a tu cuenta para recibir un código de 8 dígitos.'),
                $this->detalle('Enviar código', 'El código dura 10 minutos. Si necesitas otro, espera 3 minutos para usar Reenviar código.'),
                $this->detalle('Volver al inicio', 'Regresa a la pantalla de acceso cuando ya tengas una clave válida.'),
                $this->detalle('Ayuda', 'Abre la explicación de recuperación de acceso en otra ventana.'),
            ],
            'assets/ayuda/acceso/03-actualizar-contrasena.png' => [
                $this->detalle('Contraseña actual', 'Escribe la contraseña que utilizas para entrar al sistema.'),
                $this->detalle('Nueva contraseña', 'Debe tener al menos 8 caracteres y ser diferente a la actual.'),
                $this->detalle('Confirmar nueva contraseña', 'Repite la nueva contraseña exactamente igual para evitar errores de escritura.'),
                $this->detalle('Actualizar contraseña', 'Guarda el cambio y permite continuar al sistema cuando los datos son válidos.'),
                $this->detalle('Ayuda', 'Abre esta guía en otra ventana si tienes duda sobre los campos.'),
            ],
            'assets/ayuda/coordinacion/01-panel-principal.png' => [
                $this->detalle('Tarjetas de resumen', 'Muestran cantidades actuales de carreras, grupos, alumnos y guías para detectar capturas pendientes.'),
                $this->detalle('Atención operativa', 'Indica pendientes como grupos sin responsable, equipos sin proyecto o guías sin completar.'),
                $this->detalle('Grupos del periodo', 'Sirve para verificar rápidamente carrera, grupo, responsable asignado y cantidad de equipos.'),
                $this->detalle('Botón Ayuda', 'Abre la lista de temas para consultar la explicación de cada pantalla.'),
            ],
            'assets/ayuda/coordinacion/02-jerarquia-proyectos.png' => [
                $this->detalle('Filtro de periodo', 'Úsalo para trabajar con el ciclo correcto antes de modificar asignaciones.'),
                $this->detalle('Responsable del grupo', 'Selecciona la persona que dará seguimiento al grupo durante el periodo.'),
                $this->detalle('Asignatura principal', 'Selecciona la materia que organizará el proyecto integrador del grupo.'),
                $this->detalle('Guardar asignación', 'Guarda los cambios solo de la fila donde se presiona el botón.'),
            ],
            'assets/ayuda/coordinacion/03-periodos.png' => [
                $this->detalle('Nuevo periodo', 'Abre el formulario para registrar un nuevo ciclo de trabajo.'),
                $this->detalle('Nombre del periodo', 'Usa un nombre claro, por ejemplo Mayo - Agosto 2026.'),
                $this->detalle('Fechas', 'La fecha inicial y final definen el rango de trabajo para guías y entregas.'),
                $this->detalle('Estado', 'El periodo nuevo se guarda en borrador. Solo se activa cuando no hay otro activo; al cerrarlo se revisan pendientes y queda para consulta.'),
            ],
            'assets/ayuda/coordinacion/03-periodos-formulario.png' => [
                $this->detalle('Panel desplegado', 'Se abre al presionar Nuevo periodo y muestra los campos necesarios para crear el ciclo.'),
                $this->detalle('Guardar periodo', 'Valida nombre y fechas y lo agrega al historial como borrador.'),
            ],
            'assets/ayuda/coordinacion/04-carreras-grupos.png' => [
                $this->detalle('Nueva carrera', 'Captura nombre y clave de la carrera antes de crear grupos.'),
                $this->detalle('Nuevo grupo', 'Selecciona carrera, periodo y grado. El grupo debe ser una sola letra de la A a la L.'),
                $this->detalle('Agregar docente', 'Relaciona participantes académicos con una carrera para que aparezcan en asignaciones posteriores.'),
                $this->detalle('Filtros', 'Permiten revisar solo una carrera, un periodo o un grupo específico.'),
            ],
            'assets/ayuda/coordinacion/05-usuarios.png' => [
                $this->detalle('Pestañas', 'Cambia entre consulta de alumnos y registro de personal académico.'),
                $this->detalle('Matrícula', 'Debe ser única; si ya existe, el sistema no permitirá duplicarla.'),
                $this->detalle('Correo', 'Sirve para avisos y recuperación de acceso, por eso debe estar bien escrito.'),
                $this->detalle('Descargar plantilla de alumnos', 'Descarga el archivo de Excel, llena cédula o matrícula, nómina o nombre y correo en una fila por alumno. Después selecciona periodo, carrera, grado y grupo para cada archivo y revisa la vista previa antes de confirmar.'),
            ],
            'assets/ayuda/coordinacion/05-usuarios-docentes-formulario.png' => [
                $this->detalle('Registro de docentes', 'Usa esta pestaña para capturar una o varias cuentas académicas en una sola operación.'),
                $this->detalle('Agregar docente', 'Inserta otra fila vacía cuando necesitas registrar más de una persona.'),
                $this->detalle('Guardar docentes', 'Guarda todas las filas completas; revisa matrícula, correo, tipo de cuenta y carrera antes de enviar.'),
            ],
            'assets/ayuda/coordinacion/06-asignaturas.png' => [
                $this->detalle('Registrar asignatura', 'Captura nombre, clave, carrera y grado para agregarla al catálogo.'),
                $this->detalle('Asignar docente', 'Relaciona una persona con la asignatura y el periodo visible.'),
                $this->detalle('Cambiar ciclo', 'Actualiza la carga mostrada cuando necesitas consultar otro periodo.'),
                $this->detalle('Tabla de carga', 'Muestra materias y responsables ya registrados para confirmar que no falte información.'),
            ],
            'assets/ayuda/coordinacion/06-asignaturas-formularios.png' => [
                $this->detalle('Catálogo permanente', 'El formulario de la izquierda crea la materia base por carrera y cuatrimestre.'),
                $this->detalle('Asignación por periodo', 'El formulario de la derecha indica quién atenderá esa materia solo durante el periodo seleccionado.'),
            ],
            'assets/ayuda/coordinacion/07-guias.png' => [
                $this->detalle('Crear guía', 'Registra la guía base para la carrera y periodo seleccionados.'),
                $this->detalle('Apartado', 'Captura título, orden, ponderación, fecha límite y tipo de evidencia requerida.'),
                $this->detalle('Calificadores', 'Asigna quienes revisarán cada apartado antes de publicarlo.'),
                $this->detalle('Publicación', 'Usa la vista previa y publica la guía cuando los apartados tengan fechas, ponderación y responsables de revisión.'),
            ],
            'assets/ayuda/coordinacion/07-guias-formularios.png' => [
                $this->detalle('Crear nueva guía', 'Completa periodo, asignatura principal, nombre, cuatrimestre, versión, competencias y objetivo. La guía empieza en borrador.'),
                $this->detalle('Nuevo apartado', 'Agrega cada entrega esperada con orden, porcentaje, fecha límite y si requiere documento o código.'),
            ],
            'assets/ayuda/coordinacion/07-guias-apartados-lista.png' => [
                $this->detalle('Detalle desplegado', 'Al abrir una guía se muestran sus competencias, objetivo y apartados configurados.'),
                $this->detalle('Docentes que califican', 'En cada apartado puedes asignar o quitar quién revisará esa evidencia.'),
            ],
            'assets/ayuda/docente-lider/01-panel-principal.png' => [
                $this->detalle('Indicadores', 'Resumen de grupos, alumnos, equipos y proyectos bajo seguimiento.'),
                $this->detalle('Grupos del periodo', 'Lista los grupos disponibles y permite confirmar si ya tienen equipos y proyectos.'),
                $this->detalle('Accesos del menú', 'Usa Lista de alumnos, Equipos, Proyectos, Estado de las guías, Cierres y prórrogas y Revisión de código.'),
            ],
            'assets/ayuda/docente-lider/02-lista-alumnos.png' => [
                $this->detalle('Grupo activo', 'Selecciona el grupo que quieres revisar para evitar consultar alumnos de otro grupo.'),
                $this->detalle('Búsqueda', 'Puedes buscar por nombre, matrícula o correo cuando la lista es larga.'),
                $this->detalle('Datos del alumno', 'Verifica matrícula, nombre, correo, carrera y grupo antes de formar equipos.'),
                $this->detalle('Alumno faltante', 'Si no aparece alguien, reporta la matrícula y grupo al responsable del sistema.'),
            ],
            'assets/ayuda/docente-lider/03-equipos.png' => [
                $this->detalle('Nuevo equipo', 'Crea un equipo dentro del grupo seleccionado.'),
                $this->detalle('Integrantes', 'Agrega alumnos disponibles y evita dejar alumnos duplicados en dos equipos.'),
                $this->detalle('Responsable del equipo', 'Selecciona quién coordinará al equipo cuando la vista lo solicite.'),
                $this->detalle('Ver información', 'Abre el detalle del equipo para revisar integrantes, asesores y proyecto relacionado.'),
            ],
            'assets/ayuda/docente-lider/03-equipos-nuevo-equipo.png' => [
                $this->detalle('Grupo del equipo', 'El equipo se crea dentro del grupo seleccionado; los alumnos disponibles se toman de ese mismo grupo.'),
                $this->detalle('Alumnos libres', 'Si no hay alumnos libres, el sistema evita crear otro equipo hasta liberar o cargar alumnos.'),
            ],
            'assets/ayuda/docente-lider/03-equipos-integrantes.png' => [
                $this->detalle('Alumnos del mismo grupo', 'Solo aparecen integrantes del grupo al que pertenece el equipo; no se mezclan alumnos de otros grupos.'),
                $this->detalle('Retirar', 'Quita al alumno del equipo y lo deja disponible para reasignarlo cuando corresponda.'),
                $this->detalle('Proyecto relacionado', 'El panel también muestra el proyecto vinculado al equipo para confirmar que la información coincide.'),
            ],
            'assets/ayuda/docente-lider/04-proyectos.png' => [
                $this->detalle('Filtro de grupo', 'Muestra solamente los proyectos del grupo seleccionado.'),
                $this->detalle('Proyecto por equipo', 'Revisa nombre, equipo, guía, periodo y estado del proyecto.'),
                $this->detalle('Administrar', 'Abre acciones para completar asesores, materias participantes o datos faltantes.'),
                $this->detalle('Estado', 'Sirve para saber si el proyecto está pendiente, en proceso o ya configurado.'),
            ],
            'assets/ayuda/docente-lider/04-proyectos-administrar.png' => [
                $this->detalle('Docentes participantes', 'Agrega o retira personas vinculadas al proyecto y define si participan como asesor, evaluador o ambos.'),
                $this->detalle('Asignaturas participantes', 'Relaciona materias del mismo programa y cuatrimestre con el proyecto para dar seguimiento académico.'),
            ],
            'assets/ayuda/docente-lider/05-revision-codigo.png' => [
                $this->detalle('Periodo visible', 'Confirma que estás revisando el periodo correcto.'),
                $this->detalle('Bandeja de revisión', 'Aquí aparecen repositorios, archivos técnicos y productos enviados por equipos.'),
                $this->detalle('Archivos y repositorio', 'Abre el repositorio, la URL de demostración opcional o los archivos privados antes de evaluar.'),
                $this->detalle('Sin registros', 'Si no aparecen entregas, puede que ningún equipo haya registrado producto de código.'),
            ],
            'assets/ayuda/docente-materia/01-panel-principal.png' => [
                $this->detalle('Entregas recibidas', 'Indica cuántas evidencias llegaron para revisión.'),
                $this->detalle('Pendientes por revisar', 'Muestra entregas que requieren atención. Atiende primero las vencidas o recibidas sin resultado.'),
                $this->detalle('Próximos apartados', 'Ayuda a preparar revisiones antes de que lleguen nuevas entregas.'),
            ],
            'assets/ayuda/docente-materia/02-mis-asignaciones.png' => [
                $this->detalle('Periodo', 'Confirma que el periodo visible coincida con el ciclo que estás revisando.'),
                $this->detalle('Apartados asignados', 'Muestra título, orden, ponderación y fecha límite de cada apartado.'),
                $this->detalle('Materia', 'Indica con qué materia o etiqueta participa la revisión.'),
                $this->detalle('Sin apartados', 'Si aparece vacío, todavía no hay apartados asignados para tu cuenta.'),
            ],
            'assets/ayuda/docente-materia/03-revisiones.png' => [
                $this->detalle('Filtros', 'Busca por equipo, proyecto, apartado, grupo o estado para encontrar entregas específicas.'),
                $this->detalle('Entrega disponible', 'Abre la evidencia antes de registrar resultado.'),
                $this->detalle('Resultado', 'Usa Aprobada cuando cumple, Corrección cuando necesita ajustes y Rechazada cuando no corresponde.'),
                $this->detalle('Observaciones', 'Escribe comentarios claros para que el equipo sepa qué corregir.'),
            ],
            'assets/ayuda/docente-materia/03-revisiones-entrega-abierta.png' => [
                $this->detalle('Apartado seleccionado', 'La bandeja muestra las entregas del apartado activo para revisar una a una.'),
                $this->detalle('Entrega recibida', 'Cada fila indica equipo, proyecto, fecha de envío, persona que entregó y estado actual.'),
                $this->detalle('Abrir detalle', 'Al presionar la fila se despliegan evidencias, criterio y formulario de evaluación.'),
            ],
            'assets/ayuda/docente-materia/03-revisiones-entrega-formulario.png' => [
                $this->detalle('Evidencias entregadas', 'Abre o descarga los archivos antes de calificar.'),
                $this->detalle('Criterio asignado', 'Confirma apartado, ponderación y versión recibida para evaluar lo correcto.'),
                $this->detalle('Resultado y calificación', 'Selecciona el resultado, escribe la calificación y guarda la revisión.'),
                $this->detalle('Comentarios de seguimiento', 'Después de guardar la revisión puedes agregar indicaciones adicionales para el equipo.'),
            ],
            'assets/ayuda/estudiante/01-panel-principal.png' => [
                $this->detalle('Progreso validado', 'Muestra el porcentaje de apartados aprobados del equipo.'),
                $this->detalle('Estados', 'Distingue sin entregar, pendiente, corrección, rechazado o validado.'),
                $this->detalle('Resumen de guía', 'Indica qué apartado sigue y qué fecha límite debes cuidar.'),
            ],
            'assets/ayuda/estudiante/02-mi-proyecto.png' => [
                $this->detalle('Datos del proyecto', 'Confirma nombre del proyecto, equipo, estado y periodo.'),
                $this->detalle('Guía integradora', 'Indica la guía que deberá seguir tu equipo.'),
                $this->detalle('Integrantes', 'Revisa que todos los integrantes aparezcan correctamente.'),
                $this->detalle('Datos incorrectos', 'Si algo no coincide, reporta la captura con el responsable de seguimiento.'),
            ],
            'assets/ayuda/estudiante/03-entregas.png' => [
                $this->detalle('Apartado', 'Selecciona el apartado que corresponde a la evidencia que vas a entregar.'),
                $this->detalle('Fecha límite', 'Revisa la fecha antes de cargar archivos para evitar entregas fuera de tiempo.'),
                $this->detalle('Estado', 'Sin entrega significa que falta enviar evidencia; pendiente significa que ya fue enviada y espera revisión.'),
                $this->detalle('Comentarios', 'Cuando exista revisión, lee las observaciones antes de enviar una nueva versión.'),
            ],
            'assets/ayuda/estudiante/03-entregas-detalle-apartado.png' => [
                $this->detalle('Detalle del apartado', 'Al abrir un apartado se muestra si puedes enviar archivos, consultar historial o atender una observación.'),
                $this->detalle('Aviso de plazo', 'Si el plazo termina, la evidencia queda para consulta. Solo podrás enviar otra versión si el líder abre prórroga para este equipo y apartado.'),
            ],
            'assets/ayuda/estudiante/04-codigo-repositorio.png' => [
                $this->detalle('Repositorio', 'Captura la URL completa del repositorio del equipo.'),
                $this->detalle('Versión', 'Indica la versión o avance que estás enviando para identificar la entrega.'),
                $this->detalle('URL y archivos', 'La demostración HTTPS es opcional. Puedes adjuntar comprimidos, documentación o una aplicación móvil o de escritorio para descargar.'),
                $this->detalle('Aviso rojo', 'Si aparece una advertencia, atiéndela antes de la fecha límite.'),
            ],
        ];

        return $detalles[$imagen] ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function marcasPorImagen(string $imagen): array
    {
        $marcas = [
            'assets/ayuda/acceso/01-iniciar-sesion.png' => [
                $this->marca('Ayuda', 'Abre esta guía sin iniciar sesión.', 76, 17, 71, 17, 65, 16, '0%', '-50%', '8.5rem', 'green'),
                $this->marca('Matrícula', 'Campo para tu matrícula.', 57, 49, 66, 49, 80, 52, '0%', '-50%', '8.5rem', 'blue'),
                $this->marca('Contraseña', 'Clave de acceso.', 96, 59, 91, 59, 80, 61, '-100%', '-50%', '8rem', 'amber'),
                $this->marca('Ingresar', 'Abre tu panel.', 96, 78, 91, 78, 80, 70, '-100%', '-50%', '8rem', 'green'),
            ],
            'assets/ayuda/acceso/02-recuperar-contrasena.png' => [
                $this->marca('Ayuda', 'Consulta esta guía sin salir del formulario.', 16, 24, 28, 24, 39, 24, '0%', '-50%', '8.5rem', 'green'),
                $this->marca('Correo', 'Usa el correo registrado para solicitar recuperación.', 22, 60, 32, 60, 50, 61, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Enviar', 'Solicita instrucciones de recuperación.', 80, 66, 70, 67, 50, 69, '-100%', '-50%', '9rem', 'green'),
                $this->marca('Volver', 'Regresa al inicio de sesión.', 82, 86, 72, 82, 50, 77, '-100%', '-50%', '8rem', 'amber'),
            ],
            'assets/ayuda/acceso/03-actualizar-contrasena.png' => [
                $this->marca('Ayuda', 'Abre esta guía en otra ventana.', 16, 15, 28, 15, 39, 15, '0%', '-50%', '8.5rem', 'green'),
                $this->marca('Temporal', 'Escribe la contraseña que recibiste.', 22, 52, 32, 52, 50, 52, '0%', '-50%', '9rem', 'blue'),
                $this->marca('Nueva', 'Captura una contraseña segura.', 80, 64, 70, 64, 50, 64, '-100%', '-50%', '8.5rem', 'amber'),
                $this->marca('Confirmar', 'Repite la nueva contraseña.', 22, 77, 32, 77, 50, 77, '0%', '-50%', '9rem', 'blue'),
                $this->marca('Actualizar', 'Guarda el cambio para continuar.', 80, 85, 70, 85, 50, 85, '-100%', '-50%', '8.5rem', 'green'),
            ],
            'assets/ayuda/coordinacion/01-panel-principal.png' => [
                $this->marca('Menú principal', 'Estas son las opciones disponibles para tu cuenta.', 2, 26, 12, 26, 9, 25, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Botón ayuda', 'Desde aquí abres esta guía por apartado.', 86, 15, 88, 14, 88.5, 4.5, '-50%', '0%', '9.5rem', 'green'),
                $this->marca('Alertas', 'Revisa estos avisos antes de operar el periodo.', 82, 56, 76, 56, 73, 58, '-100%', '-50%', '10.5rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/02-jerarquia-proyectos.png' => [
                $this->marca('Filtros', 'Ubica periodo, carrera o grupo antes de asignar.', 24, 25, 34, 25, 36, 28, '0%', '-50%', '10rem', 'amber'),
                $this->marca('Asignación', 'Selecciona responsable y materia principal por grupo.', 55, 27, 57, 31, 63, 39, '-50%', '-50%', '10.5rem', 'blue'),
                $this->marca('Guardar', 'Cada fila se guarda con su botón de asignación.', 94, 44, 89, 44, 91, 39, '-100%', '-50%', '9.5rem', 'green'),
            ],
            'assets/ayuda/coordinacion/03-periodos.png' => [
                $this->marca('Resumen', 'Estas tarjetas muestran el estado actual de periodos.', 25, 35, 34, 35, 37, 38, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Nuevo periodo', 'Usa este botón para registrar un ciclo nuevo.', 93, 46, 89, 46, 91, 53, '-100%', '-50%', '10rem', 'green'),
                $this->marca('Historial', 'Desde cada fila puedes abrir su estructura.', 82, 78, 78, 78, 91, 77, '-100%', '-50%', '9.5rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/03-periodos-formulario.png' => [
                $this->marca('Nuevo periodo', 'El botón despliega el formulario de captura.', 91, 42, 87, 42, 90, 44, '-100%', '-50%', '9.5rem', 'green'),
                $this->marca('Datos basicos', 'Captura nombre, inicio y cierre del ciclo.', 26, 52, 35, 55, 43, 60, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Estado', 'Define si queda en borrador, activo o cerrado.', 26, 91, 35, 83, 30, 69, '0%', '-100%', '9rem', 'amber'),
                $this->marca('Guardar', 'Registra el periodo con los datos completos.', 66, 76, 57, 76, 46, 73, '-50%', '-50%', '9rem', 'green'),
            ],
            'assets/ayuda/coordinacion/04-carreras-grupos.png' => [
                $this->marca('Nueva carrera', 'Registra primero las carreras disponibles.', 26, 63, 34, 63, 31, 72, '0%', '-50%', '10rem', 'green'),
                $this->marca('Nuevo grupo', 'Después crea grupos asociados al periodo.', 58, 63, 60, 63, 55, 72, '-50%', '-50%', '10rem', 'blue'),
                $this->marca('Responsables', 'Asocia participantes académicos por carrera.', 92, 63, 86, 63, 84, 72, '-100%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/05-usuarios.png' => [
                $this->marca('Tipo de cuenta', 'Cambia entre listas y registros desde estas pestañas.', 25, 37, 34, 37, 29, 42, '0%', '-50%', '10.5rem', 'blue'),
                $this->marca('Filtros', 'Ajusta periodo, carrera o grupo para encontrar registros.', 74, 45, 69, 45, 60, 50, '-100%', '-50%', '10rem', 'amber'),
                $this->marca('Acciones', 'Agrega listas o guarda la vista previa desde estos botones.', 28, 92, 38, 92, 27, 90, '0%', '-100%', '11rem', 'green'),
            ],
            'assets/ayuda/coordinacion/05-usuarios-docentes-formulario.png' => [
                $this->marca('Docentes', 'Esta pestaña cambia al formulario de docentes.', 82, 35, 76, 35, 76, 36, '-100%', '-50%', '9.5rem', 'blue'),
                $this->marca('Filas', 'Cada fila corresponde a una cuenta por registrar.', 30, 62, 38, 62, 49, 61, '0%', '-50%', '10rem', 'amber'),
                $this->marca('Tipo y carrera', 'Selecciona tipo de cuenta y carrera antes de guardar.', 91, 61, 84, 61, 78, 61, '-100%', '-50%', '10rem', 'green'),
                $this->marca('Agregar', 'Anade otra fila si necesitas capturar más personas.', 22, 94, 31, 86, 28, 78, '0%', '-100%', '9.5rem', 'green'),
            ],
            'assets/ayuda/coordinacion/06-asignaturas.png' => [
                $this->marca('Registrar', 'Abre el formulario para crear una asignatura.', 31, 37, 39, 37, 36, 43, '0%', '-50%', '10rem', 'green'),
                $this->marca('Asignar docente', 'Vincula docentes a las materias del periodo.', 62, 37, 63, 37, 63, 43, '-50%', '-50%', '10rem', 'blue'),
                $this->marca('Cambiar ciclo', 'Confirma el periodo antes de revisar la carga.', 93, 52, 88, 52, 90, 53, '-100%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/06-asignaturas-formularios.png' => [
                $this->marca('Catálogo', 'Crea la materia base con carrera, cuatrimestre, nombre y clave.', 25, 59, 35, 59, 33, 60, '0%', '-50%', '11rem', 'blue'),
                $this->marca('Guardar materia', 'Agrega la asignatura al catálogo permanente.', 58, 72, 56, 72, 51, 72, '-50%', '-50%', '10rem', 'green'),
                $this->marca('Asignación', 'Vincula materia, periodo y docente para el ciclo actual.', 91, 61, 85, 61, 79, 61, '-100%', '-50%', '11rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/07-guias.png' => [
                $this->marca('Crear guía', 'Guarda los datos principales antes de agregar apartados.', 27, 46, 36, 46, 29, 52, '0%', '-50%', '10.5rem', 'green'),
                $this->marca('Apartados', 'Configura orden, fecha límite y ponderación.', 59, 36, 60, 40, 58, 45, '-50%', '-50%', '10rem', 'blue'),
                $this->marca('Docentes', 'Asigna calificadores a cada apartado publicado.', 92, 39, 86, 39, 86, 44, '-100%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/07-guias-formularios.png' => [
                $this->marca('Guía', 'Completa periodo, asignatura, nombre y versión.', 25, 57, 35, 57, 35, 60, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Apartado', 'Define orden, porcentaje, título y evidencia requerida.', 91, 47, 84, 50, 80, 58, '-100%', '-50%', '10rem', 'green'),
                $this->marca('Tipo evidencia', 'Marca Documento o Código segun lo que deberan entregar.', 91, 82, 84, 78, 78, 76, '-100%', '-100%', '10rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/07-guias-apartados-lista.png' => [
                $this->marca('Detalle', 'Abrir la guía muestra objetivo, competencias y apartados.', 33, 32, 42, 32, 39, 35, '0%', '-50%', '10.5rem', 'blue'),
                $this->marca('Apartados', 'Cada fila indica orden, fecha, ponderación y descripción.', 31, 63, 40, 63, 41, 63, '0%', '-50%', '10rem', 'amber'),
                $this->marca('Asignar', 'Usa este botón para sumar docentes que califican.', 92, 64, 86, 64, 88, 64, '-100%', '-50%', '9.5rem', 'green'),
            ],
            'assets/ayuda/docente-lider/01-panel-principal.png' => [
                $this->marca('Indicadores', 'Resume equipos, alumnos, grupos y proyectos.', 28, 36, 36, 36, 39, 39, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Grupos', 'Consulta los grupos del periodo bajo tu alcance.', 79, 69, 74, 69, 74, 78, '-100%', '-50%', '10rem', 'amber'),
                $this->marca('Ayuda', 'Este botón abre las guías disponibles para tu cuenta.', 86, 15, 88, 14, 88.5, 4.5, '-50%', '0%', '10rem', 'green'),
            ],
            'assets/ayuda/docente-lider/02-lista-alumnos.png' => [
                $this->marca('Grupo activo', 'Selecciona el grupo antes de revisar alumnos.', 32, 45, 39, 45, 33, 50, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Búsqueda', 'Usa filtros para ubicar matrícula o nombre.', 92, 29, 86, 32, 85, 42, '-100%', '-50%', '9.5rem', 'amber'),
                $this->marca('Lista', 'Valida grupo, correo y datos del alumno.', 92, 67, 84, 67, 75, 65, '-100%', '-50%', '9.5rem', 'green'),
            ],
            'assets/ayuda/docente-lider/03-equipos.png' => [
                $this->marca('Nuevo equipo', 'Crea equipos cuando el grupo ya tenga alumnos.', 93, 43, 88, 43, 92, 45, '-100%', '-50%', '10rem', 'green'),
                $this->marca('Grupos', 'Cambia de grupo con estos accesos rápidos.', 43, 57, 49, 57, 43, 62, '-50%', '-50%', '10rem', 'blue'),
                $this->marca('Detalle', 'Abre la información del equipo desde esta acción.', 92, 82, 86, 82, 87, 86, '-100%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/docente-lider/03-equipos-nuevo-equipo.png' => [
                $this->marca('Nuevo equipo', 'Al abrirlo aparece este formulario.', 92, 44, 87, 44, 91, 43, '-100%', '-50%', '9rem', 'green'),
                $this->marca('Grupo', 'El equipo se crea dentro del grupo seleccionado.', 28, 55, 36, 55, 42, 55, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Alumnos libres', 'Si no hay alumnos libres, no se puede crear otro equipo.', 33, 78, 42, 78, 59, 78, '0%', '-50%', '11rem', 'amber'),
            ],
            'assets/ayuda/docente-lider/03-equipos-integrantes.png' => [
                $this->marca('Panel abierto', 'Se abre desde Ver información del equipo.', 88, 17, 82, 17, 86, 21, '-100%', '-50%', '9.5rem', 'blue'),
                $this->marca('Mismo grupo', 'Solo se asignan alumnos del grupo de este equipo.', 30, 53, 39, 53, 42, 51, '0%', '-50%', '10.5rem', 'green'),
                $this->marca('Retirar', 'Quita al alumno y lo deja disponible para reasignar.', 92, 62, 86, 62, 91, 62, '-100%', '-50%', '10rem', 'rose'),
            ],
            'assets/ayuda/docente-lider/04-proyectos.png' => [
                $this->marca('Grupo', 'Filtra los proyectos por grupo asignado.', 39, 49, 46, 49, 42, 53, '-50%', '-50%', '9.5rem', 'blue'),
                $this->marca('Proyecto', 'Revisa equipo, guía, asesor y estado.', 31, 76, 40, 76, 32, 78, '0%', '-50%', '10rem', 'amber'),
                $this->marca('Administrar', 'Entra a las acciones del proyecto desde aquí.', 92, 75, 86, 75, 88, 77, '-100%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/docente-lider/04-proyectos-administrar.png' => [
                $this->marca('Administrar', 'El panel cambia a Cerrar cuando está abierto.', 90, 17, 85, 17, 86, 20, '-100%', '-50%', '9rem', 'blue'),
                $this->marca('Docentes', 'Agrega o retira participantes del proyecto.', 31, 48, 40, 48, 39, 48, '0%', '-50%', '10rem', 'green'),
                $this->marca('Asignaturas', 'Vincula materias del mismo programa y cuatrimestre.', 91, 51, 83, 51, 76, 51, '-100%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/docente-lider/05-revision-codigo.png' => [
                $this->marca('Periodo', 'Confirma el periodo visible antes de revisar.', 92, 27, 87, 27, 88, 26, '-100%', '-50%', '9.5rem', 'amber'),
                $this->marca('Bandeja', 'Aquí aparecen repositorios y productos enviados.', 31, 42, 39, 42, 43, 43, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Sin registros', 'Cuando no hay entregas, la vista muestra este aviso.', 79, 56, 73, 56, 55, 51, '-100%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/docente-materia/01-panel-principal.png' => [
                $this->marca('Carga', 'Estas tarjetas resumen tu trabajo de evaluación.', 29, 35, 37, 35, 39, 39, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Atención', 'Prioriza entregas vencidas o recibidas sin revisar.', 82, 54, 76, 54, 73, 58, '-100%', '-50%', '10.5rem', 'rose'),
                $this->marca('Apartados', 'Consulta próximos apartados asignados.', 60, 80, 55, 80, 53, 82, '-50%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/docente-materia/02-mis-asignaciones.png' => [
                $this->marca('Periodo', 'Verifica el periodo de asignaciones.', 88, 27, 84, 27, 83, 25, '-100%', '-50%', '9.5rem', 'amber'),
                $this->marca('Resumen', 'Mira cuántos apartados y materias tienes asignadas.', 29, 44, 37, 44, 41, 43, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Apartados', 'Aquí se listarán los apartados por evaluar.', 60, 64, 55, 64, 51, 66, '-50%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/docente-materia/03-revisiones.png' => [
                $this->marca('Filtros', 'Busca por equipo, proyecto, apartado, grupo o estado.', 42, 36, 48, 36, 47, 37, '-50%', '-50%', '10.5rem', 'amber'),
                $this->marca('Apartado', 'Selecciona un apartado para revisar entregas.', 29, 58, 37, 58, 33, 59, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Bandeja', 'Las entregas pendientes aparecerán en esta zona.', 83, 67, 76, 67, 55, 74, '-100%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/docente-materia/03-revisiones-entrega-abierta.png' => [
                $this->marca('Apartado activo', 'Selecciona el apartado para ver sus entregas.', 27, 42, 36, 42, 30, 45, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Entrega recibida', 'Revisa equipo, proyecto, fecha y estado.', 91, 64, 84, 66, 75, 84, '-100%', '-50%', '10rem', 'amber'),
                $this->marca('Abrir detalle', 'La fila despliega evidencias y formulario.', 91, 96, 84, 90, 95, 85, '-100%', '-100%', '9.5rem', 'green'),
            ],
            'assets/ayuda/docente-materia/03-revisiones-entrega-formulario.png' => [
                $this->marca('Evidencias', 'Abre o descarga archivos antes de evaluar.', 26, 40, 34, 43, 43, 51, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Criterio', 'Confirma apartado, ponderación y versión.', 91, 47, 84, 47, 78, 51, '-100%', '-50%', '9.5rem', 'amber'),
                $this->marca('Evaluación', 'Elige resultado, calificación y observaciones.', 25, 74, 35, 72, 44, 69, '0%', '-50%', '10rem', 'green'),
                $this->marca('Comentarios', 'Agrega seguimiento después de guardar revisión.', 91, 83, 84, 83, 90, 87, '-100%', '-50%', '10rem', 'rose'),
            ],
            'assets/ayuda/estudiante/01-panel-principal.png' => [
                $this->marca('Progreso', 'El porcentaje muestra avance validado del equipo.', 30, 46, 38, 46, 37, 42, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Estados', 'Identifica pendientes, rechazados o validados.', 80, 43, 74, 43, 73, 40, '-100%', '-50%', '10rem', 'amber'),
                $this->marca('Guía', 'Revisa cada apartado y su último estado.', 59, 77, 54, 77, 48, 82, '-50%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/estudiante/02-mi-proyecto.png' => [
                $this->marca('Proyecto', 'Confirma que sea el proyecto de tu equipo.', 32, 39, 39, 39, 34, 39, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Datos clave', 'Aquí aparecen responsable, guía e integrantes.', 39, 62, 47, 62, 44, 60, '-50%', '-50%', '10.5rem', 'amber'),
                $this->marca('Integrantes', 'Valida que todos los integrantes estén correctos.', 82, 82, 75, 82, 70, 82, '-100%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/estudiante/03-entregas.png' => [
                $this->marca('Apartados', 'Selecciona el apartado que vas a revisar o entregar.', 31, 42, 39, 42, 33, 43, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Estado', 'Cada fila indica si ya fue enviada o revisada.', 82, 45, 76, 45, 73, 44, '-100%', '-50%', '10rem', 'amber'),
                $this->marca('Pendientes', 'Atiende primero los apartados sin entrega.', 59, 67, 53, 67, 49, 65, '-50%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/estudiante/03-entregas-detalle-apartado.png' => [
                $this->marca('Abrir apartado', 'Al hacer clic se despliega el detalle.', 31, 30, 39, 35, 35, 41, '0%', '-50%', '9.5rem', 'blue'),
                $this->marca('Aviso', 'Lee si el plazo permite enviar o solo consultar.', 30, 60, 38, 56, 42, 50, '0%', '-50%', '9.5rem', 'rose'),
                $this->marca('Estados', 'Revisa la última entrega y su estado antes de abrir otro apartado.', 91, 42, 84, 42, 81, 39, '-100%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/estudiante/04-codigo-repositorio.png' => [
                $this->marca('Producto', 'Aquí se informa el estado del producto final.', 36, 39, 44, 39, 44, 38, '0%', '-50%', '9rem', 'blue'),
                $this->marca('Advertencia', 'Si falta registrar, atiende este aviso antes del cierre.', 94, 47, 88, 47, 60, 43, '-100%', '-50%', '9.5rem', 'rose'),
                $this->marca('Menú', 'También puedes volver a entregas o proyecto desde el lateral.', 3, 42, 12, 42, 8, 42, '0%', '-50%', '10rem', 'green'),
            ],
        ];

        return $marcas[$imagen] ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resaltadosPorImagen(string $imagen): array
    {
        $resaltados = [
            'assets/ayuda/acceso/01-iniciar-sesion.png' => [
                $this->resaltado(61.5, 13.5, 7.5, 5, 'green'),
                $this->resaltado(66, 50.5, 26, 5, 'blue'),
                $this->resaltado(66, 68, 26, 6, 'green'),
            ],
            'assets/ayuda/acceso/02-recuperar-contrasena.png' => [
                $this->resaltado(35.5, 22, 7.5, 5, 'green'),
                $this->resaltado(35.5, 58, 29, 6, 'blue'),
                $this->resaltado(35.5, 66.5, 29, 6, 'green'),
            ],
            'assets/ayuda/acceso/03-actualizar-contrasena.png' => [
                $this->resaltado(35.5, 12, 7.5, 5, 'green'),
                $this->resaltado(35.5, 49, 29, 6, 'blue'),
                $this->resaltado(35.5, 61, 29, 6, 'amber'),
                $this->resaltado(35.5, 74, 29, 6, 'blue'),
                $this->resaltado(35.5, 82, 29, 6, 'green'),
            ],
            'assets/ayuda/coordinacion/01-panel-principal.png' => [
                $this->resaltado(23.5, 30, 73, 13, 'blue'),
                $this->resaltado(23.5, 51.5, 73, 17, 'amber'),
            ],
            'assets/ayuda/coordinacion/02-jerarquia-proyectos.png' => [
                $this->resaltado(23.5, 29, 73, 13, 'blue'),
                $this->resaltado(84, 36, 11, 5, 'green'),
            ],
            'assets/ayuda/coordinacion/03-periodos.png' => [
                $this->resaltado(23.5, 32.5, 73, 12, 'blue'),
                $this->resaltado(87, 50, 9.5, 5.5, 'green'),
            ],
            'assets/ayuda/coordinacion/03-periodos-formulario.png' => [
                $this->resaltado(23.5, 39, 73, 24, 'blue'),
                $this->resaltado(39, 69, 16, 6, 'green'),
            ],
            'assets/ayuda/coordinacion/04-carreras-grupos.png' => [
                $this->resaltado(23.5, 30, 73, 13, 'blue'),
                $this->resaltado(23.5, 50, 73, 28, 'green', true),
            ],
            'assets/ayuda/coordinacion/05-usuarios.png' => [
                $this->resaltado(23.5, 36, 73, 14, 'blue'),
                $this->resaltado(23.5, 52, 73, 34, 'green', true),
            ],
            'assets/ayuda/coordinacion/05-usuarios-docentes-formulario.png' => [
                $this->resaltado(23.5, 34, 73, 10, 'blue'),
                $this->resaltado(23.5, 51, 73, 26, 'green', true),
            ],
            'assets/ayuda/coordinacion/06-asignaturas.png' => [
                $this->resaltado(23.5, 34, 73, 15, 'blue'),
                $this->resaltado(23.5, 58, 73, 28, 'green', true),
            ],
            'assets/ayuda/coordinacion/06-asignaturas-formularios.png' => [
                $this->resaltado(23.5, 35, 34, 38, 'blue'),
                $this->resaltado(61, 35, 35.5, 38, 'amber'),
            ],
            'assets/ayuda/coordinacion/07-guias.png' => [
                $this->resaltado(23.5, 35, 73, 16, 'blue'),
                $this->resaltado(23.5, 61, 73, 27, 'green', true),
            ],
            'assets/ayuda/coordinacion/07-guias-formularios.png' => [
                $this->resaltado(23.5, 38, 34, 55, 'blue'),
                $this->resaltado(61, 38, 35.5, 55, 'green'),
            ],
            'assets/ayuda/coordinacion/07-guias-apartados-lista.png' => [
                $this->resaltado(23.5, 31, 73, 20, 'blue'),
                $this->resaltado(23.5, 52, 73, 40, 'amber', true),
            ],
            'assets/ayuda/docente-lider/01-panel-principal.png' => [
                $this->resaltado(23.5, 31, 73, 13, 'blue'),
                $this->resaltado(23.5, 60, 73, 27, 'amber'),
            ],
            'assets/ayuda/docente-lider/02-lista-alumnos.png' => [
                $this->resaltado(23.5, 35, 73, 15, 'blue'),
                $this->resaltado(23.5, 54, 73, 33, 'green', true),
            ],
            'assets/ayuda/docente-lider/03-equipos.png' => [
                $this->resaltado(23.5, 32, 73, 13, 'blue'),
                $this->resaltado(23.5, 60, 73, 28, 'green', true),
            ],
            'assets/ayuda/docente-lider/03-equipos-nuevo-equipo.png' => [
                $this->resaltado(23.5, 38, 73, 31, 'blue'),
                $this->resaltado(23.5, 70, 73, 8, 'amber'),
            ],
            'assets/ayuda/docente-lider/03-equipos-integrantes.png' => [
                $this->resaltado(23.5, 30, 73, 20, 'blue'),
                $this->resaltado(23.5, 51, 73, 41, 'green', true),
                $this->resaltado(88, 57, 8, 34, 'rose', true),
            ],
            'assets/ayuda/docente-lider/04-proyectos.png' => [
                $this->resaltado(23.5, 45, 73, 17, 'blue'),
                $this->resaltado(23.5, 66, 73, 22, 'green', true),
            ],
            'assets/ayuda/docente-lider/04-proyectos-administrar.png' => [
                $this->resaltado(23.5, 30, 36, 42, 'green'),
                $this->resaltado(61, 30, 35.5, 42, 'amber'),
            ],
            'assets/ayuda/docente-lider/05-revision-codigo.png' => [
                $this->resaltado(23.5, 34, 73, 17, 'blue'),
                $this->resaltado(23.5, 52, 73, 11, 'green'),
            ],
            'assets/ayuda/docente-materia/01-panel-principal.png' => [
                $this->resaltado(23.5, 31, 73, 13, 'blue'),
                $this->resaltado(23.5, 50, 73, 20, 'rose'),
            ],
            'assets/ayuda/docente-materia/02-mis-asignaciones.png' => [
                $this->resaltado(23.5, 33, 73, 16, 'blue'),
                $this->resaltado(23.5, 52, 73, 26, 'green'),
            ],
            'assets/ayuda/docente-materia/03-revisiones.png' => [
                $this->resaltado(23.5, 31, 73, 18, 'amber'),
                $this->resaltado(23.5, 55, 73, 25, 'green'),
            ],
            'assets/ayuda/docente-materia/03-revisiones-entrega-abierta.png' => [
                $this->resaltado(23.5, 23, 73, 16, 'blue'),
                $this->resaltado(23.5, 39, 73, 13, 'amber'),
                $this->resaltado(24.5, 56, 72, 39, 'green', true),
            ],
            'assets/ayuda/docente-materia/03-revisiones-entrega-formulario.png' => [
                $this->resaltado(26, 33, 34, 12, 'blue'),
                $this->resaltado(61, 33, 34, 12, 'amber'),
                $this->resaltado(25.5, 47, 71, 18, 'green'),
                $this->resaltado(25.5, 66, 71, 28, 'rose', true),
            ],
            'assets/ayuda/estudiante/01-panel-principal.png' => [
                $this->resaltado(23.5, 31, 73, 27, 'blue'),
                $this->resaltado(23.5, 61, 73, 29, 'green', true),
            ],
            'assets/ayuda/estudiante/02-mi-proyecto.png' => [
                $this->resaltado(23.5, 29, 73, 32, 'blue'),
                $this->resaltado(23.5, 64, 73, 28, 'green', true),
            ],
            'assets/ayuda/estudiante/03-entregas.png' => [
                $this->resaltado(23.5, 31, 73, 56, 'blue'),
                $this->resaltado(79, 38, 14, 42, 'amber', true),
            ],
            'assets/ayuda/estudiante/03-entregas-detalle-apartado.png' => [
                $this->resaltado(23.5, 36, 73, 16, 'rose'),
                $this->resaltado(23.5, 53, 73, 42, 'blue', true),
            ],
            'assets/ayuda/estudiante/04-codigo-repositorio.png' => [
                $this->resaltado(23.5, 32, 73, 22, 'rose'),
                $this->resaltado(1, 24, 19, 26, 'green', true),
            ],
        ];

        return $resaltados[$imagen] ?? [];
    }

    private function marca(
        string $title,
        string $text,
        int|float $x,
        int|float $y,
        int|float $fromX,
        int|float $fromY,
        int|float $toX,
        int|float $toY,
        string $translateX = '-50%',
        string $translateY = '-50%',
        string $width = '11rem',
        string $variant = 'blue',
    ): array {
        return [
            'title' => $title,
            'text' => $text,
            'x' => $x,
            'y' => $y,
            'from_x' => $fromX,
            'from_y' => $fromY,
            'to_x' => $toX,
            'to_y' => $toY,
            'translate_x' => $translateX,
            'translate_y' => $translateY,
            'width' => $width,
            'variant' => $variant,
        ];
    }

    /**
     * @return array{titulo: string, texto: string}
     */
    private function detalle(string $titulo, string $texto): array
    {
        return [
            'titulo' => $titulo,
            'texto' => $texto,
        ];
    }

    private function resaltado(
        int|float $x,
        int|float $y,
        int|float $w,
        int|float $h,
        string $variant = 'blue',
        bool $dashed = false,
    ): array {
        return [
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => $h,
            'variant' => $variant,
            'dashed' => $dashed,
        ];
    }

    private function imagen(string $ruta): ?string
    {
        $archivo = public_path($ruta);

        if (! file_exists($archivo)) {
            return null;
        }

        return asset($ruta).'?v='.filemtime($archivo);
    }

    /**
     * @return array<int, array{pregunta: string, respuesta: string}>
     */
    private function faqs(string $rol): array
    {
        $base = [
            ['pregunta' => 'No puedo entrar al sistema', 'respuesta' => 'Verifica que la matrícula y la contraseña sean correctas. Si el problema continúa, pide que revisen el estado de tu cuenta.'],
                ['pregunta' => 'No veo una opción del menú', 'respuesta' => 'El menú muestra las opciones disponibles para tu cuenta. Si falta algo, reporta la pantalla y la acción que necesitas realizar.'],
                ['pregunta' => 'Una pantalla aparece sin registros', 'respuesta' => 'Revisa el periodo activo, los filtros aplicados y el grupo seleccionado. Algunas vistas solo muestran información cuando ya existen equipos, proyectos o entregas.'],
        ];

        $porRol = match ($rol) {
            'coordinacion' => [
                ['pregunta' => '¿Qué debo configurar primero?', 'respuesta' => 'Primero periodo, carreras, grupos y usuarios. Después asignaturas, responsables de grupo, guías, equipos y proyectos.'],
                ['pregunta' => '¿Por qué una persona no ve sus opciones?', 'respuesta' => 'Debe estar vinculada al grupo, materia o proyecto correspondiente en el periodo activo.'],
            ],
            'docente_lider' => [
                ['pregunta' => '¿Por qué no puedo crear proyectos?', 'respuesta' => 'El equipo debe existir y contar con una guía disponible para el periodo. Revisa también que el grupo pertenezca a tu asignación.'],
                ['pregunta' => '¿Puedo mover alumnos entre equipos?', 'respuesta' => 'Si tienes permiso sobre el grupo, puedes ajustar integrantes. El sistema evita que un alumno quede activo en dos equipos al mismo tiempo.'],
                ['pregunta' => '¿Por qué no se envió un recordatorio manual?', 'respuesta' => 'Los alumnos deben tener entregas pendientes o por corregir y plazo vigente; los docentes deben tener una versión asignada sin revisar. Se evitan avisos repetidos del mismo día. Revisa también la cola y la configuración del correo.'],
            ],
            'docente_materia' => [
                ['pregunta' => '¿Por qué no hay entregas pendientes?', 'respuesta' => 'Puede ocurrir cuando ningún equipo ha enviado evidencias o cuando no tienes apartados asignados en la guía del periodo.'],
                ['pregunta' => '¿Qué resultado debo usar?', 'respuesta' => 'Usa aprobada cuando cumple, corrección cuando requiere ajustes y rechazada cuando la evidencia no corresponde o no se puede evaluar.'],
            ],
            default => [
                ['pregunta' => '¿Por qué no veo mi proyecto?', 'respuesta' => 'Tu cuenta debe estar dentro de un equipo activo y ese equipo debe tener un proyecto integrador relacionado.'],
                ['pregunta' => '¿Puedo enviar otra versión?', 'respuesta' => 'Si la fecha límite sigue abierta, puedes enviar una nueva versión desde Entregas o Código y repositorio.'],
            ],
        };

        return [...$base, ...$porRol];
    }
}
