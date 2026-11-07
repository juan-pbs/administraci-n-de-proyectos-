# Recorrido por los registros cargados

Todos los datos personales y las firmas son ficticios. Los registros tienen nombres normales; no se identifican con etiquetas especiales en la interfaz. Contraseña inicial de práctica: **password**.

## Cuentas

| Cuenta | Matrícula | Uso |
| --- | --- | --- |
| Patricia Hernández Luna | 20260001 | Coordinación: periodos, guías, alumnos, asignaciones y preview. |
| Elena Rivera Soto | DOC-TI-01 | Líder: historial y grupos 8A de TI. |
| Carlos Mendoza Ruiz | DOC-TI-02 | Líder: historial y grupos 8B de TI. |
| Tomás Vega López | DOC-TI-03 | Revisión documental con firma. |
| Lucía Montes García | DOC-TI-04 | Revisión documental con firma de grupos 8B. |
| Ana Torres Salas | DOC-TI-05 | Docente sin firma con apartados asignados. |
| Sofía Ortega Cruz | DOC-MEC-01 | Líder de Mecatrónica, para comparar permisos. |
| Daniel Castillo Pérez | DOC-MEC-02 | Líder de Mecatrónica, grupo 8B. |
| Roberto Silva León | DOC-MEC-03 | Docente de materia con firma. |
| Mariana Ríos Vargas | DOC-MEC-04 | Docente de materia con firma. |
| Luis Navarro Díaz | DOC-MEC-05 | Docente de materia sin firma. |

Los docentes con sufijo `06` y algunos alumnos están inactivos para verificar que no pueden iniciar sesión.

## Periodos

- Enero - Abril 2025 y Mayo - Agosto 2025: proyectos terminados con PDF, trabajos corregidos con segunda versión y cierres incompletos.
- Mayo - Agosto 2026: entregas faltantes, firma pendiente, listo para PDF, prórroga activa, vencida, PDF final y cierre con pendientes.
- Septiembre - Diciembre 2026: periodo activo, entregas abiertas, parciales, correcciones, rechazos y aprobaciones pendientes de registrar firma.
- Enero - Abril 2027: estructura en borrador para probar activación/publicación después de cerrar el periodo actual. La guía borrador no se puede asignar desde la creación normal de proyectos; los registros de preparación permiten visualizar su estado en el historial de práctica.

## Alumnos de TI 8A

| Matrícula | Escenario |
| --- | --- |
| TI41011 | Periodo activo, sin entregas. |
| TI41021 | Solo primer apartado entregado/aprobado. |
| TI41031 | Corrección solicitada. |
| TI41041 | Entrega rechazada. |
| TI41051 | Falta la aprobación de una docente sin firma registrada. |
| TI41061 | Todos los apartados completos, falta emitir PDF. |
| TI41071 | PDF final archivado. |
| TI31011 | Periodo cerrado, faltan entregas. |
| TI31021 | Periodo cerrado, falta aprobación de código. |
| TI31031 | Periodo cerrado, listo para PDF. |
| TI31041 | Prórroga vigente en Desarrollo y Código. |
| TI31051 | Prórroga vencida, requiere decisión nueva. |
| TI31061 | PDF final de un periodo cerrado. |
| TI31071 | Cierre con constancia de pendientes, entregas bloqueadas. |
| TI41991 | Alumno activo sin equipo. |

Cada equipo tiene tres integrantes. El último dígito de su matrícula identifica al integrante (1, 2 o 3); los tres pueden colaborar. Para comparar otro grupo cambia el dígito del grupo de `1` a `2`; para Mecatrónica cambia `TI` por `MEC`.

## Ruta de revisión

1. Coordinación: consulta listas de alumnos/docentes y cambia el periodo/grupo; revisa materias líderes y los apartados de la guía. Usa preview antes de guardar.
2. Líder DOC-TI-01: abre Estado de las guías con todos los periodos; hay 21 proyectos repartidos en páginas. Compara estados y abre cada apartado para identificar motivos.
3. Abre Ver formato y firmas desde la página 2. Volver conserva el origen y los filtros. Descarga un PDF final y compara con la vista de datos actuales.
4. En Mayo - Agosto 2026, revisa prórrogas, decisiones y constancias de cierre; en la prórroga activa solo se abren Desarrollo y Código.
5. Alumno TI31041: selecciona el proyecto anterior y envía una versión nueva en esos dos apartados; Protocolo permanece bloqueado.
6. Docente DOC-TI-03: revisa documentos y comentarios. DOC-TI-05 permite comprobar el registro de firma previo a aprobar. Ninguno recibe acceso a los repositorios de la materia líder.
7. Líder DOC-TI-01: revisa los repositorios de referencia y descarga los comprimidos o el paquete Debian de práctica. El paquete no es una aplicación compilada del repositorio y no se ejecuta en el servidor.
8. Prueba recuperación con el correo de una cuenta (`doc-ti-01@example.invalid`). En modo local el código aparece en el log; el reenvío tarda tres minutos.

Las URL de aplicaciones alojadas permanecen vacías cuando no se conoce un despliegue verificado. El campo es opcional y admite una URL HTTPS pública cuando el alumno entregue su trabajo.

## Reinicio

`php artisan datos:reiniciar --confirmar=administracion_proyectos` reemplaza todos los registros locales después de crear un ZIP privado. Este incluye la clave de cifrado: nunca debe publicarse. El comando conserva las migraciones y usa transacción; una carga fallida revierte los cambios de base.

Al reiniciar se eliminan sesiones anteriores y se vuelve a ingresar con las cuentas de esta tabla. Los archivos anteriores se conservan privados y quedan también respaldados; ningún nuevo registro los utiliza. Las fechas de los escenarios se calculan al cargar, incluyendo una prórroga de siete días y otra ya vencida.
