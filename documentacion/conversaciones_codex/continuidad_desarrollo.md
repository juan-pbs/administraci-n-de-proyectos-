# Continuidad de Desarrollo

## Prioridad Recomendada

1. Asignar a los usuarios docentes existentes sus nuevos roles: encargado, lider o docente de materia.
2. Crear encargos reales por periodo, carrera y cuatrimestre desde el modulo de jerarquia.
3. Validar envio real de correos con SMTP institucional o de prueba.
3. Completar CRUD de guias integradoras con edicion y eliminacion segura.
4. Completar asignacion automatica/manual de equipos desde interfaz.
5. Implementar captura de avances por apartado para estudiantes.
6. Implementar carga de evidencias y productos de software.
7. Implementar evaluacion por apartado para docentes.
8. Implementar historial de versiones y entregas.
9. Implementar generacion de documento final PDF y Word.
10. Completar reportes academicos, avance y cumplimiento filtrados por alcance del encargado, lider y docente.

## Pendientes Funcionales

- Afinar la interfaz para ocultar formularios no autorizados, ademas de las validaciones de servidor existentes.
- Agregar asignacion explicita de apartados evaluables por materia y docente.
- Almacenamiento de archivos PDF, Word, Excel e imagenes.
- Carga de codigo fuente, ejecutables, scripts, bases de datos y manuales.
- Registro de repositorios GitHub/GitLab por proyecto.
- Notificaciones por entregas pendientes.
- Notificaciones por observaciones.
- Bitacora real de actividades.
- Respaldo y recuperacion.
- Completar permisos finos en los modulos generales de entregas, reportes y notificaciones.

## Pendientes de Interfaz

- Revisar responsive completo en laptop y pantalla grande.
- Agregar estados vacios mas claros en modulos sin datos.
- Agregar acciones de editar/eliminar donde aplique.
- Agregar confirmaciones para eliminaciones.
- Revisar consistencia visual de botones, formularios y tablas.

## Pendientes de Base de Datos

- Revisar indices para busquedas frecuentes.
- Validar relaciones de entregables, evidencias y productos de software.
- Preparar tabla o mecanismo para historial de versiones.
- Preparar almacenamiento de observaciones por revision.
- Revisar eliminacion logica en entidades criticas.

## Recomendaciones para Otra Computadora

Despues de clonar:

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Credencial base:

```text
Matricula: 20260001
Contrasena: password
Rol: Direccion / Coordinacion
```

Para probar alumnos:

```text
Matricula: 202600001 en adelante
Contrasena: password
```

Para probar docentes:

```text
Matricula: DOC-TI-01, DOC-MECA-01, DOC-ADM-01
Contrasena: password
```

## Cuidado al Continuar

- No crear migraciones duplicadas si el cambio corresponde a una migracion existente en desarrollo.
- Mantener nombres de carpetas, archivos y variables en espanol cuando se creen elementos propios del sistema.
- No reemplazar logos oficiales por imagenes generadas.
- Mantener separacion entre alumnos y docentes.
- Recordar que la carga masiva aplica solo para alumnos.
- No volver a usar `equipos.lider_id` como lider docente; el lider del proyecto se asigna al grupo mediante `lider_proyecto_id`.
- El rol `docente_asesor` es solo de compatibilidad y debe migrarse gradualmente.
- Un encargado puede administrar varios cuatrimestres y puede haber varios encargados en una carrera.
- Un lider nunca debe modificar alumnos o equipos de un grupo ajeno.
