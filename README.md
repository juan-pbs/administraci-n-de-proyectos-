# Sistema Web de Administracion de Proyectos Integradores

Proyecto Laravel para administrar proyectos integradores de la UTVM. El sistema centraliza periodos academicos, carreras, grupos, usuarios, guias integradoras, equipos, proyectos, asesores, evaluadores, evidencias y documentacion final.

## Estado Actual

Este repositorio contiene una primera base funcional del sistema:

- Login institucional con imagenes UTVM, favicon, recuperacion de contrasena y acceso por matricula.
- Cambio obligatorio de contrasena en el primer inicio de sesion para usuarios nuevos.
- Roles reducidos a tres perfiles principales:
  - Direccion / Coordinacion.
  - Docente / Asesor.
  - Estudiante / Equipo.
- Dashboard inicial por rol.
- Modulos front-end y base funcional para:
  - Periodos.
  - Carreras y grupos.
  - Usuarios, separados en alumnos y docentes.
  - Asignaturas.
  - Guias integradoras.
  - Equipos.
  - Proyectos.
  - Reportes, notificaciones, bitacora y respaldos como vistas base.
- Migraciones con nombres y columnas en espanol.
- Seeders con datos de ejemplo de universidad:
  - 3 carreras.
  - 5 grados.
  - 3 a 4 grupos por grado.
  - Grupos de 30 alumnos.
  - Equipos de 6 alumnos.
  - Docentes por carrera.
  - Proyectos, asesores y evaluadores de ejemplo.
- Filtros, busqueda, scroll interno y paginacion en tablas extensas de carreras/grupos y usuarios.
- Plantillas Excel para carga masiva de alumnos:
  - Sin equipos.
  - Con equipos.
- Dependencias instaladas para manejar Excel, Word y PDF.
- Idioma espanol agregado para validaciones, paginacion y mensajes del sistema.
- Documentacion entregable ordenada con ilustraciones, logos y recursos.

## Flujo General del Sistema

1. Direccion / Coordinacion administra periodos academicos, carreras, grupos, usuarios, asignaturas y guias.
2. Los alumnos se organizan por carrera, grado y grupo.
3. Los alumnos pueden cargarse de forma individual o mediante plantilla Excel.
4. El sistema permite formar equipos de 6 alumnos, ya sea por asignacion previa desde Excel o por asignacion manual.
5. Los docentes pertenecen a una carrera principal, pero pueden participar como asesores o evaluadores en diferentes proyectos.
6. Una guia integradora define apartados, fechas de entrega, asignaturas participantes y firmas requeridas.
7. Cada proyecto se asocia a un equipo, una guia, docentes asesores/evaluadores y asignaturas.
8. Los estudiantes capturan avances, suben evidencias y productos de software.
9. Los docentes validan entregables con checkbox, observaciones y calificaciones por apartado.
10. El sistema prepara la base para generar documentos finales en PDF y Word.

## Requisitos

- PHP 8.3 o superior.
- Composer.
- Node.js y npm.
- MariaDB o MySQL.
- Servidor local compatible con Laravel, por ejemplo Herd o Laragon.

## Dependencias Principales

Backend:

- Laravel 13.
- Laravel Tinker.
- Laravel DOMPDF.
- Maatwebsite Excel.
- PHPWord.

Desarrollo y pruebas:

- Pest.
- Laravel Lang.
- Laravel Boost.
- Vite.
- Tailwind CSS.

## Instalacion en Otra Computadora

Clonar el repositorio:

```bash
git clone https://github.com/juan-pbs/administraci-n-de-proyectos-.git
cd administraci-n-de-proyectos-
```

Instalar dependencias:

```bash
composer install
npm install
```

Crear archivo de entorno:

```bash
copy .env.example .env
php artisan key:generate
```

Configurar la base de datos en `.env`.

Ejemplo para MariaDB/MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=administracion_proyectos
DB_USERNAME=root
DB_PASSWORD=
```

Ejecutar migraciones y datos de ejemplo:

```bash
php artisan migrate:fresh --seed
```

Compilar assets:

```bash
npm run build
```

Levantar el sistema:

```bash
php artisan serve
```

URL local:

```text
http://127.0.0.1:8000/login
```

## Usuarios de Prueba

Todos los usuarios sembrados usan la contrasena:

```text
password
```

Usuario Direccion / Coordinacion:

```text
Matricula: 20260001
Correo: coordinacion@utvm.edu.mx
Contrasena: password
```

Docentes:

```text
Matricula: DOC-TI-01, DOC-MECA-01, DOC-ADM-01
Contrasena: password
```

Alumnos:

```text
Matricula: 202600001 en adelante
Contrasena: password
```

## Estructura Relevante

```text
app/
  Correos/
  Http/Controllers/
    Autenticacion/
    Auth/
    Modulos/
  Models/
  Soporte/

database/
  migrations/
  seeders/

documentacion/
  entrega_ordenada/
  conversaciones_codex/

public/
  assets/utvm/
  plantillas/

resources/views/
  autenticacion/
  auth/
  components/
  correos/
  modulos/

routes/
  web.php
  modulos.php

tools/
  hojas_calculo/
```

## Modulos Implementados

### Autenticacion

- Login con matricula y contrasena.
- Correo como dato de recuperacion.
- Vista de "olvide mi contrasena".
- Envio de contrasena temporal al registrar usuarios.
- Redireccion obligatoria a actualizacion de contrasena en primer inicio.

### Control Academico

- Periodos academicos.
- Carreras y grupos.
- Usuarios:
  - Alumnos.
  - Docentes / asesores.
  - Direccion / Coordinacion.
- Asignaturas.

### Gestion de Proyectos

- Guias integradoras.
- Apartados de guia.
- Asignaturas que contribuyen por apartado.
- Firmas por apartado.
- Equipos.
- Asignacion y retiro de alumnos.
- Asignacion y retiro de asesores.
- Proyectos.
- Asignacion de docentes asesores/evaluadores.
- Asignacion de asignaturas participantes.

### Documentacion y Plantillas

- Documento entregable ordenado en `documentacion/entrega_ordenada/`.
- Ilustraciones del documento en `documentacion/entrega_ordenada/ilustraciones/`.
- Plantillas Excel en:
  - `outputs/plantillas_carga_alumnos/`
  - `public/plantillas/`

## Comandos Utiles

Pruebas:

```bash
php artisan test
```

Compilar estilos y scripts:

```bash
npm run build
```

Servidor de desarrollo:

```bash
php artisan serve
```

Regenerar base de datos con datos de ejemplo:

```bash
php artisan migrate:fresh --seed
```

## Notas de Desarrollo

- El proyecto esta en desarrollo activo.
- La base de datos usa nombres en espanol para facilitar la relacion con la documentacion.
- Los docentes no se cargan por lista masiva; solo los alumnos tienen carga por Excel.
- Los docentes pueden ser asesores de uno o varios equipos y tambien evaluadores de apartados sin ser asesores.
- Los equipos estan definidos con 6 alumnos.
- Las tablas largas ya cuentan con filtros, busqueda, scroll interno y paginacion.
- Las conversaciones y decisiones de Codex se resumen en `documentacion/conversaciones_codex/`.

## Ultima Validacion

Antes de este README se validaron:

```bash
php artisan test
npm run build
```

Resultado esperado:

```text
14 tests passed
Build correcto con Vite
```
