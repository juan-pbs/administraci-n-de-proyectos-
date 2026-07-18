# Contexto Tecnico

## Framework

Laravel 13 con PHP 8.3.

## Paquetes Instalados

Produccion:

- `barryvdh/laravel-dompdf`
- `maatwebsite/excel`
- `phpoffice/phpword`
- `laravel/tinker`

Desarrollo:

- `laravel-lang/lang`
- `laravel/boost`
- `pestphp/pest`
- `pestphp/pest-plugin-laravel`
- `laravel/pint`

## Rutas Principales

```text
/login
/forgot-password
/actualizar-contrasena
/dashboard
/modulos/periodos
/modulos/carreras-grupos
/modulos/usuarios
/modulos/asignaturas
/modulos/guias
/modulos/equipos
/modulos/proyectos
/modulos/jerarquia-proyectos
```

## Archivos de Rutas

```text
routes/web.php
routes/modulos.php
```

## Controladores Principales

```text
app/Http/Controllers/Auth/AuthenticatedSessionController.php
app/Http/Controllers/Autenticacion/ControladorActualizacionContrasena.php
app/Http/Controllers/DashboardController.php
app/Http/Controllers/Modulos/ControladorPeriodos.php
app/Http/Controllers/Modulos/ControladorCarrerasGrupos.php
app/Http/Controllers/Modulos/ControladorUsuarios.php
app/Http/Controllers/Modulos/ControladorAsignaturas.php
app/Http/Controllers/Modulos/ControladorGuias.php
app/Http/Controllers/Modulos/ControladorEquipos.php
app/Http/Controllers/Modulos/ControladorProyectos.php
app/Http/Controllers/Modulos/ControladorJerarquiaProyectos.php
```

## Modelos Principales

```text
Role
User
Periodo
Carrera
GrupoAcademico
Asignatura
GuiaIntegradora
ApartadoGuia
FirmaApartadoGuia
Equipo
Proyecto
EncargoProyecto
```

## Migraciones Relevantes

```text
database/migrations/0001_01_01_000000_create_users_table.php
database/migrations/2026_07_08_000001_add_matricula_to_users_table.php
database/migrations/2026_07_11_000001_create_project_management_schema.php
database/migrations/2026_07_18_000001_create_project_role_assignments.php
```

La migracion del 18 de julio agrega:

- Roles `encargado_proyectos`, `lider_proyecto` y `docente_materia`.
- Tabla `encargos_proyecto`, con alcance por encargado, periodo, carrera y cuatrimestre.
- `lider_proyecto_id` y `asignatura_lider_id` en grupos academicos.
- `numero` y `contexto_proyecto` en equipos.

## Reglas de Autorizacion

- Direccion asigna encargados de proyectos.
- El encargado asigna lideres, materia lider, docentes de materia y materias evaluadoras dentro de su alcance.
- El lider carga alumnos y administra equipos solamente en sus grupos.
- El docente de materia consulta y revisa proyectos donde participa como docente o responsable de una asignatura.
- El alumno conserva el flujo de entregas de su equipo.

## Seeder

```text
database/seeders/DatabaseSeeder.php
```

Genera datos universitarios de prueba:

- Roles.
- Periodos.
- Carreras.
- Grupos.
- Asignaturas.
- Usuarios.
- Docentes por carrera.
- Alumnos.
- Guias.
- Equipos.
- Proyectos.

## Plantillas Excel

Origen:

```text
tools/hojas_calculo/generar_plantillas_alumnos.mjs
```

Salida:

```text
outputs/plantillas_carga_alumnos/
public/plantillas/
```

## Recursos UTVM

```text
public/assets/utvm/
```

Contiene logos, favicon e imagenes usadas en login.

## Validacion

Comandos usados durante el trabajo:

```bash
php artisan test
npm run build
```

Resultado:

```text
14 pruebas aprobadas, 55 aserciones
Build correcto
```
