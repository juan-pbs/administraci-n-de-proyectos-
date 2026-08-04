<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControladorEquipos extends Controller
{
    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';

        abort_unless(SistemaInterfaz::puedeVer($rol, 'equipos'), 403);

        return view('modulos.gestion-proyectos.equipos', [
            'active' => 'equipos',
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => SistemaInterfaz::pagina('equipos'),
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
            ...$this->datos($usuario, $request),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $this->autorizarGestionEquipos($request);

        $datos = $request->validate([
            'grupo_academico_id' => ['required', 'exists:grupos_academicos,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'contexto_proyecto' => ['nullable', 'string', 'max:3000'],
        ]);

        $grupo = GrupoAcademico::query()->findOrFail($datos['grupo_academico_id']);
        $this->autorizarGrupo($request, $grupo);

        DB::transaction(function () use ($grupo, $datos): void {
            GrupoAcademico::query()->whereKey($grupo->id)->lockForUpdate()->firstOrFail();
            $hayAlumnosLibres = User::query()
                ->where('grupo_academico_id', $grupo->id)
                ->whereHas('role', fn ($query) => $query->where('nombre', 'estudiante'))
                ->whereDoesntHave('equiposComoIntegrante')
                ->exists();
            abort_unless($hayAlumnosLibres, 422, 'El grupo no tiene alumnos libres para crear otro equipo.');

            $equipo = Equipo::query()->firstOrNew([
                'grupo_academico_id' => $grupo->id,
                'nombre' => $datos['nombre'],
            ]);
            if (! $equipo->exists) {
                $equipo->numero = (int) Equipo::query()
                    ->where('grupo_academico_id', $grupo->id)
                    ->lockForUpdate()
                    ->max('numero') + 1;
            }
            $equipo->contexto_proyecto = $datos['contexto_proyecto'] ?? 'Contexto pendiente de definir por el líder de proyecto.';
            $equipo->estado = 'activo';
            $equipo->save();
        });

        return $this->volverEquipos($request, 'Equipo guardado correctamente.');
    }

    public function asignarAlumno(Request $request): RedirectResponse
    {
        $this->autorizarGestionEquipos($request);

        $datos = $request->validate([
            'equipo_id' => ['required', 'exists:equipos,id'],
            'estudiante_id' => ['required', 'exists:usuarios,id'],
        ]);

        $equipo = Equipo::query()->with('grupoAcademico')->findOrFail($datos['equipo_id']);
        $this->autorizarGrupo($request, $equipo->grupoAcademico);
        $estudiante = User::query()->findOrFail($datos['estudiante_id']);
        abort_unless($estudiante->hasRole('estudiante'), 422);
        abort_unless((int) $estudiante->grupo_academico_id === (int) $equipo->grupo_academico_id, 422);

        $yaTieneEquipo = DB::table('integrantes_equipo')
            ->where('estudiante_id', $estudiante->id)
            ->where('activo', true)
            ->where('equipo_id', '!=', $equipo->id)
            ->exists();
        abort_unless(! $yaTieneEquipo, 422, 'El alumno ya pertenece a otro equipo.');

        $equipo->integrantes()->syncWithoutDetaching([
            $estudiante->id => ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        return $this->volverEquipos($request, 'Alumno asignado al equipo correctamente.');
    }

    public function asignarAsesor(Request $request): RedirectResponse
    {
        $this->autorizarGestionEquipos($request);

        $datos = $request->validate([
            'equipo_id' => ['required', 'exists:equipos,id'],
            'asesor_id' => ['required', 'exists:usuarios,id'],
            'principal' => ['nullable', 'boolean'],
        ]);

        $equipo = Equipo::query()->with('grupoAcademico')->findOrFail($datos['equipo_id']);
        $this->autorizarGrupo($request, $equipo->grupoAcademico);
        $asesor = User::query()
            ->whereKey($datos['asesor_id'])
            ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['docente_lider', 'docente_materia']))
            ->firstOrFail();

        if ($request->boolean('principal')) {
            $equipo->asesores()
                ->pluck('usuarios.id')
                ->each(fn (int $asesorId) => $equipo->asesores()->updateExistingPivot($asesorId, [
                    'principal' => false,
                    'actualizado_en' => now(),
                ]));
        }

        $equipo->asesores()->syncWithoutDetaching([
            $asesor->id => [
                'principal' => $request->boolean('principal'),
                'activo' => true,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ]);

        return $this->volverEquipos($request, 'Asesor asignado al equipo correctamente.');
    }

    public function quitarAlumno(Request $request): RedirectResponse
    {
        $this->autorizarGestionEquipos($request);

        $datos = $request->validate([
            'equipo_id' => ['required', 'exists:equipos,id'],
            'estudiante_id' => ['required', 'exists:usuarios,id'],
        ]);

        $equipo = Equipo::query()->findOrFail($datos['equipo_id']);
        $equipo->loadMissing('grupoAcademico');
        $this->autorizarGrupo($request, $equipo->grupoAcademico);
        $equipo->integrantes()->updateExistingPivot($datos['estudiante_id'], [
            'activo' => false,
            'actualizado_en' => now(),
        ]);

        if ((int) $equipo->lider_id === (int) $datos['estudiante_id']) {
            $equipo->forceFill(['lider_id' => null])->save();
        }

        return $this->volverEquipos($request, 'Alumno retirado del equipo correctamente.');
    }

    public function quitarAsesor(Request $request): RedirectResponse
    {
        $this->autorizarGestionEquipos($request);

        $datos = $request->validate([
            'equipo_id' => ['required', 'exists:equipos,id'],
            'asesor_id' => ['required', 'exists:usuarios,id'],
        ]);

        $equipo = Equipo::query()->with('grupoAcademico')->findOrFail($datos['equipo_id']);
        $this->autorizarGrupo($request, $equipo->grupoAcademico);
        $equipo->asesores()->updateExistingPivot($datos['asesor_id'], [
            'activo' => false,
            'principal' => false,
            'actualizado_en' => now(),
        ]);

        return $this->volverEquipos($request, 'Asesor retirado del equipo correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(User $usuario, Request $request): array
    {
        $periodos = \App\Models\Periodo::query()->orderByDesc('fecha_inicio')->get(['id', 'nombre', 'estado']);
        $periodoSeleccionado = (int) ($request->query('periodo_equipos')
            ?: $periodos->firstWhere('estado', 'activo')?->id
            ?: $periodos->first()?->id);
        $grupos = GrupoAcademico::query()
            ->with(['carrera:id,clave', 'periodo:id,nombre'])
            ->where('periodo_id', $periodoSeleccionado)
            ->when($usuario->hasRole('docente_lider'), fn ($query) => $query->conLiderProyectoDelDocente($usuario->id))
            ->orderBy('carrera_id')->orderBy('grado')->orderBy('grupo')
            ->get();
        $grupos->each(function (GrupoAcademico $grupo): void {
            $grupo->setAttribute('alumnos_libres_count', User::query()
                ->where('grupo_academico_id', $grupo->id)
                ->whereHas('role', fn ($query) => $query->where('nombre', 'estudiante'))
                ->whereDoesntHave('equiposComoIntegrante')
                ->count());
        });
        $grupoSeleccionado = $grupos->contains('id', (int) $request->query('grupo_equipos'))
            ? (int) $request->query('grupo_equipos')
            : $grupos->first()?->id;

        $equipos = Equipo::query()
            ->with([
                'grupoAcademico.carrera:id,nombre,clave',
                'grupoAcademico.periodo:id,nombre',
                'grupoAcademico.liderProyecto:id,nombre,matricula',
                'integrantes:id,nombre,matricula,grupo_academico_id',
                'asesores:id,nombre,matricula',
                'proyectos.guiaIntegradora:id,nombre,version',
            ])
            ->withCount(['integrantes as integrantes_count', 'asesores as asesores_count'])
            ->when($usuario->hasRole('docente_lider'), fn ($q) => $q->whereHas('grupoAcademico', fn ($g) => $g->conLiderProyectoDelDocente($usuario->id)))
            ->when($grupoSeleccionado, fn ($query) => $query->where('grupo_academico_id', $grupoSeleccionado))
            ->when(! $grupoSeleccionado, fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('numero')->orderBy('nombre')
            ->get();

        return [
            'equipos' => $equipos,
            'grupos' => $grupos,
            'periodos' => $periodos,
            'periodoSeleccionado' => $periodoSeleccionado,
            'grupoSeleccionado' => $grupoSeleccionado,
            'estudiantes' => User::query()
                ->whereHas('role', fn ($query) => $query->where('nombre', 'estudiante'))
                ->whereDoesntHave('equiposComoIntegrante')
                ->when($usuario->hasRole('docente_lider'), fn ($query) => $query->whereHas('grupoAcademico', fn ($grupo) => $grupo->conLiderProyectoDelDocente($usuario->id)))
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'matricula', 'grupo_academico_id']),
            'docentes' => User::query()->whereHas('role', fn ($query) => $query->whereIn('nombre', ['docente_lider', 'docente_materia']))->orderBy('nombre')->get(['id', 'nombre', 'matricula']),
            'guias' => GuiaIntegradora::query()
                ->with('asignatura:id,carrera_id,grado')
                ->where('estado', 'publicada')
                ->orderBy('nombre')
                ->get(['id', 'periodo_id', 'asignatura_id', 'nombre', 'cuatrimestre', 'version']),
            'metricasEquipos' => [
                ['label' => 'Equipos', 'value' => (string) $equipos->count()],
                ['label' => 'Integrantes', 'value' => (string) $equipos->sum('integrantes_count')],
                ['label' => 'Asesores asignados', 'value' => (string) $equipos->sum('asesores_count')],
            ],
        ];
    }

    private function autorizarGestionEquipos(Request $request): void
    {
        abort_unless($request->user()?->hasRole('docente_lider'), 403);
    }

    private function autorizarGrupo(Request $request, GrupoAcademico $grupo): void
    {
        abort_unless(GrupoAcademico::query()->whereKey($grupo->id)->conLiderProyectoDelDocente((int) $request->user()->id)->exists(), 403);
    }

    private function volverEquipos(Request $request, string $mensaje): RedirectResponse
    {
        return redirect()->route('modulos.show', array_filter([
            'modulo' => 'equipos',
            'periodo_equipos' => $request->input('periodo_equipos'),
            'grupo_equipos' => $request->input('grupo_equipos'),
        ]))->with('estado', $mensaje);
    }

}
