# Sistema de Administración de Proyectos Integradores

Aplicación Laravel para administrar periodos, carreras, grupos, alumnos, docentes, guías, equipos, entregas, firmas y documentos finales de la UTVM.

## Funciones

- Acceso por matrícula y contraseña, permisos y paneles por rol.
- Alta individual y carga de varias listas de alumnos por Excel, con vista previa y validación antes de importar.
- Contraseña inicial aleatoria normal; cambio voluntario con contraseña actual.
- Recuperación dentro del sistema: correo registrado → código de ocho dígitos → nueva contraseña → login. Código con vigencia de diez minutos y reenvío después de tres minutos, controlado en servidor y con cuenta regresiva. Sin bloqueo por intentos fallidos de login.
- Vistas de acceso sin caché y limpieza de campos al regresar con el navegador. Al recuperar la contraseña se invalidan las sesiones y las cookies de acceso anteriores.
- Búsqueda de acciones para coordinación y docente líder mediante sinónimos, palabras incompletas, acentos y errores leves. Solo ofrece módulos autorizados; no depende de servicios externos de IA.
- Periodos: borrador → activo → cerrado. Solo puede existir un periodo activo. Coordinación revisa pendientes antes de cerrar.
- Guías: borrador → publicada → cerrada. La publicación valida apartados, ponderaciones y evaluadores; el cierre corresponde al fin de su periodo final.
- Entregas colaborativas con varios archivos y versiones, revisiones, comentarios, calificaciones y correcciones.
- Firma docente por imagen PNG/JPG o dibujo en el navegador, confirmada con la contraseña actual. Normalización de imagen, cifrado y aprobación explícita por versión y contexto del proyecto.
- Vista previa de guías y apartados antes de guardar. PDF institucional con contenido, materias contribuyentes y firmas por apartado.
- PDF finales completos guardados como bytes cifrados e inmutables, con hash de integridad. Las descargas recuperan el archivo emitido; cambiar los datos actuales no reconstruye el historial.
- Repositorio y URL HTTPS de trabajo alojado opcional, con instaladores APK/EXE/MSI/DMG/AppImage/DEB y comprimidos. Repositorio, URL y aplicación mantienen los mismos permisos de la materia líder del grupo.
- Avisos de asignaciones y cambios, recordatorios previos al vencimiento y avisos al líder por entregas o firmas pendientes. Cola y programador independientes.
- Cierre con constancia de pendientes o prórroga del líder para un equipo, fecha y apartados concretos. El resto de la guía permanece bloqueado.
- Estado de las guías para el líder: finalizadas, listas para PDF, pendientes, prórrogas activas/vencidas y cierres incompletos. Filtros y paginación de 10, 20 o 40 equipos; resumen global. El formato tiene botones para regresar a la página y filtros de origen.

El formato PDF es una guía institucional de seguimiento. No concatena automáticamente los archivos Word, Excel o PDF entregados por los alumnos. Las firmas visibles en un PDF o una pantalla pueden capturarse; el sistema protege su almacenamiento, acceso y vínculo con la aprobación, pero no puede impedir una captura de pantalla.

## Roles

| Rol | Alcance |
| --- | --- |
| Coordinación | Periodos, carreras, grupos, usuarios, asignaturas, jerarquía y estructura de las guías. |
| Docente líder | Sus grupos y equipos, proyectos, revisión de código de su materia líder, estado de las guías, cierres y prórrogas. |
| Docente de materia | Apartados y equipos asignados, archivos documentales, revisiones, comentarios y firma. |
| Alumno | Su equipo y proyectos, entregas y versiones, observaciones, repositorio y aplicación cuando se soliciten. |

Los docentes permanecen registrados al cambiar de periodo; grupos, alumnos y asignaciones se configuran por ciclo. Los proyectos históricos y sus documentos conservan los permisos correspondientes. Un alumno puede seleccionar un proyecto anterior de su equipo para atender una prórroga.

## Ejecución con Docker

Requiere Docker Engine/Desktop con Compose. No requiere instalar PHP, Composer, Node o MariaDB en el equipo anfitrión.

```bash
git clone https://github.com/juan-pbs/administraci-n-de-proyectos-.git
cd administraci-n-de-proyectos-
docker compose up -d --build
```

Acceso: <http://localhost:8000/login>.

