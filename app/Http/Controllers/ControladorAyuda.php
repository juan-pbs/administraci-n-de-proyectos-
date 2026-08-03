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
     * @param array<int, array<string, string>> $navegacion
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
            'descripcion' => 'Guia visual para entrar al sistema, recuperar el acceso y actualizar una contrasena temporal.',
            'secciones' => [
                $this->seccionAcceso(),
                $this->seccionRecuperarContrasena(),
                $this->seccionActualizarContrasena(),
            ],
            'faqs' => [
                ['pregunta' => 'No puedo entrar al sistema', 'respuesta' => 'Verifica matricula y contrasena. Si el problema continua, usa recuperacion de acceso o reporta el mensaje que aparece.'],
                ['pregunta' => 'No recuerdo mi contrasena', 'respuesta' => 'Usa la opcion Olvidaste tu contrasena y captura el correo registrado para solicitar instrucciones.'],
            ],
        ];
    }

    /**
     * @param array<string, string> $rutas
     * @return array<string, mixed>
     */
    private function ayudaCoordinacion(array $rutas): array
    {
        return [
            'eyebrow' => 'Gestion academica',
            'titulo' => 'Ayuda de tu panel',
            'descripcion' => 'Guia visual para configurar periodos, usuarios, grupos, asignaturas y guias integradoras.',
            'secciones' => [
                $this->seccionModulo(
                    id: 'panel-principal',
                    menu: 'Panel principal',
                    titulo: 'Panel principal',
                    descripcion: 'Esta pantalla muestra indicadores del periodo activo y avisos que conviene atender antes de capturar nueva informacion.',
                    pasos: [
                        'Revisa las metricas generales para saber si ya existen carreras, grupos, alumnos y guias configuradas.',
                        'Lee los avisos de atencion operativa para ubicar pendientes antes de continuar.',
                        'Usa la tabla del periodo para confirmar grupo, responsable y cantidad de equipos registrados.',
                    ],
                    imagen: 'assets/ayuda/coordinacion/01-panel-principal.png',
                    ruta: $rutas['dashboard'] ?? null,
                    notas: ['Metricas del periodo', 'Alertas operativas', 'Resumen de grupos'],
                ),
                $this->seccionModulo(
                    id: 'jerarquia-proyectos',
                    menu: 'Jerarquia de proyectos',
                    titulo: 'Jerarquia de proyectos',
                    descripcion: 'Aqui se indica quien queda como responsable de cada grupo y que asignatura organiza el proyecto integrador.',
                    pasos: [
                        'Filtra por periodo o carrera cuando necesites trabajar con un bloque especifico.',
                        'Selecciona el responsable del grupo y la asignatura principal en la fila correspondiente.',
                        'Presiona Guardar asignacion en cada fila antes de pasar a equipos o proyectos.',
                    ],
                    imagen: 'assets/ayuda/coordinacion/02-jerarquia-proyectos.png',
                    ruta: $rutas['jerarquia-proyectos'] ?? null,
                    notas: ['Filtros superiores', 'Asignacion vigente', 'Accion de guardado'],
                ),
                $this->seccionModulo(
                    id: 'periodos',
                    menu: 'Periodos',
                    titulo: 'Periodos academicos',
                    descripcion: 'Los periodos controlan las fechas de trabajo, entregas y configuracion academica que usaran las demas pantallas.',
                    pasos: [
                        'Usa Nuevo periodo para abrir el formulario de captura.',
                        'Captura nombre, fecha inicial, fecha final y estado del periodo.',
                        'Revisa el historial para confirmar si un periodo esta activo, en borrador o cerrado.',
                    ],
                    imagen: 'assets/ayuda/coordinacion/03-periodos.png',
                    ruta: $rutas['periodos'] ?? null,
                    notas: ['Formulario de periodo', 'Estado operativo', 'Listado historico'],
                ),
                $this->seccionModulo(
                    id: 'carreras-grupos',
                    menu: 'Carreras y grupos',
                    titulo: 'Carreras y grupos',
                    descripcion: 'Esta vista organiza carreras y grupos para que despues puedan relacionarse alumnos, docentes, equipos y proyectos.',
                    pasos: [
                        'Crea primero la carrera con su nombre y clave.',
                        'Agrega grupos indicando carrera, periodo, grado y nombre del grupo.',
                        'Usa los filtros para revisar que la estructura quedo guardada en el periodo correcto.',
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
                        'Usa las pestanas para cambiar entre lista de alumnos y registro de personal academico.',
                        'Carga alumnos con la plantilla institucional cuando inicie un periodo.',
                        'Valida matricula, correo, carrera, grupo y tipo de cuenta antes de guardar.',
                    ],
                    imagen: 'assets/ayuda/coordinacion/05-usuarios.png',
                    ruta: $rutas['usuarios'] ?? null,
                    notas: ['Pestanas de usuarios', 'Carga por plantilla', 'Filtros y listados'],
                ),
                $this->seccionModulo(
                    id: 'asignaturas',
                    menu: 'Asignaturas',
                    titulo: 'Asignaturas',
                    descripcion: 'Concentra el catalogo de materias y permite relacionarlas con las personas que las atenderan en el periodo.',
                    pasos: [
                        'Registra asignaturas con carrera, clave, grado y nombre.',
                        'Vincula responsables a las asignaturas que participaran en la guia integradora.',
                        'Filtra por carrera y cuatrimestre para validar la carga academica.',
                    ],
                    imagen: 'assets/ayuda/coordinacion/06-asignaturas.png',
                    ruta: $rutas['asignaturas'] ?? null,
                    notas: ['Catalogo', 'Asignacion docente', 'Vista operativa'],
                ),
                $this->seccionModulo(
                    id: 'guias',
                    menu: 'Guias',
                    titulo: 'Guias integradoras',
                    descripcion: 'Las guias definen apartados, ponderaciones, fechas limite y responsables de revision.',
                    pasos: [
                        'Crea o edita la guia del periodo y carrera correspondiente.',
                        'Agrega apartados con orden, ponderacion, fecha limite y tipo de evidencia.',
                        'Asigna quienes revisaran cada apartado y publica la guia cuando este completa.',
                    ],
                    imagen: 'assets/ayuda/coordinacion/07-guias.png',
                    ruta: $rutas['guias'] ?? null,
                    notas: ['Datos de guia', 'Apartados', 'Docentes calificadores'],
                ),
            ],
        ];
    }

    /**
     * @param array<string, string> $rutas
     * @return array<string, mixed>
     */
    private function ayudaDocenteLider(array $rutas): array
    {
        return [
            'eyebrow' => 'Gestion de grupos',
            'titulo' => 'Ayuda de tu panel',
            'descripcion' => 'Guia visual para consultar alumnos, formar equipos, asignar proyectos y revisar productos de codigo.',
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
                    notas: ['Alcance del docente', 'Resumen de grupos', 'Navegacion lateral'],
                ),
                $this->seccionModulo(
                    id: 'lista-alumnos',
                    menu: 'Lista de alumnos',
                    titulo: 'Lista de alumnos',
                    descripcion: 'Consulta los alumnos de tus grupos para validar que la carga academica sea correcta antes de formar equipos.',
                    pasos: [
                        'Filtra por periodo, carrera, grupo o busqueda.',
                        'Revisa matricula, nombre, correo y grupo de cada alumno.',
                        'Si falta un alumno, reporta la correccion con el responsable del sistema.',
                    ],
                    imagen: 'assets/ayuda/docente-lider/02-lista-alumnos.png',
                    ruta: $rutas['usuarios'] ?? null,
                    notas: ['Filtros', 'Grupos visibles', 'Listado de alumnos'],
                ),
                $this->seccionModulo(
                    id: 'equipos',
                    menu: 'Equipos',
                    titulo: 'Equipos',
                    descripcion: 'Organiza integrantes, responsable del equipo, asesores y relacion con proyectos.',
                    pasos: [
                        'Selecciona el grupo que deseas revisar.',
                        'Usa Nuevo equipo para crear un equipo o ajusta integrantes desde el detalle.',
                        'Verifica que cada equipo tenga proyecto asignado antes de continuar.',
                    ],
                    imagen: 'assets/ayuda/docente-lider/03-equipos.png',
                    ruta: $rutas['equipos'] ?? null,
                    notas: ['Grupos asignados', 'Tabla de equipos', 'Detalle de informacion'],
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
                    id: 'revision-codigo',
                    menu: 'Revision de codigo',
                    titulo: 'Revision de codigo',
                    descripcion: 'Permite revisar repositorios, archivos tecnicos y comentarios sobre el producto final.',
                    pasos: [
                        'Filtra por grupo para ubicar entregas de codigo.',
                        'Descarga archivos o abre el repositorio registrado por el equipo.',
                        'Guarda resultado, calificacion y comentarios tecnicos cuando corresponda.',
                    ],
                    imagen: 'assets/ayuda/docente-lider/05-revision-codigo.png',
                    ruta: $rutas['revision-codigo'] ?? null,
                    notas: ['Filtro de grupo', 'Entregas tecnicas', 'Registro de revision'],
                ),
            ],
        ];
    }

    /**
     * @param array<string, string> $rutas
     * @return array<string, mixed>
     */
    private function ayudaDocenteMateria(array $rutas): array
    {
        return [
            'eyebrow' => 'Revision academica',
            'titulo' => 'Ayuda de tu panel',
            'descripcion' => 'Guia visual para consultar apartados asignados y registrar revisiones de entregas.',
            'secciones' => [
                $this->seccionModulo(
                    id: 'panel-principal',
                    menu: 'Panel principal',
                    titulo: 'Panel principal',
                    descripcion: 'Esta pantalla resume entregas recibidas, pendientes de revision y proximos apartados por atender.',
                    pasos: [
                        'Revisa apartados asignados y entregas por revisar.',
                        'Atiende primero los avisos de vencidas sin entrega o recibidas sin revisar.',
                        'Usa el bloque de proximos apartados para preparar tus evaluaciones.',
                    ],
                    imagen: 'assets/ayuda/docente-materia/01-panel-principal.png',
                    ruta: $rutas['dashboard'] ?? null,
                    notas: ['Carga asignada', 'Alertas de atencion', 'Proximos apartados'],
                ),
                $this->seccionModulo(
                    id: 'mis-asignaciones',
                    menu: 'Mis asignaciones',
                    titulo: 'Mis asignaciones',
                    descripcion: 'Muestra los apartados, fechas limite y materias que debes revisar en el periodo activo.',
                    pasos: [
                        'Consulta orden, titulo, fecha limite y ponderacion.',
                        'Verifica la materia o etiqueta con la que participas.',
                        'Si falta un apartado, reporta la correccion con el responsable del sistema.',
                    ],
                    imagen: 'assets/ayuda/docente-materia/02-mis-asignaciones.png',
                    ruta: $rutas['asignaciones-docente'] ?? null,
                    notas: ['Apartados', 'Materia vinculada', 'Fecha limite'],
                ),
                $this->seccionModulo(
                    id: 'revisiones',
                    menu: 'Revisiones',
                    titulo: 'Revision de entregas',
                    descripcion: 'Bandeja donde revisas evidencias, registras resultado y agregas comentarios.',
                    pasos: [
                        'Filtra por equipo, proyecto, apartado, grupo o estado.',
                        'Abre los archivos de evidencia antes de calificar.',
                        'Registra resultado, calificacion y observaciones claras para el equipo.',
                    ],
                    imagen: 'assets/ayuda/docente-materia/03-revisiones.png',
                    ruta: $rutas['revisiones-docente'] ?? null,
                    notas: ['Filtros de revision', 'Entregas disponibles', 'Resultado y comentarios'],
                ),
            ],
        ];
    }

    /**
     * @param array<string, string> $rutas
     * @return array<string, mixed>
     */
    private function ayudaEstudiante(array $rutas): array
    {
        return [
            'eyebrow' => 'Trabajo del equipo',
            'titulo' => 'Ayuda de tu panel',
            'descripcion' => 'Guia visual para consultar el proyecto, enviar entregas y registrar codigo o repositorio.',
            'secciones' => [
                $this->seccionModulo(
                    id: 'panel-principal',
                    menu: 'Panel principal',
                    titulo: 'Panel principal',
                    descripcion: 'Esta pantalla muestra el avance del equipo y el estado de cada apartado de la guia.',
                    pasos: [
                        'Revisa el porcentaje validado y la distribucion de estados.',
                        'Ubica apartados sin entregar, pendientes, rechazados o validados.',
                        'Usa el resumen para decidir que evidencia debes atender primero.',
                    ],
                    imagen: 'assets/ayuda/estudiante/01-panel-principal.png',
                    ruta: $rutas['dashboard'] ?? null,
                    notas: ['Progreso del equipo', 'Estados de entrega', 'Resumen de la guia'],
                ),
                $this->seccionModulo(
                    id: 'mi-proyecto',
                    menu: 'Mi proyecto',
                    titulo: 'Mi proyecto',
                    descripcion: 'Consulta informacion del equipo, proyecto, asesores, asignaturas e integrantes.',
                    pasos: [
                        'Confirma que aparezcas dentro del equipo correcto.',
                        'Revisa asesor, participantes academicos y guia integradora asignada.',
                        'Si la informacion no coincide, reportalo con el responsable de seguimiento.',
                    ],
                    imagen: 'assets/ayuda/estudiante/02-mi-proyecto.png',
                    ruta: $rutas['mi-proyecto'] ?? null,
                    notas: ['Datos del equipo', 'Docentes vinculados', 'Integrantes'],
                ),
                $this->seccionModulo(
                    id: 'entregas',
                    menu: 'Entregas',
                    titulo: 'Entregas',
                    descripcion: 'Aqui se cargan evidencias por apartado y se consulta el estado de cada version enviada.',
                    pasos: [
                        'Selecciona el apartado que corresponde a tu evidencia.',
                        'Carga los archivos permitidos antes de la fecha limite.',
                        'Revisa comentarios y calificacion cuando el docente concluya la revision.',
                    ],
                    imagen: 'assets/ayuda/estudiante/03-entregas.png',
                    ruta: $rutas['mis-entregas'] ?? null,
                    notas: ['Apartados', 'Fecha limite', 'Estado de revision'],
                ),
                $this->seccionModulo(
                    id: 'codigo-repositorio',
                    menu: 'Codigo y repositorio',
                    titulo: 'Codigo y repositorio',
                    descripcion: 'Registra el enlace del repositorio y adjunta archivos tecnicos del producto de software.',
                    pasos: [
                        'Captura la URL del repositorio del equipo.',
                        'Indica la version enviada y adjunta archivos comprimidos o tecnicos.',
                        'Si el plazo termino, la entrega queda visible pero ya no se reemplaza.',
                    ],
                    imagen: 'assets/ayuda/estudiante/04-codigo-repositorio.png',
                    ruta: $rutas['codigo-estudiante'] ?? null,
                    notas: ['Repositorio', 'Version', 'Archivos tecnicos'],
                ),
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
            menu: 'Iniciar Sesion',
            titulo: 'Iniciar sesion',
            descripcion: 'La pantalla de acceso solicita matricula y contrasena para abrir el panel asignado a tu cuenta.',
            pasos: [
                'Captura la matricula institucional.',
                'Escribe tu contrasena y presiona Ingresar.',
                'Si tienes una contrasena temporal, el sistema pedira actualizarla antes de continuar.',
            ],
            imagen: 'assets/ayuda/acceso/01-iniciar-sesion.png',
            ruta: route('login'),
            notas: ['Matricula', 'Contrasena', 'Recuperacion de acceso'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function seccionRecuperarContrasena(): array
    {
        return $this->seccionModulo(
            id: 'recuperar-contrasena',
            menu: 'Recuperar contrasena',
            titulo: 'Recuperar contrasena',
            descripcion: 'Esta pantalla se usa cuando no recuerdas tu contrasena o necesitas solicitar instrucciones para volver a entrar.',
            pasos: [
                'Presiona Olvidaste tu contrasena desde el inicio de sesion.',
                'Escribe el correo registrado en tu cuenta.',
                'Usa Enviar instrucciones y revisa el correo para continuar el proceso.',
            ],
            imagen: 'assets/ayuda/acceso/02-recuperar-contrasena.png',
            ruta: route('password.request'),
            notas: ['Correo registrado', 'Enviar instrucciones', 'Volver al inicio'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function seccionActualizarContrasena(): array
    {
        return $this->seccionModulo(
            id: 'actualizar-contrasena',
            menu: 'Actualizar contrasena',
            titulo: 'Actualizar contrasena',
            descripcion: 'Aparece cuando entras con una contrasena temporal y necesitas crear una nueva antes de continuar.',
            pasos: [
                'Escribe la contrasena temporal que recibiste o que te indicaron.',
                'Captura una nueva contrasena de al menos 8 caracteres.',
                'Confirma la nueva contrasena y presiona Actualizar contrasena.',
            ],
            imagen: 'assets/ayuda/acceso/03-actualizar-contrasena.png',
            ruta: null,
            notas: ['Contrasena temporal', 'Nueva contrasena', 'Confirmacion'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function seccionCerrarSesion(): array
    {
        $capturas = $this->capturasDeSeccion('assets/ayuda/comun/01-botones-superiores.png', 'Cerrar sesion');

        return [
            'id' => 'cerrar-sesion',
            'menu' => 'Cerrar Sesion',
            'titulo' => 'Cerrar sesion',
            'descripcion' => 'Usa esta opcion al terminar para proteger tu cuenta, especialmente en equipos compartidos.',
            'pasos' => [
                'Presiona el boton Salir que aparece en la esquina superior derecha.',
                'Confirma que vuelvas a la pantalla de inicio de sesion.',
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
                $this->detalle('Boton Salir', 'Cierra la sesion actual y te regresa a la pantalla de inicio de sesion.'),
                $this->detalle('Cuando usarlo', 'Usalo al terminar de trabajar, especialmente si la computadora es compartida.'),
            ],
            'tipo' => 'logout',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seccionContacto(): array
    {
        return [
            'id' => 'contactanos',
            'menu' => 'Contactanos',
            'titulo' => 'Contactanos',
            'descripcion' => 'Si el problema continua, reportalo con datos suficientes para que el responsable pueda reproducirlo.',
            'pasos' => [
                'Nombre completo y matricula con la que ingresaste.',
                'Modulo, pantalla y accion que intentabas realizar.',
                'Captura de pantalla, mensaje de error y hora aproximada del incidente.',
            ],
            'imagen' => null,
            'caption' => null,
            'ruta' => null,
            'rutaEtiqueta' => null,
            'notas' => [],
            'capturas' => [],
            'marcas' => [],
            'resaltados' => [],
            'detalles' => [
                $this->detalle('Datos necesarios', 'Incluye matricula, pantalla, accion realizada y una captura del mensaje o comportamiento observado.'),
                $this->detalle('Cuando reportar', 'Reporta cuando no puedas guardar, falten datos, no aparezca una opcion esperada o veas informacion incorrecta.'),
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
        string $imagen,
        ?string $ruta,
        array $notas,
    ): array {
        $capturas = $this->capturasDeSeccion($imagen, $titulo);

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
            'assets/ayuda/acceso/01-iniciar-sesion.png' => [
                $this->capturaContexto('assets/ayuda/acceso/02-recuperar-contrasena.png', 'Recuperacion de acceso', 'Captura de contexto: formulario para solicitar recuperacion de contrasena.'),
            ],
            'assets/ayuda/coordinacion/01-panel-principal.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/01-panel-principal-detalle.png', 'Acercamiento: indicadores y avisos', 'Captura de contexto: metricas, avisos y resumen del periodo.'),
            ],
            'assets/ayuda/coordinacion/02-jerarquia-proyectos.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/02-jerarquia-proyectos-detalle.png', 'Acercamiento: asignaciones por grupo', 'Captura de contexto: filtros, responsables, asignatura principal y boton de guardado.'),
            ],
            'assets/ayuda/coordinacion/03-periodos.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/03-periodos-detalle.png', 'Acercamiento: registro e historial', 'Captura de contexto: boton para crear periodo, tarjetas de estado e historial.'),
                $this->capturaContexto('assets/ayuda/coordinacion/03-periodos-formulario.png', 'Estado abierto: nuevo periodo', 'Captura de contexto: formulario que aparece al abrir Nuevo periodo.'),
            ],
            'assets/ayuda/coordinacion/04-carreras-grupos.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/04-carreras-grupos-detalle.png', 'Acercamiento: carreras, grupos y docentes', 'Captura de contexto: formularios para registrar estructura academica y responsables.'),
            ],
            'assets/ayuda/coordinacion/05-usuarios.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/05-usuarios-detalle.png', 'Acercamiento: alumnos y carga masiva', 'Captura de contexto: pestanas, filtros, plantilla y acciones de importacion.'),
                $this->capturaContexto('assets/ayuda/coordinacion/05-usuarios-docentes-formulario.png', 'Estado abierto: registro de docentes', 'Captura de contexto: pestana de docentes con filas de captura y botones de alta.'),
            ],
            'assets/ayuda/coordinacion/06-asignaturas.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/06-asignaturas-detalle.png', 'Acercamiento: catalogo y asignacion', 'Captura de contexto: registro de asignaturas, asignacion por periodo y carga operativa.'),
                $this->capturaContexto('assets/ayuda/coordinacion/06-asignaturas-formularios.png', 'Estado abierto: formularios de asignatura', 'Captura de contexto: paneles abiertos para crear asignatura y asignar docente.'),
            ],
            'assets/ayuda/coordinacion/07-guias.png' => [
                $this->capturaContexto('assets/ayuda/coordinacion/07-guias-detalle.png', 'Acercamiento: guia y apartados', 'Captura de contexto: alta de guia, apartados, ponderaciones y configuracion.'),
                $this->capturaContexto('assets/ayuda/coordinacion/07-guias-formularios.png', 'Estado abierto: guia y apartados', 'Captura de contexto: paneles abiertos para crear guia y capturar apartados.'),
                $this->capturaContexto('assets/ayuda/coordinacion/07-guias-apartados-lista.png', 'Estado abierto: detalle de guia', 'Captura de contexto: detalle que aparece al abrir una guia publicada o en configuracion.'),
            ],
            'assets/ayuda/docente-lider/01-panel-principal.png' => [
                $this->capturaContexto('assets/ayuda/docente-lider/01-panel-principal-detalle.png', 'Acercamiento: grupos de trabajo', 'Captura de contexto: indicadores y listado de grupos activos.'),
            ],
            'assets/ayuda/docente-lider/02-lista-alumnos.png' => [
                $this->capturaContexto('assets/ayuda/docente-lider/02-lista-alumnos-detalle.png', 'Acercamiento: filtros y alumnos', 'Captura de contexto: selector de grupo, busqueda y tabla de alumnos.'),
            ],
            'assets/ayuda/docente-lider/03-equipos.png' => [
                $this->capturaContexto('assets/ayuda/docente-lider/03-equipos-detalle.png', 'Acercamiento: equipos por grupo', 'Captura de contexto: boton para crear equipo, seleccion de grupo y tabla de equipos.'),
                $this->capturaContexto('assets/ayuda/docente-lider/03-equipos-nuevo-equipo.png', 'Estado abierto: nuevo equipo', 'Captura de contexto: formulario que aparece al abrir Nuevo equipo.'),
                $this->capturaContexto('assets/ayuda/docente-lider/03-equipos-integrantes.png', 'Estado abierto: integrantes del equipo', 'Captura de contexto: panel que aparece al abrir Ver informacion en un equipo.'),
            ],
            'assets/ayuda/docente-lider/04-proyectos.png' => [
                $this->capturaContexto('assets/ayuda/docente-lider/04-proyectos-detalle.png', 'Acercamiento: proyectos y acciones', 'Captura de contexto: filtros, proyectos por equipo, estado y acciones de administracion.'),
                $this->capturaContexto('assets/ayuda/docente-lider/04-proyectos-administrar.png', 'Estado abierto: administrar proyecto', 'Captura de contexto: panel que aparece al abrir Administrar en un proyecto.'),
            ],
            'assets/ayuda/docente-lider/05-revision-codigo.png' => [
                $this->capturaContexto('assets/ayuda/docente-lider/05-revision-codigo-detalle.png', 'Acercamiento: bandeja tecnica', 'Captura de contexto: periodo visible y zona donde aparecen productos de codigo.'),
            ],
            'assets/ayuda/docente-materia/01-panel-principal.png' => [
                $this->capturaContexto('assets/ayuda/docente-materia/01-panel-principal-detalle.png', 'Acercamiento: pendientes y avisos', 'Captura de contexto: indicadores, entregas por revisar y alertas de atencion.'),
            ],
            'assets/ayuda/docente-materia/02-mis-asignaciones.png' => [
                $this->capturaContexto('assets/ayuda/docente-materia/02-mis-asignaciones-detalle.png', 'Acercamiento: apartados asignados', 'Captura de contexto: periodo, materias, ponderacion y apartados por revisar.'),
            ],
            'assets/ayuda/docente-materia/03-revisiones.png' => [
                $this->capturaContexto('assets/ayuda/docente-materia/03-revisiones-detalle.png', 'Acercamiento: filtros y bandeja', 'Captura de contexto: busqueda, filtros, apartado seleccionado y entregas disponibles.'),
                $this->capturaContexto('assets/ayuda/docente-materia/03-revisiones-entrega-abierta.png', 'Estado abierto: entrega recibida', 'Captura de contexto: entrega desplegada desde la bandeja de revision.'),
                $this->capturaContexto('assets/ayuda/docente-materia/03-revisiones-entrega-formulario.png', 'Estado abierto: formulario de revision', 'Captura de contexto: evidencias, resultado, calificacion, observaciones y comentarios.'),
            ],
            'assets/ayuda/estudiante/01-panel-principal.png' => [
                $this->capturaContexto('assets/ayuda/estudiante/01-panel-principal-detalle.png', 'Acercamiento: progreso y guia', 'Captura de contexto: porcentaje validado, estados y resumen de apartados.'),
            ],
            'assets/ayuda/estudiante/02-mi-proyecto.png' => [
                $this->capturaContexto('assets/ayuda/estudiante/02-mi-proyecto-detalle.png', 'Acercamiento: datos del proyecto', 'Captura de contexto: equipo, asesor, guia e integrantes registrados.'),
            ],
            'assets/ayuda/estudiante/03-entregas.png' => [
                $this->capturaContexto('assets/ayuda/estudiante/03-entregas-detalle.png', 'Acercamiento: apartados y estados', 'Captura de contexto: lista de apartados, fecha limite, ultimo envio y estado.'),
                $this->capturaContexto('assets/ayuda/estudiante/03-entregas-detalle-apartado.png', 'Estado abierto: detalle de apartado', 'Captura de contexto: informacion que aparece al abrir un apartado de entrega.'),
            ],
            'assets/ayuda/estudiante/04-codigo-repositorio.png' => [
                $this->capturaContexto('assets/ayuda/estudiante/04-codigo-repositorio-detalle.png', 'Acercamiento: producto final', 'Captura de contexto: aviso, repositorio y archivos tecnicos del producto.'),
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
     * @param array<int, array<string, mixed>> $capturas
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
                $this->detalle('Matricula', 'Captura la matricula tal como fue registrada, sin espacios adicionales.'),
                $this->detalle('Contrasena', 'Escribe tu clave de acceso. Si es temporal, el sistema pedira actualizarla.'),
                $this->detalle('Iniciar sesion', 'Valida los datos y abre el panel asignado a tu cuenta.'),
                $this->detalle('Olvidaste tu contrasena', 'Usa ese enlace cuando no recuerdes tu clave o no puedas entrar.'),
                $this->detalle('Ayuda', 'Abre esta guia de acceso en otra ventana sin cerrar la pantalla actual.'),
            ],
            'assets/ayuda/acceso/02-recuperar-contrasena.png' => [
                $this->detalle('Correo registrado', 'Escribe el correo asociado a tu cuenta para solicitar instrucciones de recuperacion.'),
                $this->detalle('Enviar instrucciones', 'Busca la cuenta y envia el procedimiento de recuperacion si los datos coinciden.'),
                $this->detalle('Volver al inicio', 'Regresa a la pantalla de acceso cuando ya tengas una clave valida.'),
                $this->detalle('Ayuda', 'Abre la explicacion de recuperacion de acceso en otra ventana.'),
            ],
            'assets/ayuda/acceso/03-actualizar-contrasena.png' => [
                $this->detalle('Contrasena temporal', 'Escribe la contrasena actual que recibiste para tu primer acceso o recuperacion.'),
                $this->detalle('Nueva contrasena', 'Debe tener al menos 8 caracteres y ser diferente a la temporal.'),
                $this->detalle('Confirmar nueva contrasena', 'Repite la nueva contrasena exactamente igual para evitar errores de escritura.'),
                $this->detalle('Actualizar contrasena', 'Guarda el cambio y permite continuar al sistema cuando los datos son validos.'),
                $this->detalle('Ayuda', 'Abre esta guia en otra ventana si tienes duda sobre los campos.'),
            ],
            'assets/ayuda/coordinacion/01-panel-principal.png' => [
                $this->detalle('Tarjetas de resumen', 'Muestran cantidades actuales de carreras, grupos, alumnos y guias para detectar capturas pendientes.'),
                $this->detalle('Atencion operativa', 'Indica pendientes como grupos sin responsable, equipos sin proyecto o guias sin completar.'),
                $this->detalle('Grupos del periodo', 'Sirve para verificar rapidamente carrera, grupo, responsable asignado y cantidad de equipos.'),
                $this->detalle('Boton Ayuda', 'Abre la lista de temas para consultar la explicacion de cada pantalla.'),
            ],
            'assets/ayuda/coordinacion/02-jerarquia-proyectos.png' => [
                $this->detalle('Filtro de periodo', 'Usalo para trabajar con el ciclo correcto antes de modificar asignaciones.'),
                $this->detalle('Responsable del grupo', 'Selecciona la persona que dara seguimiento al grupo durante el periodo.'),
                $this->detalle('Asignatura principal', 'Selecciona la materia que organizara el proyecto integrador del grupo.'),
                $this->detalle('Guardar asignacion', 'Guarda los cambios solo de la fila donde se presiona el boton.'),
            ],
            'assets/ayuda/coordinacion/03-periodos.png' => [
                $this->detalle('Nuevo periodo', 'Abre el formulario para registrar un nuevo ciclo de trabajo.'),
                $this->detalle('Nombre del periodo', 'Usa un nombre claro, por ejemplo Mayo - Agosto 2026.'),
                $this->detalle('Fechas', 'La fecha inicial y final definen el rango de trabajo para guias y entregas.'),
                $this->detalle('Estado', 'Activo indica el periodo en uso; Borrador sirve para preparar informacion; Cerrado deja el ciclo solo para consulta.'),
            ],
            'assets/ayuda/coordinacion/03-periodos-formulario.png' => [
                $this->detalle('Panel desplegado', 'Se abre al presionar Nuevo periodo y muestra los campos necesarios para crear el ciclo.'),
                $this->detalle('Guardar periodo', 'Valida nombre, fechas y estado antes de agregarlo al historial.'),
            ],
            'assets/ayuda/coordinacion/04-carreras-grupos.png' => [
                $this->detalle('Nueva carrera', 'Captura nombre y clave de la carrera antes de crear grupos.'),
                $this->detalle('Nuevo grupo', 'Selecciona carrera, periodo, grado y escribe el grupo, por ejemplo 5A o 9B.'),
                $this->detalle('Agregar docente', 'Relaciona participantes academicos con una carrera para que aparezcan en asignaciones posteriores.'),
                $this->detalle('Filtros', 'Permiten revisar solo una carrera, un periodo o un grupo especifico.'),
            ],
            'assets/ayuda/coordinacion/05-usuarios.png' => [
                $this->detalle('Pestanas', 'Cambia entre consulta de alumnos y registro de personal academico.'),
                $this->detalle('Matricula', 'Debe ser unica; si ya existe, el sistema no permitira duplicarla.'),
                $this->detalle('Correo', 'Sirve para avisos y recuperacion de acceso, por eso debe estar bien escrito.'),
                $this->detalle('Importar alumnos', 'Carga una plantilla con matricula, nombre, correo, carrera y grupo. Revisa la vista previa antes de guardar.'),
            ],
            'assets/ayuda/coordinacion/05-usuarios-docentes-formulario.png' => [
                $this->detalle('Registro de docentes', 'Usa esta pestana para capturar una o varias cuentas academicas en una sola operacion.'),
                $this->detalle('Agregar docente', 'Inserta otra fila vacia cuando necesitas registrar mas de una persona.'),
                $this->detalle('Guardar docentes', 'Guarda todas las filas completas; revisa matricula, correo, tipo de cuenta y carrera antes de enviar.'),
            ],
            'assets/ayuda/coordinacion/06-asignaturas.png' => [
                $this->detalle('Registrar asignatura', 'Captura nombre, clave, carrera y grado para agregarla al catalogo.'),
                $this->detalle('Asignar docente', 'Relaciona una persona con la asignatura y el periodo visible.'),
                $this->detalle('Cambiar ciclo', 'Actualiza la carga mostrada cuando necesitas consultar otro periodo.'),
                $this->detalle('Tabla de carga', 'Muestra materias y responsables ya registrados para confirmar que no falte informacion.'),
            ],
            'assets/ayuda/coordinacion/06-asignaturas-formularios.png' => [
                $this->detalle('Catalogo permanente', 'El formulario de la izquierda crea la materia base por carrera y cuatrimestre.'),
                $this->detalle('Asignacion por periodo', 'El formulario de la derecha indica quien atendera esa materia solo durante el periodo seleccionado.'),
            ],
            'assets/ayuda/coordinacion/07-guias.png' => [
                $this->detalle('Crear guia', 'Registra la guia base para la carrera y periodo seleccionados.'),
                $this->detalle('Apartado', 'Captura titulo, orden, ponderacion, fecha limite y tipo de evidencia requerida.'),
                $this->detalle('Calificadores', 'Asigna quienes revisaran cada apartado antes de publicarlo.'),
                $this->detalle('Publicacion', 'Publica la guia hasta que todos los apartados tengan fechas, ponderacion y responsables de revision.'),
            ],
            'assets/ayuda/coordinacion/07-guias-formularios.png' => [
                $this->detalle('Crear nueva guia', 'Completa periodo, asignatura principal, nombre, cuatrimestre, version, estado, competencias y objetivo.'),
                $this->detalle('Nuevo apartado', 'Agrega cada entrega esperada con orden, porcentaje, fecha limite y si requiere documento o codigo.'),
            ],
            'assets/ayuda/coordinacion/07-guias-apartados-lista.png' => [
                $this->detalle('Detalle desplegado', 'Al abrir una guia se muestran sus competencias, objetivo y apartados configurados.'),
                $this->detalle('Docentes que califican', 'En cada apartado puedes asignar o quitar quien revisara esa evidencia.'),
            ],
            'assets/ayuda/docente-lider/01-panel-principal.png' => [
                $this->detalle('Indicadores', 'Resumen de grupos, alumnos, equipos y proyectos bajo seguimiento.'),
                $this->detalle('Grupos del periodo', 'Lista los grupos disponibles y permite confirmar si ya tienen equipos y proyectos.'),
                $this->detalle('Accesos del menu', 'Usa Lista de alumnos, Equipos, Proyectos y Revision de codigo para continuar el flujo.'),
            ],
            'assets/ayuda/docente-lider/02-lista-alumnos.png' => [
                $this->detalle('Grupo activo', 'Selecciona el grupo que quieres revisar para evitar consultar alumnos de otro grupo.'),
                $this->detalle('Busqueda', 'Puedes buscar por nombre, matricula o correo cuando la lista es larga.'),
                $this->detalle('Datos del alumno', 'Verifica matricula, nombre, correo, carrera y grupo antes de formar equipos.'),
                $this->detalle('Alumno faltante', 'Si no aparece alguien, reporta la matricula y grupo al responsable del sistema.'),
            ],
            'assets/ayuda/docente-lider/03-equipos.png' => [
                $this->detalle('Nuevo equipo', 'Crea un equipo dentro del grupo seleccionado.'),
                $this->detalle('Integrantes', 'Agrega alumnos disponibles y evita dejar alumnos duplicados en dos equipos.'),
                $this->detalle('Responsable del equipo', 'Selecciona quien coordinara al equipo cuando la vista lo solicite.'),
                $this->detalle('Ver informacion', 'Abre el detalle del equipo para revisar integrantes, asesores y proyecto relacionado.'),
            ],
            'assets/ayuda/docente-lider/03-equipos-nuevo-equipo.png' => [
                $this->detalle('Grupo del equipo', 'El equipo se crea dentro del grupo seleccionado; los alumnos disponibles se toman de ese mismo grupo.'),
                $this->detalle('Alumnos libres', 'Si no hay alumnos libres, el sistema evita crear otro equipo hasta liberar o cargar alumnos.'),
            ],
            'assets/ayuda/docente-lider/03-equipos-integrantes.png' => [
                $this->detalle('Alumnos del mismo grupo', 'Solo aparecen integrantes del grupo al que pertenece el equipo; no se mezclan alumnos de otros grupos.'),
                $this->detalle('Retirar', 'Quita al alumno del equipo y lo deja disponible para reasignarlo cuando corresponda.'),
                $this->detalle('Proyecto relacionado', 'El panel tambien muestra el proyecto vinculado al equipo para confirmar que la informacion coincide.'),
            ],
            'assets/ayuda/docente-lider/04-proyectos.png' => [
                $this->detalle('Filtro de grupo', 'Muestra solamente los proyectos del grupo seleccionado.'),
                $this->detalle('Proyecto por equipo', 'Revisa nombre, equipo, guia, periodo y estado del proyecto.'),
                $this->detalle('Administrar', 'Abre acciones para completar asesores, materias participantes o datos faltantes.'),
                $this->detalle('Estado', 'Sirve para saber si el proyecto esta pendiente, en proceso o ya configurado.'),
            ],
            'assets/ayuda/docente-lider/04-proyectos-administrar.png' => [
                $this->detalle('Docentes participantes', 'Agrega o retira personas vinculadas al proyecto y define si participan como asesor, evaluador o ambos.'),
                $this->detalle('Asignaturas participantes', 'Relaciona materias del mismo programa y cuatrimestre con el proyecto para dar seguimiento academico.'),
            ],
            'assets/ayuda/docente-lider/05-revision-codigo.png' => [
                $this->detalle('Periodo visible', 'Confirma que estas revisando el periodo correcto.'),
                $this->detalle('Bandeja de revision', 'Aqui aparecen repositorios, archivos tecnicos y productos enviados por equipos.'),
                $this->detalle('Archivos y repositorio', 'Abre primero la evidencia antes de guardar observaciones o calificacion.'),
                $this->detalle('Sin registros', 'Si no aparecen entregas, puede que ningun equipo haya registrado producto de codigo.'),
            ],
            'assets/ayuda/docente-materia/01-panel-principal.png' => [
                $this->detalle('Entregas recibidas', 'Indica cuantas evidencias llegaron para revision.'),
                $this->detalle('Pendientes por revisar', 'Muestra entregas que requieren atencion. Atiende primero las vencidas o recibidas sin resultado.'),
                $this->detalle('Proximos apartados', 'Ayuda a preparar revisiones antes de que lleguen nuevas entregas.'),
            ],
            'assets/ayuda/docente-materia/02-mis-asignaciones.png' => [
                $this->detalle('Periodo', 'Confirma que el periodo visible coincida con el ciclo que estas revisando.'),
                $this->detalle('Apartados asignados', 'Muestra titulo, orden, ponderacion y fecha limite de cada apartado.'),
                $this->detalle('Materia', 'Indica con que materia o etiqueta participa la revision.'),
                $this->detalle('Sin apartados', 'Si aparece vacio, todavia no hay apartados asignados para tu cuenta.'),
            ],
            'assets/ayuda/docente-materia/03-revisiones.png' => [
                $this->detalle('Filtros', 'Busca por equipo, proyecto, apartado, grupo o estado para encontrar entregas especificas.'),
                $this->detalle('Entrega disponible', 'Abre la evidencia antes de registrar resultado.'),
                $this->detalle('Resultado', 'Usa Aprobada cuando cumple, Correccion cuando necesita ajustes y Rechazada cuando no corresponde.'),
                $this->detalle('Observaciones', 'Escribe comentarios claros para que el equipo sepa que corregir.'),
            ],
            'assets/ayuda/docente-materia/03-revisiones-entrega-abierta.png' => [
                $this->detalle('Apartado seleccionado', 'La bandeja muestra las entregas del apartado activo para revisar una a una.'),
                $this->detalle('Entrega recibida', 'Cada fila indica equipo, proyecto, fecha de envio, persona que entrego y estado actual.'),
                $this->detalle('Abrir detalle', 'Al presionar la fila se despliegan evidencias, criterio y formulario de evaluacion.'),
            ],
            'assets/ayuda/docente-materia/03-revisiones-entrega-formulario.png' => [
                $this->detalle('Evidencias entregadas', 'Abre o descarga los archivos antes de calificar.'),
                $this->detalle('Criterio asignado', 'Confirma apartado, ponderacion y version recibida para evaluar lo correcto.'),
                $this->detalle('Resultado y calificacion', 'Selecciona el resultado, escribe la calificacion y guarda la revision.'),
                $this->detalle('Comentarios de seguimiento', 'Despues de guardar la revision puedes agregar indicaciones adicionales para el equipo.'),
            ],
            'assets/ayuda/estudiante/01-panel-principal.png' => [
                $this->detalle('Progreso validado', 'Muestra el porcentaje de apartados aprobados del equipo.'),
                $this->detalle('Estados', 'Distingue sin entregar, pendiente, correccion, rechazado o validado.'),
                $this->detalle('Resumen de guia', 'Indica que apartado sigue y que fecha limite debes cuidar.'),
            ],
            'assets/ayuda/estudiante/02-mi-proyecto.png' => [
                $this->detalle('Datos del proyecto', 'Confirma nombre del proyecto, equipo, estado y periodo.'),
                $this->detalle('Guia integradora', 'Indica la guia que debera seguir tu equipo.'),
                $this->detalle('Integrantes', 'Revisa que todos los integrantes aparezcan correctamente.'),
                $this->detalle('Datos incorrectos', 'Si algo no coincide, reporta la captura con el responsable de seguimiento.'),
            ],
            'assets/ayuda/estudiante/03-entregas.png' => [
                $this->detalle('Apartado', 'Selecciona el apartado que corresponde a la evidencia que vas a entregar.'),
                $this->detalle('Fecha limite', 'Revisa la fecha antes de cargar archivos para evitar entregas fuera de tiempo.'),
                $this->detalle('Estado', 'Sin entrega significa que falta enviar evidencia; pendiente significa que ya fue enviada y espera revision.'),
                $this->detalle('Comentarios', 'Cuando exista revision, lee las observaciones antes de enviar una nueva version.'),
            ],
            'assets/ayuda/estudiante/03-entregas-detalle-apartado.png' => [
                $this->detalle('Detalle del apartado', 'Al abrir un apartado se muestra si puedes enviar archivos, consultar historial o atender una observacion.'),
                $this->detalle('Aviso de plazo', 'Si la fecha termino, la evidencia queda visible para consulta, pero ya no se puede reemplazar.'),
            ],
            'assets/ayuda/estudiante/04-codigo-repositorio.png' => [
                $this->detalle('Repositorio', 'Captura la URL completa del repositorio del equipo.'),
                $this->detalle('Version', 'Indica la version o avance que estas enviando para identificar la entrega.'),
                $this->detalle('Archivos tecnicos', 'Adjunta comprimidos, scripts, manuales o evidencias tecnicas cuando sean solicitadas.'),
                $this->detalle('Aviso rojo', 'Si aparece una advertencia, atiendela antes de la fecha limite.'),
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
                $this->marca('Ayuda', 'Abre esta guia sin iniciar sesion.', 76, 17, 71, 17, 65, 16, '0%', '-50%', '8.5rem', 'green'),
                $this->marca('Matricula', 'Campo para tu matricula.', 57, 49, 66, 49, 80, 52, '0%', '-50%', '8.5rem', 'blue'),
                $this->marca('Contrasena', 'Clave de acceso.', 96, 59, 91, 59, 80, 61, '-100%', '-50%', '8rem', 'amber'),
                $this->marca('Ingresar', 'Abre tu panel.', 96, 78, 91, 78, 80, 70, '-100%', '-50%', '8rem', 'green'),
            ],
            'assets/ayuda/acceso/02-recuperar-contrasena.png' => [
                $this->marca('Ayuda', 'Consulta esta guia sin salir del formulario.', 16, 24, 28, 24, 39, 24, '0%', '-50%', '8.5rem', 'green'),
                $this->marca('Correo', 'Usa el correo registrado para solicitar recuperacion.', 22, 60, 32, 60, 50, 61, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Enviar', 'Solicita instrucciones de recuperacion.', 80, 66, 70, 67, 50, 69, '-100%', '-50%', '9rem', 'green'),
                $this->marca('Volver', 'Regresa al inicio de sesion.', 82, 86, 72, 82, 50, 77, '-100%', '-50%', '8rem', 'amber'),
            ],
            'assets/ayuda/acceso/03-actualizar-contrasena.png' => [
                $this->marca('Ayuda', 'Abre esta guia en otra ventana.', 16, 15, 28, 15, 39, 15, '0%', '-50%', '8.5rem', 'green'),
                $this->marca('Temporal', 'Escribe la contrasena que recibiste.', 22, 52, 32, 52, 50, 52, '0%', '-50%', '9rem', 'blue'),
                $this->marca('Nueva', 'Captura una contrasena segura.', 80, 64, 70, 64, 50, 64, '-100%', '-50%', '8.5rem', 'amber'),
                $this->marca('Confirmar', 'Repite la nueva contrasena.', 22, 77, 32, 77, 50, 77, '0%', '-50%', '9rem', 'blue'),
                $this->marca('Actualizar', 'Guarda el cambio para continuar.', 80, 85, 70, 85, 50, 85, '-100%', '-50%', '8.5rem', 'green'),
            ],
            'assets/ayuda/coordinacion/01-panel-principal.png' => [
                $this->marca('Menu principal', 'Estas son las opciones disponibles para tu cuenta.', 2, 26, 12, 26, 9, 25, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Boton ayuda', 'Desde aqui abres esta guia por apartado.', 86, 15, 88, 14, 88.5, 4.5, '-50%', '0%', '9.5rem', 'green'),
                $this->marca('Alertas', 'Revisa estos avisos antes de operar el periodo.', 82, 56, 76, 56, 73, 58, '-100%', '-50%', '10.5rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/02-jerarquia-proyectos.png' => [
                $this->marca('Filtros', 'Ubica periodo, carrera o grupo antes de asignar.', 24, 25, 34, 25, 36, 28, '0%', '-50%', '10rem', 'amber'),
                $this->marca('Asignacion', 'Selecciona responsable y materia principal por grupo.', 55, 27, 57, 31, 63, 39, '-50%', '-50%', '10.5rem', 'blue'),
                $this->marca('Guardar', 'Cada fila se guarda con su boton de asignacion.', 94, 44, 89, 44, 91, 39, '-100%', '-50%', '9.5rem', 'green'),
            ],
            'assets/ayuda/coordinacion/03-periodos.png' => [
                $this->marca('Resumen', 'Estas tarjetas muestran el estado actual de periodos.', 25, 35, 34, 35, 37, 38, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Nuevo periodo', 'Usa este boton para registrar un ciclo nuevo.', 93, 46, 89, 46, 91, 53, '-100%', '-50%', '10rem', 'green'),
                $this->marca('Historial', 'Desde cada fila puedes abrir su estructura.', 82, 78, 78, 78, 91, 77, '-100%', '-50%', '9.5rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/03-periodos-formulario.png' => [
                $this->marca('Nuevo periodo', 'El boton despliega el formulario de captura.', 91, 42, 87, 42, 90, 44, '-100%', '-50%', '9.5rem', 'green'),
                $this->marca('Datos basicos', 'Captura nombre, inicio y cierre del ciclo.', 26, 52, 35, 55, 43, 60, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Estado', 'Define si queda en borrador, activo o cerrado.', 26, 91, 35, 83, 30, 69, '0%', '-100%', '9rem', 'amber'),
                $this->marca('Guardar', 'Registra el periodo con los datos completos.', 66, 76, 57, 76, 46, 73, '-50%', '-50%', '9rem', 'green'),
            ],
            'assets/ayuda/coordinacion/04-carreras-grupos.png' => [
                $this->marca('Nueva carrera', 'Registra primero las carreras disponibles.', 26, 63, 34, 63, 31, 72, '0%', '-50%', '10rem', 'green'),
                $this->marca('Nuevo grupo', 'Despues crea grupos asociados al periodo.', 58, 63, 60, 63, 55, 72, '-50%', '-50%', '10rem', 'blue'),
                $this->marca('Responsables', 'Asocia participantes academicos por carrera.', 92, 63, 86, 63, 84, 72, '-100%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/05-usuarios.png' => [
                $this->marca('Tipo de cuenta', 'Cambia entre listas y registros desde estas pestanas.', 25, 37, 34, 37, 29, 42, '0%', '-50%', '10.5rem', 'blue'),
                $this->marca('Filtros', 'Ajusta periodo, carrera o grupo para encontrar registros.', 74, 45, 69, 45, 60, 50, '-100%', '-50%', '10rem', 'amber'),
                $this->marca('Acciones', 'Agrega listas o guarda la vista previa desde estos botones.', 28, 92, 38, 92, 27, 90, '0%', '-100%', '11rem', 'green'),
            ],
            'assets/ayuda/coordinacion/05-usuarios-docentes-formulario.png' => [
                $this->marca('Docentes', 'Esta pestana cambia al formulario de docentes.', 82, 35, 76, 35, 76, 36, '-100%', '-50%', '9.5rem', 'blue'),
                $this->marca('Filas', 'Cada fila corresponde a una cuenta por registrar.', 30, 62, 38, 62, 49, 61, '0%', '-50%', '10rem', 'amber'),
                $this->marca('Tipo y carrera', 'Selecciona tipo de cuenta y carrera antes de guardar.', 91, 61, 84, 61, 78, 61, '-100%', '-50%', '10rem', 'green'),
                $this->marca('Agregar', 'Anade otra fila si necesitas capturar mas personas.', 22, 94, 31, 86, 28, 78, '0%', '-100%', '9.5rem', 'green'),
            ],
            'assets/ayuda/coordinacion/06-asignaturas.png' => [
                $this->marca('Registrar', 'Abre el formulario para crear una asignatura.', 31, 37, 39, 37, 36, 43, '0%', '-50%', '10rem', 'green'),
                $this->marca('Asignar docente', 'Vincula docentes a las materias del periodo.', 62, 37, 63, 37, 63, 43, '-50%', '-50%', '10rem', 'blue'),
                $this->marca('Cambiar ciclo', 'Confirma el periodo antes de revisar la carga.', 93, 52, 88, 52, 90, 53, '-100%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/06-asignaturas-formularios.png' => [
                $this->marca('Catalogo', 'Crea la materia base con carrera, cuatrimestre, nombre y clave.', 25, 59, 35, 59, 33, 60, '0%', '-50%', '11rem', 'blue'),
                $this->marca('Guardar materia', 'Agrega la asignatura al catalogo permanente.', 58, 72, 56, 72, 51, 72, '-50%', '-50%', '10rem', 'green'),
                $this->marca('Asignacion', 'Vincula materia, periodo y docente para el ciclo actual.', 91, 61, 85, 61, 79, 61, '-100%', '-50%', '11rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/07-guias.png' => [
                $this->marca('Crear guia', 'Guarda los datos principales antes de agregar apartados.', 27, 46, 36, 46, 29, 52, '0%', '-50%', '10.5rem', 'green'),
                $this->marca('Apartados', 'Configura orden, fecha limite y ponderacion.', 59, 36, 60, 40, 58, 45, '-50%', '-50%', '10rem', 'blue'),
                $this->marca('Docentes', 'Asigna calificadores a cada apartado publicado.', 92, 39, 86, 39, 86, 44, '-100%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/07-guias-formularios.png' => [
                $this->marca('Guia', 'Completa periodo, asignatura, nombre y version.', 25, 57, 35, 57, 35, 60, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Apartado', 'Define orden, porcentaje, titulo y evidencia requerida.', 91, 47, 84, 50, 80, 58, '-100%', '-50%', '10rem', 'green'),
                $this->marca('Tipo evidencia', 'Marca Documento o Codigo segun lo que deberan entregar.', 91, 82, 84, 78, 78, 76, '-100%', '-100%', '10rem', 'amber'),
            ],
            'assets/ayuda/coordinacion/07-guias-apartados-lista.png' => [
                $this->marca('Detalle', 'Abrir la guia muestra objetivo, competencias y apartados.', 33, 32, 42, 32, 39, 35, '0%', '-50%', '10.5rem', 'blue'),
                $this->marca('Apartados', 'Cada fila indica orden, fecha, ponderacion y descripcion.', 31, 63, 40, 63, 41, 63, '0%', '-50%', '10rem', 'amber'),
                $this->marca('Asignar', 'Usa este boton para sumar docentes que califican.', 92, 64, 86, 64, 88, 64, '-100%', '-50%', '9.5rem', 'green'),
            ],
            'assets/ayuda/docente-lider/01-panel-principal.png' => [
                $this->marca('Indicadores', 'Resume equipos, alumnos, grupos y proyectos.', 28, 36, 36, 36, 39, 39, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Grupos', 'Consulta los grupos del periodo bajo tu alcance.', 79, 69, 74, 69, 74, 78, '-100%', '-50%', '10rem', 'amber'),
                $this->marca('Ayuda', 'Este boton abre las guias disponibles para tu cuenta.', 86, 15, 88, 14, 88.5, 4.5, '-50%', '0%', '10rem', 'green'),
            ],
            'assets/ayuda/docente-lider/02-lista-alumnos.png' => [
                $this->marca('Grupo activo', 'Selecciona el grupo antes de revisar alumnos.', 32, 45, 39, 45, 33, 50, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Busqueda', 'Usa filtros para ubicar matricula o nombre.', 92, 29, 86, 32, 85, 42, '-100%', '-50%', '9.5rem', 'amber'),
                $this->marca('Lista', 'Valida grupo, correo y datos del alumno.', 92, 67, 84, 67, 75, 65, '-100%', '-50%', '9.5rem', 'green'),
            ],
            'assets/ayuda/docente-lider/03-equipos.png' => [
                $this->marca('Nuevo equipo', 'Crea equipos cuando el grupo ya tenga alumnos.', 93, 43, 88, 43, 92, 45, '-100%', '-50%', '10rem', 'green'),
                $this->marca('Grupos', 'Cambia de grupo con estos accesos rapidos.', 43, 57, 49, 57, 43, 62, '-50%', '-50%', '10rem', 'blue'),
                $this->marca('Detalle', 'Abre la informacion del equipo desde esta accion.', 92, 82, 86, 82, 87, 86, '-100%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/docente-lider/03-equipos-nuevo-equipo.png' => [
                $this->marca('Nuevo equipo', 'Al abrirlo aparece este formulario.', 92, 44, 87, 44, 91, 43, '-100%', '-50%', '9rem', 'green'),
                $this->marca('Grupo', 'El equipo se crea dentro del grupo seleccionado.', 28, 55, 36, 55, 42, 55, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Alumnos libres', 'Si no hay alumnos libres, no se puede crear otro equipo.', 33, 78, 42, 78, 59, 78, '0%', '-50%', '11rem', 'amber'),
            ],
            'assets/ayuda/docente-lider/03-equipos-integrantes.png' => [
                $this->marca('Panel abierto', 'Se abre desde Ver informacion del equipo.', 88, 17, 82, 17, 86, 21, '-100%', '-50%', '9.5rem', 'blue'),
                $this->marca('Mismo grupo', 'Solo se asignan alumnos del grupo de este equipo.', 30, 53, 39, 53, 42, 51, '0%', '-50%', '10.5rem', 'green'),
                $this->marca('Retirar', 'Quita al alumno y lo deja disponible para reasignar.', 92, 62, 86, 62, 91, 62, '-100%', '-50%', '10rem', 'rose'),
            ],
            'assets/ayuda/docente-lider/04-proyectos.png' => [
                $this->marca('Grupo', 'Filtra los proyectos por grupo asignado.', 39, 49, 46, 49, 42, 53, '-50%', '-50%', '9.5rem', 'blue'),
                $this->marca('Proyecto', 'Revisa equipo, guia, asesor y estado.', 31, 76, 40, 76, 32, 78, '0%', '-50%', '10rem', 'amber'),
                $this->marca('Administrar', 'Entra a las acciones del proyecto desde aqui.', 92, 75, 86, 75, 88, 77, '-100%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/docente-lider/04-proyectos-administrar.png' => [
                $this->marca('Administrar', 'El panel cambia a Cerrar cuando esta abierto.', 90, 17, 85, 17, 86, 20, '-100%', '-50%', '9rem', 'blue'),
                $this->marca('Docentes', 'Agrega o retira participantes del proyecto.', 31, 48, 40, 48, 39, 48, '0%', '-50%', '10rem', 'green'),
                $this->marca('Asignaturas', 'Vincula materias del mismo programa y cuatrimestre.', 91, 51, 83, 51, 76, 51, '-100%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/docente-lider/05-revision-codigo.png' => [
                $this->marca('Periodo', 'Confirma el periodo visible antes de revisar.', 92, 27, 87, 27, 88, 26, '-100%', '-50%', '9.5rem', 'amber'),
                $this->marca('Bandeja', 'Aqui aparecen repositorios y productos enviados.', 31, 42, 39, 42, 43, 43, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Sin registros', 'Cuando no hay entregas, la vista muestra este aviso.', 79, 56, 73, 56, 55, 51, '-100%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/docente-materia/01-panel-principal.png' => [
                $this->marca('Carga', 'Estas tarjetas resumen tu trabajo de evaluacion.', 29, 35, 37, 35, 39, 39, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Atencion', 'Prioriza entregas vencidas o recibidas sin revisar.', 82, 54, 76, 54, 73, 58, '-100%', '-50%', '10.5rem', 'rose'),
                $this->marca('Apartados', 'Consulta proximos apartados asignados.', 60, 80, 55, 80, 53, 82, '-50%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/docente-materia/02-mis-asignaciones.png' => [
                $this->marca('Periodo', 'Verifica el periodo de asignaciones.', 88, 27, 84, 27, 83, 25, '-100%', '-50%', '9.5rem', 'amber'),
                $this->marca('Resumen', 'Mira cuantos apartados y materias tienes asignadas.', 29, 44, 37, 44, 41, 43, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Apartados', 'Aqui se listaran los apartados por evaluar.', 60, 64, 55, 64, 51, 66, '-50%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/docente-materia/03-revisiones.png' => [
                $this->marca('Filtros', 'Busca por equipo, proyecto, apartado, grupo o estado.', 42, 36, 48, 36, 47, 37, '-50%', '-50%', '10.5rem', 'amber'),
                $this->marca('Apartado', 'Selecciona un apartado para revisar entregas.', 29, 58, 37, 58, 33, 59, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Bandeja', 'Las entregas pendientes apareceran en esta zona.', 83, 67, 76, 67, 55, 74, '-100%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/docente-materia/03-revisiones-entrega-abierta.png' => [
                $this->marca('Apartado activo', 'Selecciona el apartado para ver sus entregas.', 27, 42, 36, 42, 30, 45, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Entrega recibida', 'Revisa equipo, proyecto, fecha y estado.', 91, 64, 84, 66, 75, 84, '-100%', '-50%', '10rem', 'amber'),
                $this->marca('Abrir detalle', 'La fila despliega evidencias y formulario.', 91, 96, 84, 90, 95, 85, '-100%', '-100%', '9.5rem', 'green'),
            ],
            'assets/ayuda/docente-materia/03-revisiones-entrega-formulario.png' => [
                $this->marca('Evidencias', 'Abre o descarga archivos antes de evaluar.', 26, 40, 34, 43, 43, 51, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Criterio', 'Confirma apartado, ponderacion y version.', 91, 47, 84, 47, 78, 51, '-100%', '-50%', '9.5rem', 'amber'),
                $this->marca('Evaluacion', 'Elige resultado, calificacion y observaciones.', 25, 74, 35, 72, 44, 69, '0%', '-50%', '10rem', 'green'),
                $this->marca('Comentarios', 'Agrega seguimiento despues de guardar revision.', 91, 83, 84, 83, 90, 87, '-100%', '-50%', '10rem', 'rose'),
            ],
            'assets/ayuda/estudiante/01-panel-principal.png' => [
                $this->marca('Progreso', 'El porcentaje muestra avance validado del equipo.', 30, 46, 38, 46, 37, 42, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Estados', 'Identifica pendientes, rechazados o validados.', 80, 43, 74, 43, 73, 40, '-100%', '-50%', '10rem', 'amber'),
                $this->marca('Guia', 'Revisa cada apartado y su ultimo estado.', 59, 77, 54, 77, 48, 82, '-50%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/estudiante/02-mi-proyecto.png' => [
                $this->marca('Proyecto', 'Confirma que sea el proyecto de tu equipo.', 32, 39, 39, 39, 34, 39, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Datos clave', 'Aqui aparecen responsable, guia e integrantes.', 39, 62, 47, 62, 44, 60, '-50%', '-50%', '10.5rem', 'amber'),
                $this->marca('Integrantes', 'Valida que todos los integrantes esten correctos.', 82, 82, 75, 82, 70, 82, '-100%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/estudiante/03-entregas.png' => [
                $this->marca('Apartados', 'Selecciona el apartado que vas a revisar o entregar.', 31, 42, 39, 42, 33, 43, '0%', '-50%', '10rem', 'blue'),
                $this->marca('Estado', 'Cada fila indica si ya fue enviada o revisada.', 82, 45, 76, 45, 73, 44, '-100%', '-50%', '10rem', 'amber'),
                $this->marca('Pendientes', 'Atiende primero los apartados sin entrega.', 59, 67, 53, 67, 49, 65, '-50%', '-50%', '10rem', 'green'),
            ],
            'assets/ayuda/estudiante/03-entregas-detalle-apartado.png' => [
                $this->marca('Abrir apartado', 'Al hacer clic se despliega el detalle.', 31, 30, 39, 35, 35, 41, '0%', '-50%', '9.5rem', 'blue'),
                $this->marca('Aviso', 'Lee si el plazo permite enviar o solo consultar.', 30, 60, 38, 56, 42, 50, '0%', '-50%', '9.5rem', 'rose'),
                $this->marca('Estados', 'Revisa ultima entrega y estado antes de abrir otro apartado.', 91, 42, 84, 42, 81, 39, '-100%', '-50%', '10rem', 'amber'),
            ],
            'assets/ayuda/estudiante/04-codigo-repositorio.png' => [
                $this->marca('Producto', 'Aqui se informa el estado del producto final.', 36, 39, 44, 39, 44, 38, '0%', '-50%', '9rem', 'blue'),
                $this->marca('Advertencia', 'Si falta registrar, atiende este aviso antes del cierre.', 94, 47, 88, 47, 60, 43, '-100%', '-50%', '9.5rem', 'rose'),
                $this->marca('Menu', 'Tambien puedes volver a entregas o proyecto desde el lateral.', 3, 42, 12, 42, 8, 42, '0%', '-50%', '10rem', 'green'),
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
            ['pregunta' => 'No puedo entrar al sistema', 'respuesta' => 'Verifica que la matricula y la contrasena sean correctas. Si el problema continua, pide que revisen el estado de tu cuenta.'],
            ['pregunta' => 'No veo una opcion del menu', 'respuesta' => 'El menu muestra las opciones disponibles para tu cuenta. Si falta algo, reporta la pantalla y la accion que necesitas realizar.'],
            ['pregunta' => 'Una pantalla aparece sin registros', 'respuesta' => 'Revisa el periodo activo, filtros aplicados y grupo seleccionado. Algunas vistas solo muestran informacion cuando ya existen equipos, proyectos o entregas.'],
        ];

        $porRol = match ($rol) {
            'coordinacion' => [
                ['pregunta' => 'Que debo configurar primero', 'respuesta' => 'Primero periodo, carreras, grupos y usuarios. Despues asignaturas, responsables de grupo, guias, equipos y proyectos.'],
                ['pregunta' => 'Por que una persona no ve sus opciones', 'respuesta' => 'Debe estar vinculada al grupo, materia o proyecto correspondiente en el periodo activo.'],
            ],
            'docente_lider' => [
                ['pregunta' => 'Por que no puedo crear proyectos', 'respuesta' => 'El equipo debe existir y contar con una guia disponible para el periodo. Revisa tambien que el grupo pertenezca a tu asignacion.'],
                ['pregunta' => 'Puedo mover alumnos entre equipos', 'respuesta' => 'Si tienes permiso sobre el grupo, puedes ajustar integrantes. El sistema evita que un alumno quede activo en dos equipos al mismo tiempo.'],
            ],
            'docente_materia' => [
                ['pregunta' => 'Por que no hay entregas pendientes', 'respuesta' => 'Puede ocurrir cuando ningun equipo ha enviado evidencias o cuando no tienes apartados asignados en la guia del periodo.'],
                ['pregunta' => 'Que resultado debo usar', 'respuesta' => 'Usa aprobada cuando cumple, correccion cuando requiere ajustes y rechazada cuando la evidencia no corresponde o no se puede evaluar.'],
            ],
            default => [
                ['pregunta' => 'Por que no veo mi proyecto', 'respuesta' => 'Tu cuenta debe estar dentro de un equipo activo y ese equipo debe tener un proyecto integrador relacionado.'],
                ['pregunta' => 'Puedo enviar otra version', 'respuesta' => 'Si la fecha limite sigue abierta, puedes enviar una nueva version desde Entregas o Codigo y repositorio.'],
            ],
        };

        return [...$base, ...$porRol];
    }
}
