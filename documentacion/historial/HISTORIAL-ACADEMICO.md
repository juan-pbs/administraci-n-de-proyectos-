# Historial académico local

Carga adicional para explorar el sistema después de varios ciclos de uso. Las cuentas, evidencias y firmas de esta carga son ficticias. Los correos usan `example.invalid`. No se envían mensajes durante la carga.

## Acceso

Contraseña inicial de todas las cuentas nuevas: `password`. Al repetir la carga se conservan las matrículas y las contraseñas que ya existen.

| Matrícula | Perfil |
| --- | --- |
| ACA-COORD | Coordinación |
| ACA-LIDER-01 | Elena Rivera, docente líder |
| ACA-DOC-01 | Tomás Vega, docente de materia |
| ACA-DOC-02 | Lucía Montes, docente de materia |
| ACA-ACTUAL-D1-1 | Alumna, proyecto terminado |
| ACA-ACTUAL-D2-1 | Alumna, aprobación de código pendiente |
| ACA-ACTUAL-E1-1 | Alumna, código con corrección solicitada |
| ACA-ACTUAL-E2-1 | Alumna, primeras tres secciones aprobadas |

## Contenido

- Seis periodos cerrados: enero–abril, mayo–agosto y septiembre–diciembre de 2024 y 2025.
- Siete guías, seis históricas cerradas y una actual publicada, cada una con cinco apartados y ponderaciones que suman 100%.
- Catorce grupos, 28 equipos y 84 alumnos ficticios.
- Veintiocho proyectos: 24 históricos terminados y cuatro casos en el periodo activo existente, uno también terminado.
- 152 entregas, incluyendo versiones anteriores con observaciones y posteriores corregidas.
- 358 aprobaciones con firma y 15 revisiones con corrección solicitada. Calificaciones en escala de 0 a 10.
- Veinticinco PDF finales archivados, cifrados y con comprobación de integridad.
- Tres firmas ficticias almacenadas mediante el servicio habitual de normalización y cifrado.

## Recorrido sugerido

1. Entra como coordinación y abre **Periodos** para ver los seis ciclos cerrados de Historial académico.
2. En **Guías integradoras**, selecciona un periodo histórico para ver la estructura publicada y su vista previa.
3. En **Proyectos**, selecciona el mismo periodo y el grupo **8D - Historial** o **8E - Historial**. Abre el documento del proyecto para consultar las aprobaciones y descargar su PDF final.
4. Entra como `ACA-LIDER-01` para revisar los grupos actuales, entregas de código y casos aprobado, pendiente y con corrección.
5. Entra como `ACA-DOC-01` o `ACA-DOC-02` para consultar las asignaciones y las revisiones de los apartados documentales. El producto de código conserva su acceso exclusivo a la materia líder.
6. Entra con cualquiera de las cuatro matrículas de alumna de la tabla para comparar los avances. Los datos de inventario y tutorías incluyen una primera versión con observaciones y una segunda versión corregida; las bandejas muestran la última versión.

Las evidencias son archivos de texto de muestra. Los repositorios y las URL de demostración están vacíos; no se atribuyen repositorios ni aplicaciones reales a estos proyectos ficticios.

## Guías cerradas para probar prórrogas

Carga adicional: `php artisan db:seed --class=CierresDemostracionSeeder --force`.

Periodo cerrado **Seguimiento de cierres y prórrogas**, grupo **8C - Cierres y prórrogas**. Contiene tres guías cerradas y siete proyectos. Conserva el periodo activo y todos los datos anteriores. Repetir la carga no cambia tus decisiones ni duplica los PDF.

Entra como **ACA-LIDER-01**, contraseña inicial **password**, y abre **Cierres y prórrogas**:

| Caso | Qué puedes probar | Alumno |
| --- | --- | --- |
| 01 - Faltan entregas | Dar prórroga o cerrar con pendientes | ACA-CIERRE-ENTREGA-1 |
| 02 - Falta firma de código | Reabrir código para su aprobación firmada | ACA-CIERRE-FIRMA-1 |
| 03 - Prórroga activa | Dos apartados abiertos hasta siete días después de la carga | ACA-PRORROGA-ACTIVA-1 |
| 04 - Prórroga vencida | Consultar la decisión anterior y conceder otro plazo | ACA-PRORROGA-VENCIDA-1 |
| 05 - PDF final firmado | Ver el PDF archivado y el cierre resuelto | ACA-PDF-CERRADO-1 |
| 06 - PDF tras prórroga | Ver nuevas versiones, aprobaciones y PDF final | ACA-PDF-PRORROGA-1 |
| 07 - Cerrado con pendientes | Consultar constancia y entregas bloqueadas; sin PDF final completo | ACA-CIERRE-PARCIAL-1 |

Todas estas cuentas nuevas de alumno usan **password**. En el caso 03, solo están abiertos **Desarrollo y resultados** y **Código y liberación**; **Protocolo del proyecto** conserva sus firmas y permanece bloqueado. Las fechas de las prórrogas se calculan al cargar por primera vez. Para consultar los PDF dentro del sistema, abre el caso 05 o 06 y **Consultar formato y PDF emitidos**.

La carga crea avisos pendientes de demostración sin enviar correos ni despachar jobs. Usa direcciones `example.invalid`, evidencias de texto y firmas ficticias. Los PDF completos se generan con el controlador habitual y se guardan cifrados como archivos definitivos.

## Paginación y seguimiento de las guías

Carga adicional: `php artisan db:seed --class=PaginacionGuiasDemostracionSeeder --force`.

Entra como **ACA-LIDER-01**, contraseña inicial **password**, abre **Estado de las guías** y selecciona **Historial de guías integradoras**. Contiene cuatro grupos, cuatro guías cerradas y 28 equipos: cuatro PDF finales archivados, cuatro proyectos listos para generar PDF, cuatro prórrogas activas, cuatro vencidas, ocho casos con entregas o firmas faltantes y cuatro cierres con pendientes. Con diez equipos por página hay tres páginas.

Los alumnos nuevos son `ACA-HIST-1-1` hasta `ACA-HIST-4-7`, todos con contraseña inicial **password**. El último número identifica el caso: 1 entregas faltantes, 2 firma pendiente, 3 listo para PDF, 4 prórroga activa, 5 vencida, 6 PDF archivado y 7 cierre con pendientes.

Los filtros permiten elegir 10, 20 o 40 equipos por página. El resumen cuenta todos los proyectos del periodo, grupo y búsqueda elegidos; el filtro de estado afecta el detalle. Al abrir **Ver formato y firmas**, los botones **Volver** arriba y abajo recuperan la página y sus filtros. Una guía con muchos equipos puede ocupar varias páginas; la cabecera indica cuántos equipos visibles finalizaron.

Esta carga conserva los ejemplos previos y los PDF emitidos; repetirla no reinicia decisiones, contraseñas ni prórrogas. Solo funciona en local o pruebas y no envía correos.

## Repetir el historial general

```powershell
php artisan db:seed --class=HistorialDemostracionSeeder --force
```

La carga solo funciona en entornos `local` y `testing`. Conserva los ciclos de historial ya cargados y no sobrescribe sus revisiones ni duplica sus PDF. Mantiene los usuarios, firmas y proyectos anteriores. Cada ciclo se guarda dentro de una transacción. El periodo activo existente se conserva para mantener el comportamiento actual de los paneles.
