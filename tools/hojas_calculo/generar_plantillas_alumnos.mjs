import fs from "node:fs/promises";
import path from "node:path";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const salida = path.resolve("outputs/plantillas_carga_alumnos");
const salidaPublica = path.resolve("public/plantillas");

const periodos = [
  ["Mayo - Agosto 2026", "2026-05-01", "2026-08-01"],
  ["Septiembre - Noviembre 2026", "2026-09-10", "2026-11-20"],
];

const carreras = [
  ["TI", "Tecnologias de la Informacion"],
  ["MECA", "Mecatronica"],
  ["ADM", "Administracion"],
];

const grupos = [];
for (const [claveCarrera] of carreras) {
  for (const grado of [5, 6, 7, 8, 9]) {
    const letras = [8, 9].includes(grado) ? ["A", "B", "C", "D"] : ["A", "B", "C"];
    for (const grupo of letras) {
      grupos.push([claveCarrera, grado, grupo]);
    }
  }
}

const rolesEquipo = [["Lider"], ["Integrante"]];
const datosUniversidad = generarAlumnosUniversidad();

const columnasBase = [
  "matricula",
  "nombre_completo",
  "correo_electronico",
  "clave_carrera",
  "grado",
  "grupo",
  "periodo",
  "observaciones",
];

const columnasConEquipos = [
  "matricula",
  "nombre_completo",
  "correo_electronico",
  "clave_carrera",
  "grado",
  "grupo",
  "periodo",
  "equipo",
  "rol_en_equipo",
  "observaciones",
];

const ejemplosBase = datosUniversidad.map((alumno, indice) => [
  alumno.matricula,
  alumno.nombre,
  alumno.correo,
  alumno.carrera,
  alumno.grado,
  alumno.grupo,
  alumno.periodo,
  indice === 0 ? "Alumno sin equipo; se asignara en el sistema" : "",
]);

const ejemplosConEquipos = datosUniversidad.map((alumno, indice) => [
  alumno.matricula,
  alumno.nombre,
  alumno.correo,
  alumno.carrera,
  alumno.grado,
  alumno.grupo,
  alumno.periodo,
  alumno.equipo,
  alumno.rolEquipo,
  indice === 0 ? "Cada equipo debe tener 6 alumnos y solo un lider" : "",
]);

const azul = "#0D376D";
const azulMedio = "#15529A";
const verde = "#21A366";
const amarillo = "#FFD966";
const grisBorde = "#CBD5E1";
const grisFondo = "#F8FAFC";

async function crearPlantilla({ nombreArchivo, titulo, columnas, ejemplos, incluyeEquipos }) {
  const workbook = Workbook.create();
  const instrucciones = workbook.worksheets.add("Instrucciones");
  const carga = workbook.worksheets.add("Carga alumnos");
  const catalogos = workbook.worksheets.add("Catalogos");

  await fs.mkdir(salida, { recursive: true });
  await fs.mkdir(salidaPublica, { recursive: true });

  [instrucciones, carga, catalogos].forEach((sheet) => {
    sheet.showGridLines = false;
  });

  configurarInstrucciones(instrucciones, titulo, incluyeEquipos);
  configurarCatalogos(catalogos);
  configurarCarga(carga, columnas, ejemplos, incluyeEquipos);

  const revision = await workbook.inspect({
    kind: "table",
    sheetId: "Carga alumnos",
    range: `A1:${columnaExcel(columnas.length)}8`,
    include: "values",
    tableMaxRows: 8,
    tableMaxCols: columnas.length,
    maxChars: 3000,
  });
  console.log(revision.ndjson);

  const errores = await workbook.inspect({
    kind: "match",
    searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
    options: { useRegex: true, maxResults: 100 },
    summary: "revision de errores",
  });
  console.log(errores.ndjson);

  await renderizar(workbook, "Instrucciones", `${nombreArchivo}-instrucciones.png`);
  await renderizar(workbook, "Carga alumnos", `${nombreArchivo}-carga.png`, `A1:${columnaExcel(columnas.length)}18`);
  await renderizar(workbook, "Catalogos", `${nombreArchivo}-catalogos.png`);

  const archivo = await SpreadsheetFile.exportXlsx(workbook);
  await archivo.save(path.join(salida, nombreArchivo));
  await archivo.save(path.join(salidaPublica, nombreArchivo));
}

