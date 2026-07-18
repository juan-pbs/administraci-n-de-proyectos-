# Bitacora Cronologica de Trabajo

## 1. Documentacion Inicial

Se revisaron archivos Word previos y se pidio ordenar el documento siguiendo una guia proporcionada por imagen.

Se limpio el espacio de trabajo y se conservaron recursos importantes.

## 2. Modelo Entidad-Relacion

Se pidio que el modelo entidad-relacion fuera similar a la referencia visual.

Ajustes solicitados:

- Evitar que las lineas cruzaran cuadros.
- Evitar burbujas encimadas.
- Evitar atributos fuera del contorno.
- Quitar leyenda de PK.
- Cambiar login de usuarios de correo a matricula.
- Conservar correo para recuperacion de contrasena.
- Conectar correctamente entidades y relaciones.

## 3. Herramientas, Logos y Tablas

Se pidio actualizar:

- Plan de pruebas.
- Herramientas y justificacion.
- Herramientas de comunicacion.
- Imagenes oficiales de herramientas.
- Logo correcto de MariaDB.
- Paleta de colores.

Se agregaron imagenes/logos y se corrigieron estilos de tablas.

## 4. Cronograma

Se aclaro que el cronograma era para la creacion del sistema, no para documentos.

Criterios usados:

- Metodologia Scrum.
- Desarrollo iniciado dos semanas antes del 1 de agosto.
- 70% del avance antes del 1 de agosto.
- Reanudacion el 10 de septiembre.
- Cierre el 20 de noviembre.
- Consideracion de dias festivos y pausa academica.

## 5. Login UTVM

Se pidio mejorar el login usando imagenes de UTVM.

Ajustes realizados:

- Logo UTVM sin fondo.
- Banner institucional.
- Favicon de navegador.
- Eliminacion de cuadros decorativos.
- Correccion de textos.
- Agregar enlace "Olvidaste tu contrasena".
- Cambiar correo por matricula como usuario de acceso.

## 6. Base de Datos y Roles

Se pidio preparar la base de datos con migraciones, usando nombres y variables en espanol.

Se redujeron roles a:

- Direccion / Coordinacion.
- Docente / Asesor.
- Estudiante / Equipo.

Se instalaron paquetes de espanol para mensajes y validaciones.

## 7. Front-End del Sistema

Se genero la parte frontal de modulos principales:

- Periodos.
- Carreras y grupos.
- Usuarios.
- Asignaturas.
- Guias.
- Equipos.
- Proyectos.
- Reportes.
- Notificaciones.
- Bitacora.
- Respaldos.

Tambien se fijo el menu lateral para que no se mueva al desplazar el contenido.

## 8. Funcionalidad con Base de Datos

Se conectaron modulos a datos reales con controladores separados.

Se organizaron:

- Controladores por funcion.
- Vistas por carpeta.
- Rutas en archivo separado.

## 9. Usuarios

Se separo la administracion en:

- Alumnos.
- Docentes / asesores.

Reglas aplicadas:

- Solo alumnos tienen carga masiva por Excel.
- Docentes se registran individualmente.
- La contrasena se genera automaticamente.
- La contrasena se envia por correo.
- Primer inicio obliga a actualizar contrasena.

## 10. Plantillas Excel

Se generaron plantillas para carga de alumnos:

- Plantilla sin equipos.
- Plantilla con equipos.

Se agregaron datos de ejemplo tipo universidad:

- 3 carreras.
- 5 grados.
- 3 a 4 grupos por grado.
- 30 alumnos por grupo.
- Equipos de 6 alumnos.

## 11. Guias Integradoras

Se usaron ejemplos de guias para entender:

- Contenido por apartado.
- Asignaturas que contribuyen.
- Docentes asignados posteriormente.
- Cuatrimestre/periodo.
- Firmas.
- Asesores.
- Posibilidad de cambiar asesores.
- Posibilidad de modificar equipos.

## 12. Tablas Extensas

Se corrigio que las tablas se extendieran demasiado en:

- Carreras y grupos.
- Usuarios.

Se agregaron:

- Busqueda.
- Filtros.
- Paginacion.
- Scroll interno.
- Encabezados fijos.

## 13. Repositorio

Se hizo commit local de todos los archivos sin omitir contenido.

Despues se agrego el remoto de GitHub y se subio la rama `main`.

## 14. Nueva Jerarquia de Docentes

El 18 de julio de 2026 se reemplazo la operacion basada en un unico rol generico de docente por una jerarquia con responsabilidades separadas:

- Encargado de proyectos por periodo, carrera y cuatrimestre.
- Lider de proyecto y materia lider por grupo.
- Docente de materia para revisar la parte asignada.
- Alumno como integrante de equipo.

Se agrego el modulo `Jerarquia de proyectos`, el modelo `EncargoProyecto`, relaciones de lider y materia lider en grupos, y numero/contexto en equipos.

Los permisos del servidor se actualizaron para que:

- Direccion designe encargados.
- El encargado designe lideres y docentes de materia.
- El lider cargue listas y organice solamente sus grupos.
- El docente consulte proyectos donde tiene una asignacion.

La migracion se aplico localmente y la suite termino con 14 pruebas aprobadas y 55 aserciones.
