# Cambios de esta etapa

## Acceso

1. El alta individual y la importación de alumnos asignan una contraseña aleatoria normal. Se eliminó el cambio obligatorio por contraseña temporal.
2. Recuperación por código de correo dentro del sistema: envío, validación, cambio y regreso al login. Código de ocho dígitos, vigencia de diez minutos y reenvío cada tres minutos, con control en servidor.
3. Protección contra reutilización de códigos, campos residuales al regresar, caché de las vistas de acceso y repetición de POST. Invalidación de sesiones previas tras recuperar la contraseña. No se añadió bloqueo por intentos fallidos de login.

## Docentes y alumnos

4. Registro de firma por PNG/JPG o dibujo JavaScript, confirmación con contraseña, normalización y cifrado.
5. Firma adjunta solo tras aprobación y autorización del docente para una versión y contexto específicos. Cambios posteriores en perfil no sustituyen aprobaciones emitidas; una nueva entrega requiere revisión nueva.
6. Repositorio, URL HTTPS opcional de trabajo alojado y aplicación/instalador o comprimidos. Permisos iguales a los de revisión de código de la materia líder; descargas privadas e iframe aislado con apertura externa. Se descartó ejecutar Docker de repositorios de alumnos.
7. Avisos de asignaciones, cambios y recordatorios por correo; cola, deduplicación y tareas programadas. El modo local registra correos, sin enviarlos a un proveedor real.
8. Búsqueda inteligente de acciones para coordinación y docente líder, con sinónimos, acentos, fragmentos y errores leves, restringida a su navegación.

## Guías, documentos y cierre

9. Vista previa de la guía y de los cambios del apartado antes de guardar, usando la plantilla institucional del PDF.
10. Periodos creados como borradores, activación con restricción de un único periodo activo y cierre explícito tras revisar pendientes. No se permite crear un periodo ya cerrado.
11. Guías creadas como borradores y publicación validada. El cierre acompaña a su periodo final; la creación no ofrece estado cerrado.
12. PDF final permitido cuando se completan entregas y firmas. Se guardan bytes cifrados, huella, hash y autor: el archivo descargado conserva el contenido emitido y no se reconstruye con datos actuales.
13. Identificación de pendientes al vencer el periodo o una prórroga y aviso al docente líder con motivos: entregas, correcciones, firmas o emisión de PDF.
14. Decisiones del líder: cerrar con constancia de pendientes o dar prórroga para un equipo, apartados y fecha concretos. Historial de decisiones, rondas y expiración; no abre automáticamente toda la guía ni otros equipos.
15. Selección del proyecto histórico del alumno para atender prórrogas conservando los permisos del equipo.
16. Estado de las guías del líder con avance por apartado, firmas vigentes, PDF archivados y estados de cierre; filtros de periodo/grupo/estado/búsqueda, resumen global y paginación de 10/20/40 equipos.
17. Botones de regreso arriba y abajo del formato, con ruta interna validada y conservación de filtros/página. Se añadieron orígenes para proyectos, revisiones, código, cierres y proyecto del alumno.

## Datos, operación y verificación

18. Cargas históricas anteriores para explorar firmas, PDF y prórrogas; sustituidas en la instalación local por una carga integral con nombres normales, sin etiquetas en los registros.
19. Nueva carga predeterminada: dos carreras, cinco periodos, veinte grupos, 325 usuarios, 104 equipos, 84 proyectos, 24 guías y 24 PDF. Incluye docentes con/sin firma, usuarios inactivos, alumnos libres, equipos vacíos, versiones, comentarios y paquetes de escritorio de práctica.
20. Referencias a repositorios públicos del propietario: comedor, e-support-system, equipo_dinamita y universidad-. No se atribuye a esos repositorios el contenido sintético de las evidencias o paquetes.
21. Comando de reinicio local con respaldo privado de registros, archivos y clave de cifrado, nombre exacto de base, transacción y restricción de entorno. Se conserva el historial de migraciones.
22. Docker con servicios app/base/cola/programador y pruebas aisladas en SQLite; almacenamiento y clave persistentes. Correcciones de finales de línea del arranque, zona horaria, modo de pruebas y exclusión de caché/mantenimiento/archivos privados del contexto de construcción.
23. README actualizado con funciones, accesos actuales, carga de datos, correo, comandos locales y Docker, respaldos y límites reales de las firmas y vistas integradas.
24. Pruebas de permisos, recuperación, firma por contexto, historial inmutable, ciclos, prórrogas, búsqueda, paginación, regreso y carga/reinicio de registros. Construcción de recursos y revisión visual de PDF.

El bloqueo de capturas de pantalla no es técnicamente garantizable: se protege el acceso y la autorización de la firma y se marca su contexto en el documento. La entrega real de correo requiere configurar un proveedor SMTP.
