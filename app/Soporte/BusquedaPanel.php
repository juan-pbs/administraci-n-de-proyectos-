<?php

namespace App\Soporte;

use Illuminate\Support\Str;

class BusquedaPanel
{
    public static function disponible(string $rol): bool
    {
        return in_array($rol, ['coordinacion', 'docente_lider'], true);
    }

    public static function buscar(string $rol, string $consulta, ?array $navegacion = null): array
    {
        if (! self::disponible($rol)) {
            return [];
        }
        $visibles = collect($navegacion ?? SistemaInterfaz::navegacionPara($rol))->keyBy('clave');
        $acciones = [];
        foreach (self::catalogo($rol) as [$id, $modulo, $titulo, $descripcion, $palabras]) {
            if (! $visibles->has($modulo)) {
                continue;
            }
            $ruta = $visibles[$modulo]['ruta'];
            if ($id === 'registrar-docentes') {
                $ruta .= (str_contains($ruta, '?') ? '&' : '?').'seccion=docentes';
            }
            $acciones[] = ['id' => $id, 'titulo' => $titulo, 'descripcion' => $descripcion,
                'modulo' => $visibles[$modulo]['titulo'], 'ruta' => $ruta, 'palabras' => $palabras];
        }
        $consulta = self::normalizar($consulta);
        $tokens = self::tokens($consulta);
        if ($consulta === '') {
            return array_map(self::publicar(...), array_slice($acciones, 0, 5));
        }
        if (! $tokens) {
            return [];
        }
        $verbos = ['crear', 'crea', 'registrar', 'registro', 'agregar', 'anadir', 'alta', 'nuevo', 'nueva', 'dar', 'cargar', 'carga', 'importar', 'subir', 'ingresar', 'organizar', 'acomodar', 'distribuir', 'repartir', 'formar', 'agrupar', 'revisar', 'evaluar', 'calificar', 'buscar', 'consultar', 'mostrar', 'poner', 'asignar', 'completar', 'cambiar', 'descargar', 'abrir', 'cerrar', 'guardar', 'actualizar'];
        $conceptos = array_values(array_diff($tokens, $verbos));
        $resultados = [];
        foreach ($acciones as $orden => $accion) {
            $titulo = self::normalizar($accion['titulo']);
            $terminos = self::tokens($accion['titulo'].' '.$accion['palabras'].' '.$accion['descripcion']);
            $coincidencias = 0;
            $fuertes = 0;
            $conceptuales = 0;
            $puntos = 0;
            foreach ($tokens as $token) {
                $mejor = 0;
                foreach ($terminos as $termino) {
                    $mejor = max($mejor, self::similitud($token, $termino));
                }
                if ($mejor > 0) {
                    $coincidencias++;
                    $puntos += $mejor;
                    $fuertes += $mejor >= 5 ? 1 : 0;
                    $conceptuales += in_array($token, $conceptos, true) ? 1 : 0;
                }
            }
            $cobertura = $coincidencias / count($tokens);
            // Evita sugerir cualquier módulo por una única palabra en una frase ajena al sistema.
            if ($coincidencias === 0 || $cobertura < 0.5 || ($conceptos && $conceptuales / count($conceptos) < 0.5)) {
                continue;
            }
            $puntos += $cobertura * 12;
            if (str_contains($titulo, $consulta)) {
                $puntos += 15;
            }
            $resultados[] = ['accion' => self::publicar($accion), 'puntos' => $puntos, 'orden' => $orden, 'fuertes' => $fuertes];
        }
        // Si hay relaciones claras, no mezclar acciones que solo se parecen por una errata.
        if (collect($resultados)->contains(fn ($r) => $r['fuertes'] > 0)) {
            $resultados = array_filter($resultados, fn ($r) => $r['fuertes'] > 0);
        }
        usort($resultados, fn ($a, $b) => ($b['puntos'] <=> $a['puntos']) ?: ($a['orden'] <=> $b['orden']));

        return array_column(array_slice($resultados, 0, 6), 'accion');
    }

    private static function publicar(array $accion): array
    {
        unset($accion['palabras']);

        return $accion;
    }

