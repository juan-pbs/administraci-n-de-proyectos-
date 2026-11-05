import fs from "node:fs/promises";
import path from "node:path";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const workbook = Workbook.create();
const carga = workbook.worksheets.add("Carga alumnos");
const instrucciones = workbook.worksheets.add("Instrucciones");

carga.showGridLines = false;
carga.getRange("A1:C1").values = [["matricula", "nombre_completo", "correo_electronico"]];
carga.getRange("A1:C1").format = {
  fill: "#0D376D",
  font: { bold: true, color: "#FFFFFF" },
  rowHeight: 30,
};
carga.getRange("A:A").format.columnWidth = 20;
carga.getRange("B:B").format.columnWidth = 38;
carga.getRange("C:C").format.columnWidth = 42;
carga.freezePanes.freezeRows(1);

instrucciones.showGridLines = false;
instrucciones.getRange("A1").values = [["Plantilla de carga de alumnos"]];
instrucciones.getRange("A1").format = {
  font: { bold: true, color: "#0D376D", size: 16 },
  rowHeight: 32,
};
instrucciones.getRange("A3:A6").values = [
  ["1. Llena las tres columnas de la hoja Carga alumnos, sin cambiar los encabezados."],
  ["2. Usa una fila por alumno y un correo válido para cada registro."],
  ["3. Prepara un archivo por lista; en el sistema elige periodo, carrera, grado y grupo."],
  ["4. Genera la vista previa y verifica los datos antes de guardar."],
];
instrucciones.getRange("A:A").format.columnWidth = 100;

const output = path.resolve("public/plantillas/plantilla_carga_alumnos.xlsx");
const file = await SpreadsheetFile.exportXlsx(workbook);
await file.save(output);
await fs.rm(`${output}.inspect.ndjson`, { force: true });
