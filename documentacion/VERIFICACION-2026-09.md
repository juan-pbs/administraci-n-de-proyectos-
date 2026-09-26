# Verificación del 26 de septiembre de 2026

- Pruebas locales: 100 aprobadas, 1120 aserciones.
- Pruebas dentro de Docker: 100 aprobadas, 1120 aserciones; SQLite en memoria y contenedor de pruebas descartable.
- Construcción Docker completa con dependencias PHP, extensiones y recursos Vite; arranque inicial y actualización conservando volúmenes.
- Stack de revisión separado en puerto 8010: app y MariaDB saludables; worker y scheduler en ejecución.
- Programador: `asignaciones:notificar` y `cierres:revisar` cada minuto; ejecución observada en los logs.
- Cola: trabajos de asignación/cierre procesados; correo configurado en modo log, sin entrega SMTP real.
- Base local reemplazada con respaldo privado ZIP y transacción. También se verificó el dataset independiente de Docker.
- Ambas cargas: 325 usuarios, cinco periodos, veinte grupos, seis asignaturas, 104 equipos, 84 proyectos, 24 guías, 180 entregas, 284 revisiones y 284 comentarios, ocho firmas de docentes, 24 PDF, 48 registros de código, 244 archivos, 52 casos de cierre y 16 aperturas de apartados por prórroga.
- Verificación de los 24 PDF: firma vigente de cada apartado, cabecera PDF e integridad SHA-256. Revisión visual de formatos histórico y actual, con tabla de contenidos/materias/firmas y paginación institucional.
- Navegador local: acceso de DOC-TI-01, periodo activo correcto y 21 proyectos propios en tres páginas, sin exposición de grupos de otra carrera. La navegación de formato y regreso con filtros también está cubierta por las pruebas.
- Recursos locales construidos y vistas Blade compiladas.

Las URL de trabajos alojados no se inventan: permanecen vacías hasta conocer un despliegue. Los enlaces de repositorio utilizados son referencias públicas de `juan-pbs`. Las evidencias y paquetes de práctica son sintéticos y no se atribuyen a esos repositorios.
