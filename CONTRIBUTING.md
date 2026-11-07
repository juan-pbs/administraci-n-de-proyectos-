# Contribuir al repositorio

## Ramas

`main` es la rama estable y la única excepción al criterio de nombres en español. No se trabaja directamente en ella. Las ramas `seccion/*` reúnen cambios de una sección del sistema; cada error o funcionalidad se desarrolla en una rama corta y se revisa mediante una solicitud de cambio (pull request). No se necesita otra rama general de desarrollo.

### Ramas por sección

| Rama | Alcance principal |
| --- | --- |
| `seccion/autenticacion` | Inicio de sesión, contraseñas, recuperación y permisos de acceso. |
| `seccion/control-academico` | Periodos, carreras, grupos, usuarios y asignaturas. |
| `seccion/gestion-proyectos` | Equipos, proyectos, jerarquía y guías. |
| `seccion/docente-lider` | Estado de guías, revisión del líder, cierres y prórrogas. |
| `seccion/docente-materia` | Asignaciones, entregas asignadas, revisiones y comentarios. |
| `seccion/estudiante` | Selección de proyecto, entregas y código del alumno. |
| `seccion/documentos-firmas` | Archivos, firmas, PDF y documentos finales. |
| `seccion/notificaciones` | Correos, avisos, recordatorios, cola y programación. |
| `seccion/interfaz-ayuda` | Panel, navegación, estilos, componentes visuales y ayuda. |

El área se elige por el comportamiento que cambia, aunque el trabajo toque archivos compartidos. Los cambios que cruzan varias áreas, como infraestructura o migraciones generales, salen de `main` y vuelven directamente a `main`. Las ramas de área se conservan después de integrar sus cambios; se actualizan desde `main` antes de iniciar otro bloque de trabajo.

### Ramas históricas

`historial/*` marca versiones anteriores para consulta y comparación. No se usa como base de nuevas correcciones ni se integra otra vez en `main`, porque esos commits ya forman parte de su historia. Consulta el [catálogo de versiones anteriores a la reorganización](documentacion/historial/RAMAS-2026-11.md). Para corregir un error actual, crea `correccion/<seccion>/<problema>` desde la rama `seccion/*` correspondiente.

Nombres de ramas, en minúsculas y con palabras separadas por guiones:

| Prefijo | Uso | Ejemplo |
| --- | --- | --- |
| `funcionalidad/` | Funcionalidad nueva | `funcionalidad/control-academico/reportes-periodo` |
| `correccion/` | Corrección de un defecto | `correccion/docente-lider/cierre-prorroga` |
| `documentacion/` | Documentación | `documentacion/interfaz-ayuda/guia-acceso` |
| `mantenimiento/` | Dependencias o estructura | `mantenimiento/infraestructura/actualizar-contenedor` |
| `organizacion/` | Organización del repositorio y sus procesos | `organizacion/ramas-por-seccion` |

Una corrección urgente sale de `main` como `correccion/<seccion>/<problema>` y vuelve directamente a `main`; luego se sincroniza la sección afectada. No se reescribe el historial de `main` ni de las ramas compartidas. Las ramas cortas se eliminan después de integrarse. `separa-materia-lider` es una rama histórica con trabajo sin integrar y requiere revisión independiente antes de decidir su destino.

## Flujo de trabajo

```bash
git fetch origin
git switch seccion/control-academico
git pull --ff-only origin seccion/control-academico
git switch -c correccion/control-academico/descripcion-del-error
# hacer el cambio y sus pruebas
git push -u origin correccion/control-academico/descripcion-del-error
```

Abre la solicitud de la rama corta hacia su `seccion/*`, con el alcance, la forma de probarlo y cualquier migración o configuración necesaria. Mantén cada solicitud enfocada en una tarea. Antes de integrarla, revisa el diff y espera la verificación automática. Usa *squash merge* para la rama corta y elimínala al cerrar la solicitud.

Cuando el conjunto de la sección esté listo, actualiza esa rama con `main`, ejecuta las pruebas y abre una solicitud `seccion/*` → `main`. Integra esa solicitud con **merge commit**, para conservar la relación entre ambas ramas. Después, avanza la sección a la nueva punta de `main` con `git merge --ff-only main`. Si hay colaboradores, solicita una revisión antes de integrar. Los cambios transversales y urgentes pueden ir directamente de su rama corta a `main`.

## Verificación local

```bash
php artisan test --compact
npm run build
```

Las pruebas usan SQLite en memoria y un límite de 512 MB según `phpunit.xml`, igual que `docker/php.ini`. Para cambios que afecten contenedores o instalación, ejecuta también `docker compose --profile test run --rm test`. No subas `.env`, respaldos, archivos de `storage/` ni salidas de `output/`.

## Configuración recomendada en GitHub

Protege `main` y `seccion/*` con reglas que exijan solicitud de cambio y la verificación `tests` de `.github/workflows/ci.yml`. Bloquea los pushes forzados y la eliminación de esas ramas. Si el equipo tiene varias personas, exige una aprobación antes de integrar. Las reglas de protección se configuran en GitHub por quien administre el repositorio.
