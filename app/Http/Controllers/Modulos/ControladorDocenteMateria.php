<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\Entrega;
use App\Models\ComentarioRevision;
use App\Models\ArchivoEntrega;
use App\Models\FirmaApartadoGuia;
use App\Models\Periodo;
use App\Models\Revision;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ControladorDocenteMateria extends Controller
{
    public function asignaciones(Request $request): View
    {
        $usuario = $this->docente($request);
        $periodo = $this->periodoActual();
        $firmas = FirmaApartadoGuia::query()
            ->with([
                'asignatura:id,nombre,clave',
                'apartadoGuia:id,guia_integradora_id,orden,titulo,descripcion,fecha_limite,ponderacion,requiere_documento,requiere_codigo',
                'apartadoGuia.guiaIntegradora:id,periodo_id,nombre,cuatrimestre,version,estado',
                'apartadoGuia.guiaIntegradora.periodo:id,nombre',
            ])
            ->where('docente_id', $usuario->id)
            ->when($periodo, fn ($query) => $query->whereHas('apartadoGuia.guiaIntegradora', fn ($guia) => $guia->where('periodo_id', $periodo->id)))
            ->orderBy('apartado_guia_id')
            ->get();

        return view('modulos.docente-materia.asignaciones', $this->base($usuario, compact('periodo', 'firmas')));
    }

    public function revisiones(Request $request): View
    {
        $usuario = $this->docente($request);
        $periodo = $this->periodoActual();
        $apartados = FirmaApartadoGuia::query()
            ->where('docente_id', $usuario->id)
            ->when($periodo, fn ($query) => $query->whereHas('apartadoGuia.guiaIntegradora', fn ($guia) => $guia->where('periodo_id', $periodo->id)))
            ->pluck('apartado_guia_id');

        $entregas = Entrega::query()
            ->with([
                'apartado:id,titulo,ponderacion,fecha_limite',
                'proyecto:id,equipo_id,titulo',
                'equipo:id,grupo_academico_id,numero,nombre',
                'equipo.grupoAcademico:id,carrera_id,periodo_id,grado,grupo',
                'equipo.grupoAcademico.carrera:id,clave,nombre',
                'entregadoPor:id,nombre,matricula',
                'archivos:id,entrega_id,nombre_original,ruta,tipo_archivo,tamano',
                'revisiones' => fn ($query) => $query->where('revisor_id', $usuario->id)->with('comentarios.autor:id,nombre')->latest('revisado_en'),
            ])
            ->whereIn('apartado_guia_id', $apartados)
            ->whereHas('proyecto', fn ($query) => $this->proyectosAsignados($query, $usuario->id))
            ->when($periodo, fn ($query) => $query->whereHas('equipo.grupoAcademico', fn ($grupo) => $grupo->where('periodo_id', $periodo->id)))
            ->latest('entregado_en')->get()
            ->unique(fn ($entrega) => $entrega->proyecto_id.'-'.$entrega->apartado_guia_id)->values();

        return view('modulos.docente-materia.revisiones', $this->base($usuario, compact('periodo', 'entregas')));
    }

    public function guardarRevision(Request $request, Entrega $entrega): RedirectResponse
    {
        $usuario = $this->docente($request);
        $this->autorizarApartado($usuario->id, $entrega);

        $datos = $request->validate([
            'resultado' => ['required', 'in:aprobada,correccion,rechazada'],
            'calificacion' => ['required', 'numeric', 'min:0', 'max:10'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        Revision::query()->updateOrCreate(
            ['entrega_id' => $entrega->id, 'revisor_id' => $usuario->id],
            [...$datos, 'revisado_en' => now()]
        );
        $entrega->update(['estado' => $datos['resultado']]);

        return back()->with('status', 'La revisión se guardó correctamente.');
    }

    public function guardarComentario(Request $request, Entrega $entrega): RedirectResponse
    {
        $usuario = $this->docente($request);
        $this->autorizarApartado($usuario->id, $entrega);
        $datos = $request->validate(['comentario' => ['required', 'string', 'max:1500']]);
        $revision = Revision::query()->where('entrega_id', $entrega->id)->where('revisor_id', $usuario->id)->first();

        if (! $revision) {
            return back()->withErrors(['comentario' => 'Primero guarda la evaluación para poder agregar comentarios de seguimiento.']);
        }

        ComentarioRevision::query()->create([
            'revision_id' => $revision->id,
            'autor_id' => $usuario->id,
            'comentario' => $datos['comentario'],
            'visible_estudiante' => true,
        ]);

        return back()->with('status', 'El comentario se agregó al seguimiento.');
    }

    public function descargarArchivo(Request $request, ArchivoEntrega $archivo): BinaryFileResponse
    {
        $usuario = $this->docente($request);
        $archivo->loadMissing('entrega');
        $this->autorizarApartado($usuario->id, $archivo->entrega);
        $ruta = Storage::path($archivo->ruta);
        if (! is_file($ruta)) {
            $ruta = storage_path('app/'.$archivo->ruta);
        }
        abort_unless(is_file($ruta), 404, 'El archivo ya no está disponible.');

        return response()->download($ruta, $archivo->nombre_original);
    }

    private function autorizarApartado(int $usuarioId, Entrega $entrega): void
    {
        abort_unless(FirmaApartadoGuia::query()->where('docente_id', $usuarioId)->where('apartado_guia_id', $entrega->apartado_guia_id)->exists(), 403);
        abort_unless(
            $this->proyectosAsignados(\App\Models\Proyecto::query()->whereKey($entrega->proyecto_id), $usuarioId)->exists(),
            403
        );
    }

    private function proyectosAsignados($query, int $usuarioId)
    {
        return $query->where(function ($scope) use ($usuarioId) {
            $scope
                ->whereHas('docentes', fn ($docentes) => $docentes->where('usuarios.id', $usuarioId))
                ->orWhereHas('asignaturas', fn ($asignaturas) => $asignaturas->where('asignaturas_proyecto.docente_id', $usuarioId));
        });
    }

    private function docente(Request $request)
    {
        $usuario = $request->user()->loadMissing('role');
        abort_unless($usuario->hasRole('docente_materia'), 403);
        return $usuario;
    }

    private function periodoActual(): ?Periodo
    {
        return Periodo::query()->where('estado', 'activo')->latest('fecha_inicio')->first()
            ?? Periodo::query()->latest('fecha_inicio')->first();
    }

    private function base($usuario, array $datos): array
    {
        return [...$datos, 'navegacion' => SistemaInterfaz::navegacionPara('docente_materia'), 'roleName' => $usuario->role->nombre_visible];
    }
}