function configurarInstrucciones(sheet, titulo, incluyeEquipos) {
  sheet.getRange("A1:H1").merge();
  sheet.getRange("A1").values = [[titulo]];
  sheet.getRange("A1").format = {
    fill: azul,
    font: { bold: true, color: "#FFFFFF", size: 16 },
    horizontalAlignment: "center",
    verticalAlignment: "center",
  };
  sheet.getRange("A1").format.rowHeight = 34;

  sheet.getRange("A3:H3").merge();
  sheet.getRange("A3").values = [["Uso de la plantilla"]];
  sheet.getRange("A3").format = encabezadoSeccion();

  const textoEquipo = incluyeEquipos
    ? "Si el alumno ya pertenece a un equipo, captura el nombre del equipo y marca si es Lider o Integrante. Cada equipo debe agrupar 6 alumnos y usar exactamente el mismo nombre de equipo."
    : "Esta plantilla no asigna equipos. Despues de cargar alumnos, los equipos se forman desde el modulo Equipos.";

  sheet.getRange("A4:H9").values = [
    ["1.", "No cambies los nombres de las columnas de la hoja Carga alumnos.", null, null, null, null, null, null],
    ["2.", "Llena una fila por alumno. Las primeras columnas son los datos del alumno: matricula, nombre y correo.", null, null, null, null, null, null],
    ["3.", "Despues captura clave de carrera, grado, grupo y periodo usando los catalogos incluidos.", null, null, null, null, null, null],
    ["4.", "El grado debe ser numerico y el grupo debe coincidir con la carrera.", null, null, null, null, null, null],
    ["5.", textoEquipo, null, null, null, null, null, null],
    ["6.", "El correo se usara para enviar la contrasena temporal generada por el sistema.", null, null, null, null, null, null],
  ];
  sheet.getRange("A4:A9").format = {
    font: { bold: true, color: azul },
    horizontalAlignment: "center",
  };
  sheet.getRange("B4:H9").merge(true);
  sheet.getRange("A4:H9").format = {
    fill: "#FFFFFF",
    font: { color: "#334155" },
    wrapText: true,
    borders: { preset: "inside", style: "thin", color: "#E2E8F0" },
  };

  sheet.getRange("A11:H11").merge();
  sheet.getRange("A11").values = [["Columnas obligatorias"]];
  sheet.getRange("A11").format = encabezadoSeccion();
  sheet.getRange("A12:H16").values = [
    ["matricula", "Identificador con el que el alumno iniciara sesion.", null, null, null, null, null, null],
    ["nombre_completo", "Nombre del alumno tal como debe aparecer en el sistema.", null, null, null, null, null, null],
    ["correo_electronico", "Correo al que se enviara la contrasena temporal.", null, null, null, null, null, null],
    ["clave_carrera, grado, grupo, periodo", "Datos academicos para ubicar al alumno antes de formar o asignar equipos.", null, null, null, null, null, null],
    ["equipo, rol_en_equipo", "Solo se usan en la plantilla con equipos. El rol debe ser Lider o Integrante.", null, null, null, null, null, null],
  ];
  sheet.getRange("A12:A16").format = { font: { bold: true, color: azulMedio } };
  sheet.getRange("B12:H16").merge(true);
  sheet.getRange("A12:H16").format = {
    fill: grisFondo,
    wrapText: true,
    borders: { preset: "inside", style: "thin", color: "#E2E8F0" },
  };

  sheet.getRange("A:H").format.columnWidth = 18;
  sheet.getRange("B:H").format.columnWidth = 19;
}

