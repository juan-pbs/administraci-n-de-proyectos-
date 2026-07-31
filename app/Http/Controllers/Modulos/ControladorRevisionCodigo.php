<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\ArchivoEntrega;
use App\Models\ComentarioRevision;
use App\Models\Entrega;
use App\Models\Periodo;
use App\Models\Revision;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ControladorRevisionCodigo extends Controller
{
    public function mostrar(Request $request): View
    {
        $docente = $this->lider($request);
        $periodo = Periodo::query()->where('estado', 'activo')->latest('fecha_inicio')->first()
            ?? Periodo::query()->latest('fecha_inicio')->first();
        $entregas = Entrega::query()
            ->with([
                'apartado:id,titulo,ponderacion,fecha_limite,requiere_codigo',
                'proyecto:id,equipo_id,titulo',
                'equipo:id,grupo_academico_id,numero,nombre',
                'equipo.grupoAcademico:id,carrera_id,periodo_id,grado,grupo,lider_proyecto_id',
                'equipo.grupoAcademico.carrera:id,clave,nombre',
                'entregadoPor:id,nombre,matricula',
                'archivos:id,entrega_id,nombre_original,ruta,tipo_archivo,tamano',
                'productosCodigo:id,entrega_id,proyecto_id,repositorio_url,archivo_fuente,version',
                'revisiones' => fn ($query) => $query->where('revisor_id', $docente->id)->with('comentarios.autor:id,nombre')->latest('revisado_en'),
            ])
            ->whereHas('apartado', fn ($query) => $query->where('requiere_codigo', true))
            ->whereHas('equipo.grupoAcademico', function ($query) use ($docente, $periodo) {
                $query->conMateriaLiderDelDocente($docente->id)
                    ->when($periodo, fn ($grupo) => $grupo->where('periodo_id', $periodo->id));
            })
            ->latest('entregado_en')->get()
            ->unique(fn ($entrega) => $entrega->proyecto_id.'-'.$entrega->apartado_guia_id)->values();

        return view('modulos.docente-lider.revision-codigo', [
            'periodo' => $periodo,
            'entregas' => $entregas,
            'navegacion' => SistemaInterfaz::navegacionPara('docente_lider'),
            'roleName' => $docente->role->nombre_visible,
        ]);
    }

    public function guardarRevision(Request $request, Entrega $entrega): RedirectResponse
    {
        $docente = $this->lider($request);
        $this->autorizar($docente->id, $entrega);
        $datos = $request->validate([
            'resultado' => ['required', 'in:aprobada,correccion,rechazada'],
            'calificacion' => ['required', 'numeric', 'min:0', 'max:10'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);
        Revision::query()->updateOrCreate(
            ['entrega_id' => $entrega->id, 'revisor_id' => $docente->id],
            [...$datos, 'revisado_en' => now()]
        );
        $entrega->update(['estado' => $datos['resultado']]);

        return back()->with('status', 'La revisión técnica se guardó correctamente.');
    }

    public function guardarComentario(Request $request, Entrega $entrega): RedirectResponse
    {
        $docente = $this->lider($request);
        $this->autorizar($docente->id, $entrega);
        $datos = $request->validate(['comentario' => ['required', 'string', 'max:1500']]);
        $revision = Revision::query()->where('entrega_id', $entrega->id)->where('revisor_id', $docente->id)->first();
        abort_unless($revision, 422, 'Primero guarda la revisión técnica.');
        ComentarioRevision::query()->create(['revision_id' => $revision->id, 'autor_id' => $docente->id, 'comentario' => $datos['comentario'], 'visible_estudiante' => true]);

        return back()->with('status', 'Comentario técnico agregado.');
    }

    public function descargar(Request $request, ArchivoEntrega $archivo): BinaryFileResponse
    {
        $docente = $this->lider($request);
        $archivo->loadMissing('entrega');
        $this->autorizar($docente->id, $archivo->entrega);
        $ruta = Storage::path($archivo->ruta);
        if (! is_file($ruta)) {
            $ruta = storage_path('app/'.$archivo->ruta);
        }
        abort_unless(is_file($ruta), 404);

        return response()->download($ruta, $archivo->nombre_original);
    }

    private function lider(Request $request)
    {
        $docente = $request->user()->loadMissing('role');
        abort_unless($docente->hasRole('docente_lider'), 403);
        return $docente;
    }

    private function autorizar(int $docenteId, Entrega $entrega): void
    {
        abort_unless(
            $entrega->apartado()->where('requiere_codigo', true)->exists()
            && $entrega->equipo()->whereHas('grupoAcademico', fn ($grupo) => $grupo->conMateriaLiderDelDocente($docenteId))->exists(),
            403
        );
    }
}
