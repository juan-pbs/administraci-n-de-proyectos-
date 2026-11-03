<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\PlantillaRecordatorio;
use App\Models\Proyecto;
use App\Servicios\AvisosAsignaciones;
use App\Servicios\AvisosRevisiones;
use App\Servicios\TextoRecordatorios;
use App\Soporte\SistemaInterfaz;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ControladorRecordatorios extends Controller
{
    public function mostrar(Request $request, TextoRecordatorios $textos)
    {
        $docente = $this->docente($request);
        $proyectos = Proyecto::query()
            ->with(['equipo.grupoAcademico', 'guiaIntegradora.apartados'])
            ->where('estado', 'en_proceso')
            ->whereHas('equipo.grupoAcademico', fn ($q) => $q->conMateriaLiderDelDocente($docente->id))
            ->orderBy('titulo')->paginate(15);
        $plantillas = [];
        foreach (TextoRecordatorios::TIPOS as $tipo => $nombre) {
            $plantillas[$tipo] = $textos->plantilla($docente->id, $tipo);
        }

        return response()->view('modulos.docente-lider.recordatorios', [
            'proyectos' => $proyectos, 'plantillas' => $plantillas, 'tipos' => TextoRecordatorios::TIPOS,
            'navegacion' => SistemaInterfaz::navegacionPara('docente_lider'), 'roleName' => $docente->role->nombre_visible,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function guardar(Request $request)
    {
        $docente = $this->docente($request);
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(TextoRecordatorios::TIPOS))],
            'texto' => ['required', 'string', 'max:3000'],
        ]);
        PlantillaRecordatorio::query()->updateOrCreate(
            ['docente_id' => $docente->id, 'tipo' => $datos['tipo']],
            ['texto' => trim($datos['texto'])],
        );

        return redirect()->route('docente-lider.recordatorios')->with('status', 'Formato de recordatorio guardado.');
    }

    public function enviar(Request $request, Proyecto $proyecto, AvisosAsignaciones $avisos, AvisosRevisiones $revisiones)
    {
        $docente = $this->docente($request);
        abort_unless($proyecto->estado === 'en_proceso' && $proyecto->equipo?->grupoAcademico
            && $proyecto->equipo->grupoAcademico()->conMateriaLiderDelDocente($docente->id)->exists(), 403);
        $estudiantes = $avisos->enviarManualmente($proyecto);
        $docentes = $revisiones->paraProyecto($proyecto, true);

        return redirect()->route('docente-lider.recordatorios')->with('status', $estudiantes + $docentes
            ? "Se programaron {$estudiantes} recordatorios de entrega para estudiantes y {$docentes} avisos de revisión para docentes."
            : 'No hay recordatorios nuevos para enviar. Se omiten entregas completas, revisiones realizadas y avisos ya solicitados hoy.');
    }

    public function enviarTodos(Request $request, AvisosAsignaciones $avisos, AvisosRevisiones $revisiones)
    {
        $docente = $this->docente($request);
        $estudiantes = 0;
        $docentes = 0;
        Proyecto::query()->where('estado', 'en_proceso')
            ->whereHas('equipo.grupoAcademico', fn ($q) => $q->conMateriaLiderDelDocente($docente->id))
            ->chunkById(50, function ($proyectos) use ($avisos, $revisiones, &$estudiantes, &$docentes): void {
                foreach ($proyectos as $proyecto) {
                    $estudiantes += $avisos->enviarManualmente($proyecto);
                    $docentes += $revisiones->paraProyecto($proyecto, true);
                }
            });

        return redirect()->route('docente-lider.recordatorios')->with('status', $estudiantes + $docentes
            ? "Se programaron {$estudiantes} recordatorios de entrega para estudiantes y {$docentes} avisos de revisión para docentes de tus proyectos."
            : 'No hay recordatorios nuevos para enviar en tus proyectos.');
    }

    private function docente(Request $request)
    {
        $docente = $request->user()->loadMissing('role');
        abort_unless($docente->hasRole('docente_lider') && $docente->estado === 'activo', 403);

        return $docente;
    }
}
