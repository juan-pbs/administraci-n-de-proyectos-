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
```

## Migraciones Relevantes

```text
database/migrations/0001_01_01_000000_create_users_table.php
database/migrations/2026_07_08_000001_add_matricula_to_users_table.php
database/migrations/2026_07_11_000001_create_project_management_schema.php
```

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
14 pruebas aprobadas
Build correcto
```