    private static function normalizar(string $texto): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii($texto))));
    }

    private static function tokens(string $texto): array
    {
        $ignorar = ['a', 'al', 'algo', 'como', 'con', 'cual', 'de', 'del', 'donde', 'el', 'en', 'es', 'esta', 'hacer', 'la', 'las', 'lo', 'los', 'me', 'mi', 'mis', 'necesito', 'ocupo', 'para', 'por', 'puedo', 'que', 'quiero', 'quisiera', 'se', 'tengo', 'un', 'una', 'unos', 'unas', 'ver', 'y'];

        return array_values(array_unique(array_filter(explode(' ', self::normalizar($texto)), fn ($token) => strlen($token) >= 2 && ! in_array($token, $ignorar, true))));
    }

    private static function similitud(string $a, string $b): int
    {
        if ($a === $b) {
            return 8;
        }
        foreach (self::sinonimos() as $grupo) {
            if (in_array($a, $grupo, true) && in_array($b, $grupo, true)) {
                return 7;
            }
        }
        if (strlen($a) >= 3 && str_starts_with($b, $a)) {
            return 5;
        }
        if (min(strlen($a), strlen($b)) >= 4) {
            $limite = min(strlen($a), strlen($b)) >= 7 ? 2 : 1;
            if (abs(strlen($a) - strlen($b)) <= $limite && levenshtein($a, $b) <= $limite) {
                return 4;
            }
        }

        return 0;
    }

    private static function sinonimos(): array
    {
        return [
            ['alumno', 'alumnos', 'estudiante', 'estudiantes', 'escolar', 'escolares'],
            ['docente', 'docentes', 'profesor', 'profesores', 'maestro', 'maestros', 'asesor', 'asesores'],
            ['crear', 'crea', 'registrar', 'registro', 'agregar', 'anadir', 'alta', 'nuevo', 'nueva', 'dar'],
            ['cargar', 'carga', 'importar', 'importacion', 'subir', 'ingresar'],
            ['organizar', 'organizacion', 'acomodar', 'distribuir', 'repartir', 'formar', 'agrupar'],
            ['revisar', 'revision', 'revisiones', 'evaluar', 'evaluacion', 'calificar', 'calificacion', 'calificaciones', 'notas'],
            ['equipo', 'equipos', 'integrantes', 'colaboradores'],
            ['materia', 'materias', 'asignatura', 'asignaturas', 'clase', 'clases'],
            ['guia', 'guias', 'formato', 'formatos', 'plantilla', 'plantillas'],
            ['apartado', 'apartados', 'seccion', 'secciones', 'capitulo', 'capitulos'],
            ['fecha', 'fechas', 'plazo', 'plazos', 'limite', 'vencimiento', 'calendario'],
            ['firma', 'firmas', 'rubrica', 'rubricas', 'firmar'],
            ['repositorio', 'repo', 'repos', 'github', 'gitlab', 'codigo', 'software'],
            ['demostracion', 'demo', 'url', 'enlace', 'link', 'apk', 'instalador', 'aplicacion'],
            ['periodo', 'periodos', 'cuatrimestre', 'cuatrimestres', 'semestre', 'semestres', 'ciclo'],
            ['lider', 'lideres', 'responsable', 'responsables', 'encargado', 'encargados'],
        ];
    }

    private static function catalogo(string $rol): array
    {
        $comunes = [
            ['cargar-alumnos', 'usuarios', 'Cargar listas de alumnos', 'Importa alumnos desde una lista y consulta los estudiantes de tus grupos.', 'subir excel csv archivo matricula inscribir inscripcion registrar estudiantes lista padron'],
        ];
        if ($rol === 'coordinacion') {
            return [...$comunes,
                ['registrar-docentes', 'usuarios', 'Registrar docentes', 'Da de alta profesores y consulta sus cuentas de acceso.', 'nuevo agregar maestro profesor correo usuario acceso contrasena cuenta'],
                ['guias', 'guias', 'Crear y consultar guías', 'Configura el formato, las instrucciones y los requisitos del proyecto integrador.', 'plantilla documento crear formato actividades evidencias requisitos instrucciones'],
                ['apartados', 'guias', 'Configurar apartados y fechas', 'Define capítulos, fechas de entrega, ponderaciones y materias que contribuyen.', 'seccion capitulo plazo vencimiento cambiar fecha porcentaje peso rubrica actividad tarea'],
                ['preview-pdf', 'guias', 'Ver la vista previa del PDF', 'Revisa cómo quedará la guía mientras configuras sus apartados.', 'preview previsualizar pdf visualizar documento formato vista previa impresion imprimir exportar'],
                ['evaluadores', 'guias', 'Asignar evaluadores y firmas', 'Elige los docentes que revisan y firman cada apartado de la guía.', 'calificador profesor maestro asignar evaluacion aprobacion firma asesor responsable'],
                ['grupos', 'carreras-grupos', 'Administrar carreras y grupos', 'Crea grupos académicos y organiza la estructura de cada carrera.', 'salon aula seccion grado alumnos grupo carrera especialidad nuevo crear'],
                ['materias', 'asignaturas', 'Administrar materias y docentes', 'Registra asignaturas y vincula profesores con sus materias por periodo.', 'asignatura clase materia maestro profesor asignar docente carga academica'],
                ['lideres', 'jerarquia-proyectos', 'Asignar docentes líderes', 'Designa al docente responsable y la materia líder de cada grupo.', 'jerarquia encargado coordinador lider responsable grupo asignar maestro profesor'],
                ['periodos', 'periodos', 'Abrir o cerrar periodos', 'Configura el calendario y el estado del ciclo académico.', 'semestre cuatrimestre ciclo periodo nuevo inicio fin calendario cerrar abrir activar'],
            ];
        }

        return [...$comunes,
            ['estado-guias', 'estado-guias', 'Consultar el estado de las guías', 'Identifica guías finalizadas, entregas y firmas pendientes, PDF y equipos que necesitan una prórroga.', 'estado avance progreso seguimiento terminadas finalizadas completas guia pendientes faltan entregas firmas prorroga pdf'],
            ['cierres', 'cierres', 'Resolver cierres y dar prórrogas', 'Consulta entregas y firmas pendientes al terminar el periodo; decide el cierre o amplía el plazo de apartados concretos.', 'prorroga prórrogas extender ampliar tiempo plazo faltan firmas entregas alumnos pendientes cierre cerrar guia'],
            ['equipos', 'equipos', 'Crear y organizar equipos', 'Forma equipos y distribuye a los integrantes dentro de tus grupos.', 'acomodar agrupar repartir estudiantes alumnos grupo integrantes asignar mover equipo'],
            ['proyectos', 'proyectos', 'Asignar y consultar proyectos', 'Consulta los trabajos de tus equipos y completa sus materias y docentes participantes.', 'trabajo proyecto integrador tema contexto asignar profesor asesor docente materia seguimiento'],
            ['revision-codigo', 'revision-codigo', 'Revisar repositorios y demostraciones', 'Evalúa el código, las demos y los instaladores entregados por tus equipos.', 'github gitlab repositorio repo url link enlace demo apk aplicacion software codigo instalador aprobar calificar'],
            ['revisiones', 'revisiones-docente', 'Calificar entregas de apartados', 'Revisa evidencias, registra calificaciones y solicita correcciones.', 'evaluar revisar avances documentos entregas tareas notas calificacion retroalimentacion aprobar corregir correcciones'],
            ['firma', 'mi-firma', 'Registrar mi firma', 'Sube una imagen o dibuja tu firma para autorizarla al aprobar una entrega.', 'poner guardar cargar subir imagen dibujar firmar firma digital rubrica autografa'],
            ['asignaciones', 'asignaciones-docente', 'Consultar mis asignaciones', 'Encuentra los apartados que te corresponde evaluar y sus fechas límite.', 'actividad tarea pendiente plazo fecha apartado asignado responsabilidades materias evaluacion'],
            ['pdf-final', 'proyectos', 'Consultar el formato final del proyecto', 'Abre un proyecto y entra en «Formato final y firmas» para ver pendientes y generar el PDF.', 'pdf descargar documento final firmas aprobaciones guia formato imprimir exportar reporte'],
        ];
    }
}