El stack incluye `app`, `db` (MariaDB), `worker` para la cola y `scheduler` para avisos y cierres. El servicio `test` usa SQLite en memoria, sin ejecutar migraciones sobre MariaDB ni cargar datos del entorno de aplicación.

El arranque migra la base y carga `EscenariosAcademicosSeeder` únicamente cuando no existen usuarios. Repetir el arranque conserva los datos. `AUTO_SEED=false` desactiva la carga. Si el entorno es producción, el seeder de práctica no está permitido; configura `AUTO_SEED=false`.

No es obligatorio crear `.env`. Para personalizar la instalación, copia `.env.example`: `APP_PORT`, `DB_FORWARD_PORT`, `APP_URL`, `APP_TIMEZONE`, `MAIL_*` y `DOCKER_DB_*`. Estas últimas configuran las credenciales internas de Docker sin cambiar la conexión local de Herd.

```bash
docker compose ps
docker compose logs -f app worker scheduler
docker compose --profile test run --rm test
docker compose exec app php artisan schedule:list
docker compose down
```

Los volúmenes `mariadb-data` y `app-storage` conservan base y archivos. Cuando no se proporciona `APP_KEY`, el arranque conserva una clave privada en el volumen de almacenamiento y la comparte entre app, worker y scheduler. No cambies la clave en una instalación con firmas o PDF cifrados. `docker compose down -v` elimina los volúmenes: úsalo únicamente si quieres descartar esa instalación y tienes respaldo.

Para validar un stack separado, cambia los puertos y usa un proyecto Compose distinto; no reutilices los volúmenes de otra instalación.

## Ejecución local con Herd/PHP

Requiere PHP 8.4.1 o superior, Composer, Node.js compatible con Vite 8 y MySQL/MariaDB. Las extensiones necesarias están declaradas por Composer e instaladas en el Dockerfile.

```bash
composer install
npm ci
npm run build
```

