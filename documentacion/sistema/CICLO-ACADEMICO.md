# Periodos, guías y PDF emitidos

## Periodos

Todo periodo nuevo se guarda como **borrador**. Coordinación puede preparar su estructura y después pulsar **Activar periodo**. Solo puede haber uno activo; esta regla se verifica dentro de una transacción y también mediante una restricción de base de datos.

Al llegar la fecha de fin se muestra un aviso en el panel de coordinación y en el historial de periodos. El sistema no cierra el ciclo automáticamente.

La acción **Revisar pendientes y cerrar periodo** muestra los proyectos sin PDF final y las guías sin publicar, con enlaces para consultarlos. Coordinación debe marcar que ha revisado los pendientes antes de confirmar. El cierre puede realizarse con pendientes; no aprueba trabajos ni genera documentos automáticamente.

El ciclo es **Borrador → Activo → Cerrado**. Un periodo cerrado permanece disponible para consulta y no puede volver a activarse desde estas acciones.

## Guías

Toda guía nueva se guarda como **borrador**. La acción **Publicar guía** comprueba:

- Al menos un apartado.
- Ponderaciones que suman 100%.
- Tipo de evidencia requerido en cada apartado.
- Evaluadores requeridos y activos.
- Un docente líder requerido para los apartados de código.
- Evaluador configurado para cada materia que requiere firma.

Los borradores no se pueden asignar a proyectos. Se pueden publicar guías para un periodo en preparación, pero no para periodos cerrados.

Cuando se cierra el periodo, sus guías publicadas pasan a **cerradas**. Una guía que abarca varios ciclos se cierra al cerrar su **periodo final**. Las guías que nunca se publicaron conservan la etiqueta borrador dentro del historial cerrado y ya no pueden publicarse ni modificarse.

Las modificaciones a la estructura académica, las entregas y las evaluaciones se rechazan si pertenecen a un periodo o guía cerrados. Una prórroga del líder habilita únicamente las entregas y revisiones de sus apartados y equipo seleccionados. La consulta de evidencias y PDF emitidos permanece disponible para los usuarios autorizados.

## Cierres pendientes y prórrogas

### Estado de las guías para el docente líder

El menú **Estado de las guías**, también accesible desde el dashboard y su búsqueda, muestra todas las guías asignadas a proyectos de los grupos que lidera el docente. Incluye periodos anteriores y permite filtrar por periodo, grupo, estado y nombre de guía/proyecto/equipo. Una misma guía se agrupa con sus equipos y el estado de cada uno.

El resumen identifica **finalizada con PDF**, **lista para generar PDF**, **requiere decisión de cierre**, **prórroga activa**, **prórroga vencida**, **cerrada con pendientes**, **en curso con pendientes** y **guía en borrador**. Se muestran entregas, firmas requeridas y aprobaciones vigentes por apartado, siempre sobre la última versión. Los motivos distinguen falta de entrega, correcciones, aprobación firmada o configuración del evaluador. No depende de comentarios ni de que el scheduler haya registrado previamente un caso.

Una guía cerrada como estructura no implica que todos sus equipos hayan finalizado. Los cierres completos con PDF conservan su estado histórico; si los datos actuales cambiaron después, se indica y se ofrece el PDF original archivado. La vista no expone imágenes de firma.

La consulta es de solo lectura. Si el periodo ya terminó y aún no se registró el caso, **Revisar cierre y prórroga** lo prepara mediante POST autorizado y abre la decisión habitual. Los demás enlaces permiten consultar el formato, descargar el PDF y revisar el historial de cierre. Todos los filtros y acciones vuelven a comprobar los grupos del líder.

### Decisiones de cierre

Al finalizar la fecha del periodo final, al cerrarlo manualmente o al vencer una prórroga, `cierres:revisar` detecta los proyectos en proceso sin formato final completo. El aviso por correo va exclusivamente al docente líder responsable del grupo y enumera los apartados con entregas faltantes, correcciones o aprobaciones firmadas faltantes. Si todas las entregas y firmas están completas, indica que falta emitir el PDF. Un equipo sin entrega no aparece como si todos sus docentes hubieran omitido firmar.

El líder encuentra **Cierres y prórrogas** en su menú y en la búsqueda del dashboard, incluso cuando cambia el periodo activo. Las decisiones son por proyecto/equipo, porque una guía puede compartirse entre varios grupos:

- **Cerrar en su estado actual:** requiere motivo y confirmación; guarda los pendientes, responsable y fecha en el historial. Bloquea nuevas entregas y revisiones. No inventa firmas ni genera un PDF final que aparente estar completo.
- **Dar una prórroga:** requiere motivo, fecha/hora futura y selección de apartados de esa guía. Solo abre entregas, comentarios y revisiones de esos apartados para ese equipo y sus evaluadores autorizados. El periodo y la guía globales conservan su estado cerrado. Una nueva selección sustituye la prórroga vigente.

Las fechas se interpretan en la zona horaria configurada de la aplicación. La fecha original del apartado no se modifica; la prórroga es un plazo específico del proyecto. Las firmas de los apartados reabiertos requieren aprobación del nuevo contexto; las de otros apartados conservan su validez. Al vencer, los apartados se bloquean inmediatamente y se vuelve a avisar al líder si quedan pendientes. Generar el PDF final completo marca el caso resuelto y cancela sus avisos pendientes.

Si el alumno ya participa en otro proyecto, puede seleccionar su proyecto anterior desde **Mi proyecto**, **Entregas** o **Código**. Los formularios y enlaces de correo conservan ese proyecto y vuelven a comprobar su pertenencia al equipo para evitar que una entrega termine en el proyecto nuevo.

Se registra un aviso por líder y ronda de prórroga, con deduplicación, revalidación del responsable antes del envío y reintentos en cola. Las decisiones se serializan en transacciones y los formularios incluyen la ronda para rechazar decisiones enviadas desde páginas antiguas.

El scheduler ejecuta `cierres:revisar` cada minuto. Requiere los mismos worker y SMTP que las asignaciones. En desarrollo `MAIL_MAILER=log` conserva los correos en el log sin enviarlos a una bandeja. Para limitar una revisión de demostración: `php artisan cierres:revisar --docente=DEMO-LIDER-01`.

## PDF emitidos

Al generar un PDF final se almacena el **archivo PDF completo**, cifrado, con su huella de integridad. La descarga recupera los bytes originales guardados y verifica su SHA-256; no reconstruye el documento con los datos actuales.

Una emisión existente no se sobrescribe. Si se cumplen los requisitos para otra emisión con datos distintos, se agrega otro registro al historial. El modelo también impide actualizar o eliminar una emisión mediante las operaciones habituales de la aplicación.

El apartado **Borrador con los datos actuales** sí se vuelve a generar para mostrar el estado actual del proyecto. Está identificado por separado de **Documentos emitidos**, cuyos archivos conservan los datos y firmas originales.

La migración del ciclo adapta a cerradas las guías publicadas cuyo periodo final ya estaba cerrado. No modifica ningún PDF guardado ni activa o cierra periodos existentes.
