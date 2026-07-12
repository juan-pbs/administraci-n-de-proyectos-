# Resumen General del Trabajo con Codex

## Proyecto

Sistema Web de Administracion de Proyectos Integradores para la UTVM.

El sistema busca administrar el ciclo de vida de los proyectos integradores: periodos, carreras, grupos, alumnos, docentes, asignaturas, guias, equipos, proyectos, asesores, evaluadores, entregas, evidencias, productos de software, calificaciones, reportes y generacion documental.

## Documentacion Trabajada

Se trabajo una documentacion entregable con orden de guia:

1. Project Charter.
2. Seleccion de la herramienta de comunicacion.
3. Seleccion y justificacion de la herramienta de gestion de proyecto de TI.
4. Matriz de control de riesgos.
5. Plan de pruebas.
6. Plan de comunicacion y seguimiento.
7. Cronogramas.

Tambien se ajustaron:

- Modelo entidad-relacion.
- Modelo relacional.
- Modelo de casos de uso.
- Mapa de sitio.
- Tablas de herramientas y comunicacion con imagenes/logos.
- Paleta de colores.
- Cronograma basado en Scrum.
- Carpeta de ilustraciones para imagenes usadas en el documento.

La documentacion final organizada esta en:

```text
documentacion/entrega_ordenada/
```

## Decisiones Funcionales

Roles finales:

- Direccion / Coordinacion.
- Docente / Asesor.
- Estudiante / Equipo.

Reglas principales:

- Los usuarios inician sesion con matricula.
- El correo se conserva para recuperacion de contrasena.
- Al crear un usuario, la contrasena temporal se genera automaticamente y se envia por correo.
- En el primer inicio de sesion, el sistema manda al usuario a actualizar su contrasena.
- Los alumnos se organizan por carrera, grado y grupo.
- Los alumnos pueden cargarse desde Excel.
- Los docentes no se cargan por Excel.
- Los docentes pertenecen a una carrera principal.
- Un docente puede ser asesor de varios equipos.
- Un docente puede evaluar apartados sin ser asesor del equipo.
- Los equipos son de 6 alumnos.
- Los proyectos pueden modificar asesores y miembros de equipo.

## Sistema Implementado

Se construyo una base Laravel con:

- Login institucional UTVM.
- Imagenes y favicon de UTVM.
- Dashboard por rol.
- Modulos iniciales de administracion.
- Controladores separados por funcion.
- Rutas separadas en `routes/modulos.php`.
- Vistas organizadas por carpetas.
- Migraciones y modelos principales.
- Seeder grande con datos de ejemplo.
- Plantillas Excel para alumnos.
- Dependencias para Excel, PDF y Word.
- Idioma espanol para errores y mensajes.

## Ajustes Visuales Recientes

Se corrigieron las tablas extensas de:

- Carreras y grupos.
- Usuarios.

Ahora tienen:

- Barra de busqueda.
- Filtros.
- Scroll interno.
- Encabezado fijo.
- Paginacion.
- Consultas paginadas desde base de datos.

## Estado del Repositorio

El repositorio fue inicializado, se agregaron todos los archivos y se subio a GitHub.

Repositorio remoto:

```text
https://github.com/juan-pbs/administraci-n-de-proyectos-.git
```

Rama:

```text
main
```

Primer commit subido:

```text
77dd9c1 Avance sistema administracion proyectos
```
