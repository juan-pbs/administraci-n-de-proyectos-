<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\EncargoProyecto;
use App\Models\Equipo;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Proyecto;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ControladorProyectos extends Controller
{
    use AutorizaDireccion;

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
            ...$this->datos($usuario),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole('direccion_coordinacion', 'lider_proyecto'), 403);

        $datos = $request->validate([
            'guia_integradora_id' => ['required', 'exists:guias_integradoras,id'],
            'equipo_id' => ['required', 'exists:equipos,id'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $equipo = Equipo::query()->with('grupoAcademico')->findOrFail($datos['equipo_id']);
        if ($request->user()->hasRole('lider_proyecto')) {
            abort_unless((int) $equipo->grupoAcademico->lider_proyecto_id === (int) $request->user()->id, 403);
        }

        Proyecto::query()->updateOrCreate(
            ['guia_integradora_id' => $datos['guia_integradora_id'], 'equipo_id' => $datos['equipo_id']],
            ['titulo' => $datos['titulo'], 'descripcion' => $datos['descripcion'] ?? null, 'estado' => 'en_proceso'],
        );

        return redirect()->route('modulos.show', 'proyectos')->with('estado', 'Proyecto guardado correctamente.');
    }

    public function asignarDocente(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole('direccion_coordinacion', 'encargado_proyectos'), 403);

        $datos = $request->validate([
            'proyecto_id' => ['required', 'exists:proyectos,id'],
            'docente_id' => ['required', 'exists:usuarios,id'],
            'tipo_participacion' => ['required', 'string', 'max:50'],
        ]);

        $proyecto = Proyecto::query()->with('equipo.grupoAcademico')->findOrFail($datos['proyecto_id']);
        $this->autorizarProyectoAsignado($request, $proyecto);

        $docente = User::query()
            ->whereKey($datos['docente_id'])
            ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['lider_proyecto', 'docente_materia', 'docente_asesor']))
            ->firstOrFail();

        $proyecto->docentes()->syncWithoutDetaching([
            $docente->id => [
                'tipo_participacion' => $datos['tipo_participacion'],
                'activo' => true,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ]);

        return redirect()->route('modulos.show', 'proyectos')->with('estado', 'Docente asignado al proyecto correctamente.');
    }

    public function quitarDocente(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole('direccion_coordinacion', 'encargado_proyectos'), 403);

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

        return redirect()->route('modulos.show', 'proyectos')->with('estado', 'Docente retirado del proyecto correctamente.');
    }

    public function asignarAsignatura(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole('direccion_coordinacion', 'encargado_proyectos'), 403);

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
                ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['lider_proyecto', 'docente_materia', 'docente_asesor']))
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

        return redirect()->route('modulos.show', 'proyectos')->with('estado', 'Asignatura participante vinculada al proyecto correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(User $usuario): array
    {
        $proyectos = Proyecto::query()
            ->with([
                'equipo.grupoAcademico.carrera:id,clave,nombre',
                'equipo.asesores:id,nombre,matricula',
                'guiaIntegradora:id,nombre,version',
                'docentes:id,nombre,matricula',
                'asignaturas:id,nombre,clave',
            ])
            ->withCount(['docentes as docentes_count', 'asignaturas as asignaturas_count'])
            ->when($usuario->hasRole('lider_proyecto'), fn ($q) => $q->whereHas('equipo.grupoAcademico', fn ($g) => $g->where('lider_proyecto_id', $usuario->id)))
            ->when($usuario->hasRole('encargado_proyectos'), fn ($q) => $q->whereHas('equipo.grupoAcademico', fn ($g) => $this->consultaGruposEncargado($g, $usuario)))
            ->when($usuario->hasRole('docente_materia'), fn ($q) => $q->where(function ($scope) use ($usuario) {
                $scope->whereHas('docentes', fn ($d) => $d->where('usuarios.id', $usuario->id))->orWhereHas('asignaturas', fn ($a) => $a->where('asignaturas_proyecto.docente_id', $usuario->id));
            }))
            ->orderBy('titulo')
            ->get();

        return [
            'proyectos' => $proyectos,
            'equipos' => Equipo::query()
                ->with('grupoAcademico.carrera:id,clave')
                ->when($usuario->hasRole('lider_proyecto'), fn ($q) => $q->whereHas('grupoAcademico', fn ($g) => $g->where('lider_proyecto_id', $usuario->id)))
                ->when($usuario->hasRole('encargado_proyectos'), fn ($q) => $q->whereHas('grupoAcademico', fn ($g) => $this->consultaGruposEncargado($g, $usuario)))
                ->orderBy('nombre')
                ->get(),
            'guias' => GuiaIntegradora::query()->orderBy('nombre')->get(['id', 'nombre', 'version', 'estado']),
            'docentes' => User::query()->whereHas('role', fn ($query) => $query->whereIn('nombre', ['lider_proyecto', 'docente_materia', 'docente_asesor']))->orderBy('nombre')->get(['id', 'nombre', 'matricula']),
            'asignaturas' => Asignatura::query()->orderBy('nombre')->get(['id', 'nombre', 'clave']),
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
        if ($request->user()->hasRole('encargado_proyectos')) {
            $this->autorizarGrupoEncargado($request->user(), $proyecto->equipo->grupoAcademico);
        }
    }

    private function autorizarGrupoEncargado(User $usuario, GrupoAcademico $grupo): void
    {
        abort_unless(EncargoProyecto::query()
            ->where('encargado_id', $usuario->id)
            ->where('periodo_id', $grupo->periodo_id)
            ->where('carrera_id', $grupo->carrera_id)
            ->where('cuatrimestre', $grupo->grado)
            ->where('activo', true)
            ->exists(), 403);
    }

    private function consultaGruposEncargado($query, User $usuario): void
    {
        $query->whereExists(function ($subquery) use ($usuario) {
            $subquery->selectRaw('1')
                ->from('encargos_proyecto')
                ->whereColumn('encargos_proyecto.periodo_id', 'grupos_academicos.periodo_id')
                ->whereColumn('encargos_proyecto.carrera_id', 'grupos_academicos.carrera_id')
                ->whereColumn('encargos_proyecto.cuatrimestre', 'grupos_academicos.grado')
                ->where('encargos_proyecto.encargado_id', $usuario->id)
                ->where('encargos_proyecto.activo', true);
        });
    }
}