function configurarCatalogos(sheet) {
  sheet.getRange("A1:C1").values = [["Periodos", "Fecha inicio", "Fecha fin"]];
  sheet.getRange("A2:C3").values = periodos;
  sheet.getRange("E1:F1").values = [["Clave carrera", "Carrera"]];
  sheet.getRange("E2:F4").values = carreras;
  sheet.getRange("H1:J1").values = [["Clave carrera", "Grado", "Grupo"]];
  sheet.getRange(`H2:J${grupos.length + 1}`).values = grupos;
  sheet.getRange("L1:L1").values = [["Rol en equipo"]];
  sheet.getRange("L2:L3").values = rolesEquipo;

  ["A1:C1", "E1:F1", "H1:J1", "L1:L1"].forEach((range) => {
    sheet.getRange(range).format = {
      fill: azul,
      font: { bold: true, color: "#FFFFFF" },
      horizontalAlignment: "center",
      borders: { preset: "outside", style: "thin", color: azul },
    };
  });

  sheet.getRange("A2:C3").format = cuerpoCatalogo();
  sheet.getRange("E2:F4").format = cuerpoCatalogo();
  sheet.getRange(`H2:J${grupos.length + 1}`).format = cuerpoCatalogo();
  sheet.getRange("L2:L3").format = cuerpoCatalogo();
  sheet.getRange("B2:C3").format.numberFormat = "yyyy-mm-dd";
  sheet.getRange("A:L").format.columnWidth = 20;
  sheet.freezePanes.freezeRows(1);
}

function configurarCarga(sheet, columnas, ejemplos, incluyeEquipos) {
  const ultimaCol = columnaExcel(columnas.length);
  const filasTotales = Math.max(60, ejemplos.length + 20);
  const ultimaFila = filasTotales + 2;

  sheet.getRange(`A1:${ultimaCol}1`).merge();
  sheet.getRange("A1").values = [["Carga de alumnos"]];
  sheet.getRange("A1").format = {
    fill: azul,
    font: { bold: true, color: "#FFFFFF", size: 15 },
    horizontalAlignment: "center",
    verticalAlignment: "center",
  };
  sheet.getRange("A1").format.rowHeight = 32;

  sheet.getRange(`A2:${ultimaCol}2`).values = [columnas];
  sheet.getRange(`A2:${ultimaCol}2`).format = {
    fill: amarillo,
    font: { bold: true, color: "#0F172A" },
    horizontalAlignment: "center",
    verticalAlignment: "center",
    wrapText: true,
    borders: { preset: "all", style: "thin", color: grisBorde },
  };

  const filasVacias = Array.from({ length: Math.max(0, filasTotales - ejemplos.length) }, () => columnas.map(() => null));
  sheet.getRange(`A3:${ultimaCol}${ultimaFila}`).values = [...ejemplos, ...filasVacias].slice(0, filasTotales);
  sheet.getRange(`A3:${ultimaCol}${ultimaFila}`).format = {
    fill: "#FFFFFF",
    font: { color: "#0F172A" },
    borders: {
      insideHorizontal: { style: "thin", color: "#E2E8F0" },
      insideVertical: { style: "thin", color: "#E2E8F0" },
      bottom: { style: "thin", color: grisBorde },
    },
  };

  sheet.getRange(`D3:D${ultimaFila}`).dataValidation = { rule: { type: "list", formula1: "'Catalogos'!$E$2:$E$4" } };
  sheet.getRange(`E3:E${ultimaFila}`).dataValidation = { rule: { type: "whole", operator: "between", formula1: 1, formula2: 12 } };
  sheet.getRange(`F3:F${ultimaFila}`).dataValidation = { rule: { type: "list", values: ["A", "B", "C", "D", "E"] } };
  sheet.getRange(`G3:G${ultimaFila}`).dataValidation = { rule: { type: "list", formula1: "'Catalogos'!$A$2:$A$3" } };
  if (incluyeEquipos) {
    sheet.getRange(`I3:I${ultimaFila}`).dataValidation = { rule: { type: "list", formula1: "'Catalogos'!$L$2:$L$3" } };
  }

  const tabla = sheet.tables.add(`A2:${ultimaCol}${ultimaFila}`, true, incluyeEquipos ? "TablaAlumnosConEquipos" : "TablaAlumnosSinEquipos");
  tabla.style = "TableStyleMedium2";
  tabla.showFilterButton = true;

  sheet.freezePanes.freezeRows(2);
  sheet.getRange("A:A").format.columnWidth = 16;
  sheet.getRange("B:B").format.columnWidth = 34;
  sheet.getRange("C:C").format.columnWidth = 32;
  sheet.getRange("D:D").format.columnWidth = 16;
  sheet.getRange("E:E").format.columnWidth = 10;
  sheet.getRange("F:F").format.columnWidth = 10;
  sheet.getRange("G:G").format.columnWidth = 26;
  if (incluyeEquipos) {
    sheet.getRange("H:H").format.columnWidth = 26;
    sheet.getRange("I:I").format.columnWidth = 18;
    sheet.getRange("J:J").format.columnWidth = 34;
  } else {
    sheet.getRange("H:H").format.columnWidth = 38;
  }
}

