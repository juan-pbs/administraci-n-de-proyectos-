# Contribuir al repositorio

## Ramas

`main` es la rama estable y la base de cada cambio. No se trabaja directamente en ella. Cada tarea usa una rama corta creada desde la versión más reciente de `origin/main` y se integra mediante una solicitud de cambio (pull request). No se necesita una rama `develop` mientras los cambios se integren de forma continua.

Nombres de ramas, en minúsculas y con palabras separadas por guiones:

| Prefijo | Uso | Ejemplo |
| --- | --- | --- |
| `feature/` | Funcionalidad nueva | `feature/reportes-periodo` |
| `fix/` | Corrección de un defecto | `fix/cierre-prorroga` |
| `docs/` | Documentación | `docs/guia-despliegue` |
| `chore/` | Mantenimiento, dependencias o estructura | `chore/ordenar-documentacion` |
| `codex/` | Trabajo realizado por Codex; el tema debe explicar el cambio | `codex/reorganiza-repositorio` |

Una corrección urgente también sale de `main` mediante `fix/` y regresa por solicitud de cambio. No se reescribe el historial de `main` ni se hacen fusiones directas desde ramas antiguas. Después de integrar una rama, se elimina si ya no contiene trabajo pendiente. Las ramas históricas existentes se revisan antes de eliminarlas; `separa-materia-lider` aún contiene trabajo sin integrar.

## Flujo de trabajo

```bash
git fetch origin
git switch main
git pull --ff-only origin main
git switch -c feature/tema-concreto
# hacer el cambio y sus pruebas
git push -u origin feature/tema-concreto
```

Abre una solicitud de cambio hacia `main` con el alcance, la forma de probarlo y cualquier migración o configuración necesaria. Mantén cada solicitud enfocada en una tarea. Antes de integrarla, revisa el diff, espera la verificación automática y obtiene al menos una revisión de otra persona cuando haya colaboradores disponibles. Se recomienda integrar con *squash merge* y borrar la rama de trabajo al cerrar la solicitud.

## Verificación local

```bash
php artisan test --compact
npm run build
```

Las pruebas usan SQLite en memoria y un límite de 512 MB según `phpunit.xml`, igual que `docker/php.ini`. Para cambios que afecten contenedores o instalación, ejecuta también `docker compose --profile test run --rm test`. No subas `.env`, respaldos, archivos de `storage/` ni salidas de `output/`.

## Configuración recomendada en GitHub

Protege `main` con una regla que exija solicitud de cambio y la verificación `tests` de `.github/workflows/ci.yml`. Bloquea los pushes forzados y la eliminación de `main`. Si el equipo tiene varias personas, exige una aprobación antes de integrar. Las reglas de protección se configuran en GitHub por quien administre el repositorio.