Crea `.env` desde `.env.example`, configura tu base local y una `APP_KEY` solo si es una instalación nueva. En una base nueva:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
php artisan queue:work --tries=4 --timeout=60
php artisan schedule:work
```

`composer dev` inicia servidor, Vite, cola y programador. Para una instalación existente, respalda antes de migrar; revisa las migraciones pendientes. La migración histórica `2026_08_01_000001_clean_project_database` elimina las antiguas tablas de bitácora y notificaciones.

Las cinco migraciones de septiembre agregan firmas/PDF/avisos, vínculo de firmas con su contexto, recuperación por código, restricción de periodo activo y cierres/prórrogas. No ejecutes `key:generate` para actualizar una instalación existente.

## Datos de práctica y accesos

La carga utiliza nombres, matrículas y títulos normales. Todos los datos personales, evidencias y firmas son ficticios. Los enlaces de repositorio pertenecen a `juan-pbs` y sirven como referencias; los paquetes adjuntos no son versiones compiladas de esos repositorios.

Contenido inicial:

- Dos carreras, cinco periodos (tres cerrados, uno activo y uno borrador), veinte grupos y seis asignaturas.
- 325 usuarios: coordinación, doce docentes y 312 alumnos; docentes con y sin firma y usuarios inactivos.
- 104 equipos, incluyendo equipos sin integrantes, y alumnos libres para probar la organización.
- 24 guías y 84 proyectos con ocho estados distintos; 24 PDF finales.
- Entregas completas, parciales, faltantes, corregidas, rechazadas y versiones anteriores; firmas válidas y pendientes, comentarios, comprimidos y paquetes Debian de práctica.
- Repositorios públicos `comedor`, `e-support-system`, `equipo_dinamita` y `universidad-`. La URL de trabajo alojado queda vacía cuando no existe un despliegue confirmado.

Contraseña de todas las cuentas iniciales: **password**. Solo las cuentas de práctica usan esta contraseña conocida; el alta normal genera una aleatoria.

| Matrícula | Cuenta |
| --- | --- |
| `20260001` | Patricia Hernández, coordinación |
| `DOC-TI-01` | Elena Rivera, líder de 8A de TI |
| `DOC-TI-02` | Carlos Mendoza, líder de 8B de TI |
| `DOC-TI-03` | Tomás Vega, docente con firma |
| `DOC-TI-04` | Lucía Montes, docente con firma |
| `DOC-TI-05` | Ana Torres, docente sin firma |
| `DOC-MEC-01` | Sofía Ortega, líder de 8A de Mecatrónica |
| `DOC-MEC-02` | Daniel Castillo, líder de 8B de Mecatrónica |
| `TI41011` | Alumno del periodo activo, sin entregas |
| `TI41031` | Alumno con correcciones pendientes |
| `TI41051` | Alumno con aprobación de docente sin firma pendiente |
| `TI41071` | Alumno con PDF final |
| `TI31041` | Alumno de periodo cerrado, con prórroga activa |
| `TI31051` | Alumno con prórroga vencida |
| `TI41991` | Alumno libre del grupo 8A de TI |

Como líder, abre **Estado de las guías** con todos los periodos para ver el historial y sus páginas. Selecciona **Mayo - Agosto 2026** para los cierres y prórrogas y **Septiembre - Diciembre 2026** para las entregas actuales. La lista completa de alumnos y docentes está en **Usuarios**.

### Reemplazar la base local con respaldo

```bash
php artisan datos:reiniciar --confirmar=administracion_proyectos
```

En Docker:

```bash
docker compose exec app php artisan datos:reiniciar --confirmar=administracion_proyectos
```

El comando exige entorno `local` o `testing` y el nombre exacto de la base. Guarda un ZIP privado en `storage/app/private/respaldos/` con registros, archivos y la clave necesaria para descifrar el respaldo. Después reemplaza los registros en una transacción y conserva las migraciones. El ZIP contiene información sensible: conserva el acceso privado y no lo publiques. El comando no elimina los archivos antiguos ni envía mensajes durante la carga. En una instalación con cola/programador activos, detén esos servicios antes de reemplazar registros y reinícialos al terminar.

Los seeders históricos anteriores permanecen como fixtures de pruebas; la carga predeterminada ya no los utiliza.

## Correo y tareas programadas

`MAIL_MAILER=log` guarda mensajes localmente y no entrega correo real. Las cuentas de práctica usan `example.invalid`. Para enviar correos configura SMTP y direcciones reales. El alta, la recuperación y los avisos usan esa misma configuración.

```bash
php artisan asignaciones:notificar
php artisan cierres:revisar
php artisan schedule:list
```

Estos comandos se programan cada minuto. La cola envía los avisos y conserva el estado para evitar duplicaciones; revisa `failed_jobs` si el proveedor falla. Los recordatorios se configuran en `config/asignaciones.php`.

## Carga de alumnos y archivos

En **Usuarios > Alumnos**, selecciona varias listas y revisa la vista previa. La hoja `Carga alumnos` debe contener `CÉDULA`, `NÓMINA`, `CORREO`; también se aceptan encabezados equivalentes como `matricula`, `nombre_completo` y `correo_electronico`. Cada archivo representa un grupo con periodo, carrera y grado elegidos. No se permiten matrículas/correos duplicados ni filas incompletas.

Cada entrega admite varios archivos y conserva qué integrante la envió. Las rutas de descarga comprueban permisos. Los instaladores se validan por extensión y estructura básica; no se ejecutan en el servidor. Las URL HTTPS públicas no se consultan desde el servidor; la vista integrada utiliza un iframe aislado y ofrece apertura externa cuando el sitio impide incrustarse. No se ejecutan contenedores de repositorios enviados por alumnos.

Docker permite archivos de hasta 150 MB y solicitudes de 512 MB. Herd utiliza `public/.user.ini`; puede necesitar reiniciar PHP. En `php artisan serve` prevalecen los límites del PHP CLI.

## Pruebas y documentación

```bash
php artisan test --compact
npm run build
docker compose --profile test run --rm test
```

- [Cambios realizados en esta etapa](documentacion/CAMBIOS-2026-09.md)
- [Periodos, guías, cierres, prórrogas y PDF](documentacion/CICLO-ACADEMICO.md)
- [Recorrido por los datos de práctica](documentacion/DATOS-PRACTICA.md)

Tecnologías: Laravel 13, PHP 8.4, MariaDB/MySQL, Tailwind 4, Vite 8, Maatwebsite Excel/PhpSpreadsheet, Dompdf, PHPWord y Pest 4.

`.env`, respaldos, archivos privados, salidas de revisión y dependencias locales están excluidos de Git y del contexto de Docker. La exclusión de `.env` no borra su contenido de commits antiguos.
