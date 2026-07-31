# Sistema de Administración de Proyectos Integradores

Aplicación web desarrollada con Laravel para organizar el proceso académico de los proyectos integradores de la UTVM. El sistema administra periodos, carreras, grupos, usuarios, asignaturas, guías, equipos, proyectos, entregas y revisiones de acuerdo con el alcance de cada rol.

## Estado del proyecto

El repositorio contiene una versión funcional con:

- Autenticación por matrícula y contraseña.
- Recuperación de acceso y cambio obligatorio de contraseña inicial.
- Navegación, permisos y dashboard diferenciados por rol.
- Operación independiente por periodo académico.
- Carga masiva de alumnos mediante varias listas de Excel.
- Conservación de docentes entre periodos, sin heredar sus asignaturas.
- Gestión de carreras, grupos, asignaturas y materias líderes.
- Configuración de guías integradoras por cuatrimestre.
- Creación de equipos únicamente con alumnos libres del mismo grupo.
- Creación y asignación de proyectos por el docente líder.
- Entregas colaborativas con varios archivos y registro del integrante que entregó.
- Revisión, comentarios, solicitud de correcciones y calificación.
- Registro de repositorios y descarga de archivos comprimidos.
- Filtros asíncronos que actualizan únicamente el apartado consultado.
- Vistas de error personalizadas y autorización de rutas por rol.

Las vistas de notificaciones, bitácora, reportes y respaldos fueron retiradas del sistema.

## Roles

### Coordinación

Integra las funciones de dirección/coordinación y encargado de proyectos.

- Administra periodos, carreras, grupos y usuarios.
- Registra varios docentes desde un mismo formulario.
- Carga varias listas de alumnos y revisa una vista previa antes de confirmar.
- Configura asignaturas y asigna docentes por periodo.
- Define qué asignatura es la materia líder de cada grupo.
- Asigna docentes líderes mediante la materia líder.
- Configura las guías y sus apartados para todos los equipos del mismo cuatrimestre.
- Define qué parte de la guía calificará cada docente.

El dashboard de Coordinación es informativo y no funciona como un conjunto de accesos rápidos.

### Docente líder

Integra las funciones de docente asesor y líder de proyecto.

- Visualiza únicamente los grupos que le fueron asignados.
- Consulta las listas de alumnos, pero no puede crear ni importar alumnos.
- Crea equipos cuando existen alumnos libres en el grupo seleccionado.
- Asigna o retira alumnos respetando que todos pertenezcan al mismo grupo.
- Crea proyectos y los asigna a los equipos.
- Consulta el avance de sus grupos.
- Revisa productos de código solamente cuando su asignatura fue configurada como materia líder.
- Visualiza repositorios, archivos y comprimidos enviados por los alumnos.

El número de un equipo se genera con el consecutivo siguiente dentro de su grupo.

### Docente de materia

- Consulta los apartados de la guía que Coordinación le asignó.
- Visualiza únicamente los equipos que debe revisar.
- Descarga los archivos entregados.
- Agrega comentarios.
- Califica, valida, rechaza o solicita correcciones.
- Consulta entregas pendientes de revisión y actividades vencidas sin avance.

Ser docente líder de un grupo y tener asignada la materia líder son condiciones independientes.

### Estudiante

- Consulta su equipo, integrantes, docente líder, guía y proyecto.
- Visualiza el estado de las entregas de su propio equipo.
- Sube varios archivos por apartado.
- Puede reemplazar una entrega mientras la actividad continúe dentro del plazo.
- Visualiza qué integrante realizó la entrega.
- Consulta entregas validadas, rechazadas y no entregadas.
- Registra el repositorio del proyecto y adjunta archivos de código o comprimidos.

## Flujo académico

1. Coordinación crea o activa el periodo académico.
2. Configura las carreras y los grupos del periodo.
3. Carga las listas de alumnos y registra o conserva a los docentes.
4. Configura las asignaturas y asigna sus docentes para el periodo.
5. Define la materia líder y, mediante ella, el docente responsable de cada grupo.
6. Configura una guía por carrera, cuatrimestre y periodo.
7. Define los apartados, fechas, ponderaciones, evidencias y docentes calificadores.
8. El docente líder organiza equipos con alumnos del mismo grupo.
9. El docente líder crea los proyectos y los asigna a los equipos.
10. Los integrantes realizan entregas y registran el producto de código.
11. Los docentes asignados revisan, comentan, califican o solicitan correcciones.

Al iniciar un periodo nuevo no se heredan alumnos, grupos, asignaciones, guías, equipos ni proyectos. Los docentes permanecen registrados, pero sus materias se configuran nuevamente.

## Carga de alumnos por Excel

La carga principal se realiza en **Usuarios > Alumnos**. Es posible seleccionar varias listas en una sola operación y revisar cada lista completa antes de confirmarla.

Por cada archivo se seleccionan desde el formulario:

- Periodo.
- Carrera.
- Grado.
- Grupo.