function generarAlumnosUniversidad() {
  const nombres = ["Ana Sofia", "Luis Fernando", "Maria Fernanda", "Jose Antonio", "Diana Paola", "Ricardo", "Valeria", "Hector Ivan", "Camila", "Emiliano", "Paola", "Andres", "Ximena", "Diego", "Regina", "Santiago", "Montserrat", "Leonardo", "Renata", "Mateo", "Alejandra", "Sebastian", "Natalia", "Angel", "Daniela", "Javier", "Andrea", "Rodrigo", "Fernanda", "Mauricio"];
  const apellidos = ["Martinez", "Perez", "Gomez", "Ramirez", "Vargas", "Salinas", "Moreno", "Flores", "Reyes", "Castillo", "Jimenez", "Molina", "Cruz", "Hernandez", "Soto", "Nava", "Ortega", "Campos", "Luna", "Diaz"];
  const segundosApellidos = ["Cruz", "Diaz", "Luna", "Soto", "Leon", "Torres", "Campos", "Nava", "Ortega", "Rios", "Arce", "Pacheco", "Aguilar", "Morales", "Salinas", "Perez", "Garcia", "Ruiz", "Mendez", "Vega"];
  const alumnos = [];
  let contador = 1;

  for (const [carrera, grado, grupo] of grupos) {
    for (let indice = 1; indice <= 30; indice += 1) {
      const equipoNumero = Math.floor((indice - 1) / 6) + 1;
      const posicionEquipo = ((indice - 1) % 6) + 1;
      const matricula = `2026${String(contador).padStart(5, "0")}`;
      const nombre = `${nombres[(contador - 1) % nombres.length]} ${apellidos[(contador + indice) % apellidos.length]} ${segundosApellidos[(contador + grado + indice) % segundosApellidos.length]}`;
      const equipoPrefijo = carrera === "MECA" ? "Equipo Mecatronica" : carrera === "ADM" ? "Equipo Administracion" : "Equipo";

      alumnos.push({
        matricula,
        nombre,
        correo: `alumno${matricula}@utvm.edu.mx`,
        carrera,
        grado,
        grupo,
        periodo: "Septiembre - Noviembre 2026",
        equipo: `${equipoPrefijo} ${equipoNumero}`,
        rolEquipo: posicionEquipo === 1 ? "Lider" : "Integrante",
      });

      contador += 1;
    }
  }

  return alumnos;
}

function encabezadoSeccion() {
  return {
    fill: verde,
    font: { bold: true, color: "#FFFFFF" },
    horizontalAlignment: "left",
    verticalAlignment: "center",
  };
}

function cuerpoCatalogo() {
  return {
    fill: "#FFFFFF",
    borders: { preset: "all", style: "thin", color: grisBorde },
  };
}

function columnaExcel(numero) {
  let resultado = "";
  while (numero > 0) {
    const resto = (numero - 1) % 26;
    resultado = String.fromCharCode(65 + resto) + resultado;
    numero = Math.floor((numero - resto - 1) / 26);
  }
  return resultado;
}

async function renderizar(workbook, sheetName, nombre, range = null) {
  const preview = await workbook.render({
    sheetName,
    ...(range ? { range } : { autoCrop: "all" }),
    scale: 1,
    format: "png",
  });
  await fs.writeFile(path.join(salida, nombre), new Uint8Array(await preview.arrayBuffer()));
}

await crearPlantilla({
  nombreArchivo: "plantilla_carga_alumnos_sin_equipos.xlsx",
  titulo: "Plantilla de carga de alumnos sin equipos",
  columnas: columnasBase,
  ejemplos: ejemplosBase,
  incluyeEquipos: false,
});

await crearPlantilla({
  nombreArchivo: "plantilla_carga_alumnos_con_equipos.xlsx",
  titulo: "Plantilla de carga de alumnos con equipos",
  columnas: columnasConEquipos,
  ejemplos: ejemplosConEquipos,
  incluyeEquipos: true,
});
