<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\Equipo;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ControladorProyectos extends Controller
{
    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';

        abort_unless(SistemaInterfaz::puedeVer($rol, 'proyectos'), 403);

        return view('modulos.gestion-proyectos.proyectos', [
            'active' => 'proyectos',
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => SistemaInterfaz::pagina('proyectos'),
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
            ...$this->datos($usuario, $request),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('docente_lider'), 403);

        $datos = $request->validate([
            'guia_integradora_id' => ['required', 'exists:guias_integradoras,id'],
            'equipo_id' => ['required', 'exists:equipos,id'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $equipo = Equipo::query()->with('grupoAcademico')->findOrFail($datos['equipo_id']);
        abort_unless(GrupoAcademico::query()->whereKey($equipo->grupo_academico_id)->conMateriaLiderDelDocente((int) $request->user()->id)->exists(), 403);
        $guia = GuiaIntegradora::query()->with('asignatura')->findOrFail($datos['guia_integradora_id']);
        abort_unless($guia->estado === 'publicada', 422, 'Publica la guía antes de asignarla a un proyecto.');
        abort_unless(
            (int) $guia->periodo_id === (int) $equipo->grupoAcademico->periodo_id
            && (int) $guia->asignatura?->carrera_id === (int) $equipo->grupoAcademico->carrera_id
            && (int) $guia->cuatrimestre === (int) $equipo->grupoAcademico->grado,
            422,
        );

        Proyecto::query()->updateOrCreate(
            ['guia_integradora_id' => $datos['guia_integradora_id'], 'equipo_id' => $datos['equipo_id']],
            ['titulo' => $datos['titulo'], 'descripcion' => $datos['descripcion'] ?? null, 'estado' => 'en_proceso'],
        );

        if ($request->string('origen')->toString() === 'equipos') {
            return redirect()->route('modulos.show', array_filter([
                'modulo' => 'equipos',
                'periodo_equipos' => $request->input('periodo_equipos'),
                'grupo_equipos' => $request->input('grupo_equipos'),
            ]))->with('estado', 'Proyecto guardado correctamente.');
        }

        return redirect()->route('modulos.show', 'proyectos')->with('estado', 'Proyecto guardado correctamente.');
    }

    public function asignarDocente(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('docente_lider'), 403);

        $datos = $request->validate([
            'proyecto_id' => ['required', 'exists:proyectos,id'],
            'docente_id' => ['required', 'exists:usuarios,id'],
            'tipo_participacion' => ['required', 'string', 'max:50'],
        ]);

        $proyecto = Proyecto::query()->with('equipo.grupoAcademico')->findOrFail($datos['proyecto_id']);
        $this->autorizarProyectoAsignado($request, $proyecto);

        $docente = User::query()
            ->whereKey($datos['docente_id'])
            ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['docente_lider', 'docente_materia']))
            ->firstOrFail();

        $proyecto->docentes()->syncWithoutDetaching([
            $docente->id => [
                'tipo_participacion' => $datos['tipo_participacion'],
                'activo' => true,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ]);

        return $this->volverProyectos($request, 'Docente asignado al proyecto correctamente.');
    }

    public function quitarDocente(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('docente_lider'), 403);

        $datos = $request->validate([
            'proyecto_id' => ['required', 'exists:proyectos,id'],
            'docente_id' => ['required', 'exists:usuarios,id'],
        ]);

        $proyecto = Proyecto::query()->with('equipo.grupoAcademico')->findOrFail($datos['proyecto_id']);
        $this->autorizarProyectoAsignado($request, $proyecto);

        $proyecto->docentes()->updateExistingPivot($datos['docente_id'], [
            'activo' => false,
            'actualizado_en' => now(),
        ]);

        return $this->volverProyectos($request, 'Docente retirado del proyecto correctamente.');
    }

    public function asignarAsignatura(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('docente_lider'), 403);

        $datos = $request->validate([
            'proyecto_id' => ['required', 'exists:proyectos,id'],
            'asignatura_id' => ['required', 'exists:asignaturas,id'],
            'docente_id' => ['nullable', 'exists:usuarios,id'],
            'participa_evaluacion' => ['nullable', 'boolean'],
        ]);

        $proyecto = Proyecto::query()->with('equipo.grupoAcademico')->findOrFail($datos['proyecto_id']);
        $this->autorizarProyectoAsignado($request, $proyecto);

        $asignatura = Asignatura::query()->findOrFail($datos['asignatura_id']);
        abort_unless((int) $asignatura->carrera_id === (int) $proyecto->equipo->grupoAcademico->carrera_id, 422);

        $docenteId = null;
        if (! empty($datos['docente_id'])) {
            $docenteId = User::query()
                ->whereKey($datos['docente_id'])
                ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['docente_lider', 'docente_materia']))
                ->value('id');
            abort_unless($docenteId !== null, 422);
        }

        $proyecto->asignaturas()->syncWithoutDetaching([
            $asignatura->id => [
                'docente_id' => $docenteId,
                'participa_evaluacion' => $request->boolean('participa_evaluacion', true),
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ]);

        return $this->volverProyectos($request, 'Asignatura participante vinculada al proyecto correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(User $usuario, Request $request): array
    {
        $periodos = Periodo::query()->orderByDesc('fecha_inicio')->get(['id', 'nombre', 'estado']);
        $periodoSeleccionado = (int) ($request->query('periodo_proyectos')
            ?: $periodos->firstWhere('estado', 'activo')?->id
            ?: $periodos->first()?->id);
        $grupos = GrupoAcademico::query()
            ->with(['carrera:id,clave', 'periodo:id,nombre'])
            ->where('periodo_id', $periodoSeleccionado)
            ->when($usuario->hasRole('docente_lider'), fn ($query) => $query->conMateriaLiderDelDocente($usuario->id))
            ->orderBy('carrera_id')->orderBy('grado')->orderBy('grupo')
            ->get();
        $grupoSeleccionado = $grupos->contains('id', (int) $request->query('grupo_proyectos'))
            ? (int) $request->query('grupo_proyectos')
            : $grupos->first()?->id;

        $proyectos = Proyecto::query()
            ->with([
                'equipo.grupoAcademico.carrera:id,clave,nombre',
                'equipo.asesores:id,nombre,matricula',
                'guiaIntegradora:id,nombre,version',
                'docentes:id,nombre,matricula',
                'asignaturas:id,nombre,clave',
            ])
            ->withCount(['docentes as docentes_count', 'asignaturas as asignaturas_count'])
            ->when($usuario->hasRole('docente_lider'), fn ($q) => $q->whereHas('equipo.grupoAcademico', fn ($g) => $g->conMateriaLiderDelDocente($usuario->id)))
            ->when($usuario->hasRole('docente_materia'), fn ($q) => $q->where(function ($scope) use ($usuario) {
                $scope->whereHas('docentes', fn ($d) => $d->where('usuarios.id', $usuario->id))->orWhereHas('asignaturas', fn ($a) => $a->where('asignaturas_proyecto.docente_id', $usuario->id));
            }))
            ->when($grupoSeleccionado, fn ($query) => $query->whereHas('equipo', fn ($equipo) => $equipo->where('grupo_academico_id', $grupoSeleccionado)))
            ->when(! $grupoSeleccionado, fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('titulo')
            ->get();

        return [
            'proyectos' => $proyectos,
            'grupos' => $grupos,
            'periodos' => $periodos,
            'periodoSeleccionado' => $periodoSeleccionado,
            'grupoSeleccionado' => $grupoSeleccionado,
            'equipos' => Equipo::query()
                ->with('grupoAcademico.carrera:id,clave')
                ->when($usuario->hasRole('docente_lider'), fn ($q) => $q->whereHas('grupoAcademico', fn ($g) => $g->conMateriaLiderDelDocente($usuario->id)))
                ->orderBy('nombre')
                ->get(),
            'guias' => GuiaIntegradora::query()->where('estado', 'publicada')->whereHas('periodo', fn ($q) => $q->where('estado', '!=', 'cerrado'))->orderBy('nombre')->get(['id', 'nombre', 'version', 'estado']),
            'docentes' => User::query()->whereHas('role', fn ($query) => $query->whereIn('nombre', ['docente_lider', 'docente_materia']))->orderBy('nombre')->get(['id', 'nombre', 'matricula', 'carrera_id']),
            'asignaturas' => Asignatura::query()->orderBy('nombre')->get(['id', 'carrera_id', 'grado', 'nombre', 'clave']),
            'apartados' => ApartadoGuia::query()->with('guiaIntegradora:id,nombre')->orderBy('orden')->get(['id', 'guia_integradora_id', 'orden', 'titulo']),
            'metricasProyectos' => [
                ['label' => 'Proyectos', 'value' => (string) $proyectos->count()],
                ['label' => 'Con docentes', 'value' => (string) $proyectos->where('docentes_count', '>', 0)->count()],
                ['label' => 'Multidisciplinarios', 'value' => (string) $proyectos->where('asignaturas_count', '>', 1)->count()],
            ],
        ];
    }

    private function autorizarProyectoAsignado(Request $request, Proyecto $proyecto): void
    {
        abort_unless(
            $request->user()?->hasRole('docente_lider')
            && GrupoAcademico::query()->whereKey($proyecto->equipo->grupo_academico_id)->conMateriaLiderDelDocente((int) $request->user()->id)->exists(),
            403,
        );
    }

    private function volverProyectos(Request $request, string $mensaje): RedirectResponse
    {
        return redirect()->route('modulos.show', array_filter([
            'modulo' => 'proyectos',
            'periodo_proyectos' => $request->input('periodo_proyectos'),
            'grupo_proyectos' => $request->input('grupo_proyectos'),
        ]))->with('estado', $mensaje);
    }
}