La hoja debe llamarse `Carga alumnos` y contener estas columnas:

| CÉDULA | NÓMINA | CORREO |
| --- | --- | --- |
| Matrícula o cédula | Nombre completo | Correo válido |

También se reconocen encabezados equivalentes como `matricula`, `nombre_completo` y `correo_electronico`.

Reglas importantes:

- No incluir filas incompletas.
- No repetir un correo para matrículas diferentes.
- La matrícula se conserva como identificador de texto.
- Cada archivo representa una lista completa de un grupo.
- El docente líder no puede cargar alumnos.
- El sistema admite archivos `.xlsx`, `.xls`, `.csv` y `.txt` de hasta 10 MB por lista.

El repositorio también incluye plantillas históricas en `public/plantillas/`.

## Entregas y archivos

- Cada entrega puede incluir varios archivos.
- Cada archivo comprimido admite hasta 150 MB.
- El servidor debe permitir un tamaño total de solicitud suficiente para cargas múltiples.
- El sistema registra al integrante que realizó o reemplazó la entrega.
- Otro integrante del mismo equipo puede actualizarla mientras el plazo siga vigente.
- Los docentes autorizados pueden visualizar y descargar las evidencias.
- Los archivos y repositorios de código se muestran únicamente al docente cuya materia fue definida como líder.

## Requisitos

- PHP 8.4.1 o superior.
- Composer.
- Node.js y npm.
- MariaDB o MySQL.
- Extensiones de PHP requeridas por Laravel y PhpSpreadsheet.
- Un servidor compatible con Laravel, como Herd, Laragon o `php artisan serve`.

## Ejecución con Docker

Si se usa Docker, no es necesario instalar PHP, Composer, Node.js ni MariaDB en la máquina local. El contenedor construye las dependencias, compila los recursos de Vite, levanta MariaDB, ejecuta las migraciones y carga los datos demostrativos cuando la base está vacía.

Construir y levantar el sistema:

```bash
docker compose up -d --build
```

Abrir el sistema:

```text
http://localhost:8000/login
```

Credenciales demostrativas:

```text
Matrícula: 20260001
Contraseña: password
```

Comandos útiles:

```bash
docker compose logs -f app
docker compose run --rm test
docker compose down
docker compose down -v
```

`docker compose down -v` elimina también la base de datos del contenedor para empezar desde cero.

## Tecnologías principales

- Laravel 13.
- PHP 8.4.
- MariaDB/MySQL.
- Tailwind CSS 4.
- Vite 8.
- Maatwebsite Excel y PhpSpreadsheet.
- Laravel DOMPDF.
- PHPWord.
- Pest 4.

## Instalación

```bash
git clone https://github.com/juan-pbs/administraci-n-de-proyectos-.git
cd administraci-n-de-proyectos-
composer install
npm install
```

Crear el archivo de entorno y la clave:

```bash
copy .env.example .env
php artisan key:generate
```

Configurar la conexión en `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=administracion_proyectos
DB_USERNAME=root
DB_PASSWORD=
```

Preparar la base de datos y los datos demostrativos:

```bash
php artisan migrate:fresh --seed
```

Compilar los recursos:

```bash
npm run build
```

Iniciar el sistema:

```bash
php artisan serve
```

La pantalla de acceso estará disponible en:

```text
http://127.0.0.1:8000/login
```

También puede utilizarse el instalador automatizado:

```bash
composer run setup
```

## Usuarios demostrativos

Los usuarios creados por el seeder utilizan la contraseña:

```text
password
```

Coordinación:

```text
Matrícula: 20260001
Correo: coordinacion@utvm.edu.mx
Contraseña: password
```

Docente líder con información demostrativa:

```text
Matrícula: 20260002
Contraseña: password
```

Otros docentes:

```text
DOC-TI-01
DOC-MECA-01
DOC-ADM-01
```

Estudiante:

```text
Matrícula: 202600001
Contraseña: password
```

Las asignaciones exactas dependen de la base de datos cargada y del periodo activo.

## Estructura relevante

```text
app/
  Correos/
  Http/Controllers/
    Auth/
    Autenticacion/
    Modulos/
  Models/
  Soporte/

database/
  factories/
  migrations/
  seeders/

public/
  assets/utvm/
  plantillas/

resources/
  css/
  js/
  views/
    components/
    correos/
    errors/
    modulos/
      control-academico/
      docente-lider/
      docente-materia/
      estudiante/
      gestion-proyectos/

routes/
  web.php
  modulos.php

tests/
  Feature/
  Unit/
```

## Comandos útiles

Ejecutar pruebas:

```bash
php artisan test
```

Comprobar el estilo de PHP:

```bash
vendor/bin/pint --test
```

Compilar estilos y scripts:

```bash
npm run build
```

Ejecutar servidor, cola y Vite:

```bash
composer run dev
```

Limpiar cachés:

```bash
php artisan optimize:clear
```

Regenerar la base de datos:

```bash
php artisan migrate:fresh --seed
```
