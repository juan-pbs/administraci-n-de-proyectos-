<?php

namespace App\Http\Controllers;

use App\Models\Entrega;
use App\Models\Equipo;
use App\Models\FirmaApartadoGuia;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Revision;
use App\Models\User;
use App\Servicios\PlazosProyecto;
use App\Soporte\BusquedaPanel;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->loadMissing('role');
        $role = $user->role?->nombre ?? 'estudiante';
        $periodo = Periodo::query()->where('estado', 'activo')->latest('fecha_inicio')->first()
            ?? Periodo::query()->latest('fecha_inicio')->first();
        $panel = SistemaInterfaz::dashboardPara($role);
        $navegacion = SistemaInterfaz::navegacionPara($role);
        $consultaBusqueda = BusquedaPanel::disponible($role)
            ? ($request->validate(['buscar' => ['nullable', 'string', 'max:160']])['buscar'] ?? '') : '';

        $panel['stats'] = $this->metricas($role, $user->id, $periodo?->id);
        $alertas = $this->alertasOperativas($role, $user->id, $periodo?->id);
        $grupos = GrupoAcademico::query()
            ->with(['carrera:id,clave', 'periodo:id,nombre', 'liderProyecto:id,nombre'])
            ->withCount(['alumnos', 'equipos'])
            ->when($periodo, fn ($query) => $query->where('periodo_id', $periodo->id))
            ->when($role === 'docente_lider', fn ($query) => $query->conMateriaLiderDelDocente($user->id))
            ->when($role === 'docente_materia', fn ($query) => $query->whereHas('equipos.proyectos', fn ($proyectos) => $proyectos->where(function ($scope) use ($user) {
                $scope->whereHas('docentes', fn ($docentes) => $docentes->where('usuarios.id', $user->id))
                    ->orWhereHas('asignaturas', fn ($asignaturas) => $asignaturas->where('asignaturas_proyecto.docente_id', $user->id));
            })))
            ->when($role === 'estudiante', fn ($query) => $query->where('id', $user->grupo_academico_id))
            ->orderBy('carrera_id')->orderBy('grado')->orderBy('grupo')
            ->limit(6)->get();
        $asignacionesDocente = collect();

        if ($role === 'docente_materia') {
            $asignacionesDocente = FirmaApartadoGuia::query()
                ->with(['asignatura:id,nombre,clave', 'apartadoGuia:id,guia_integradora_id,orden,titulo,ponderacion,fecha_limite', 'apartadoGuia.guiaIntegradora:id,periodo_id,nombre'])
                ->where('docente_id', $user->id)
                ->whereHas('apartadoGuia', fn ($apartado) => $apartado->where('requiere_codigo', false))
                ->when($periodo, fn ($query) => $query->whereHas('apartadoGuia.guiaIntegradora', fn ($guia) => $guia->where('periodo_id', $periodo->id)))
                ->orderBy('apartado_guia_id')->limit(6)->get();
        }
        $resumenEntregasEstudiante = $role === 'estudiante'
            ? $this->resumenEntregasEstudiante($user->id)
            : collect();

        return view('dashboard', [
            'role' => $role,
            'roleName' => $user->role?->nombre_visible ?? 'Estudiante',
            'panel' => $panel,
            'periodoActual' => $periodo,
            'alertasOperativas' => $alertas,
            'gruposResumen' => $grupos,
            'asignacionesDocente' => $asignacionesDocente,
            'resumenEntregasEstudiante' => $resumenEntregasEstudiante,
            'navegacion' => $navegacion,
            'consultaBusqueda' => $consultaBusqueda,
            'resultadosBusqueda' => BusquedaPanel::buscar($role, $consultaBusqueda, $navegacion),
        ]);
    }

    public function buscar(Request $request)
    {
        $rol = $request->user()->loadMissing('role')->role?->nombre ?? '';
        abort_unless(BusquedaPanel::disponible($rol), 403);
        $datos = $request->validate(['buscar' => ['nullable', 'string', 'max:160']]);

        return response()->json(['resultados' => BusquedaPanel::buscar($rol, $datos['buscar'] ?? '')]);
    }

    /**
     * @return array<int, array{label: string, value: int|string}>
     */
    private function metricas(string $rol, int $usuarioId, ?int $periodoId): array
    {
        $grupos = GrupoAcademico::query()
            ->when($periodoId, fn ($query) => $query->where('periodo_id', $periodoId));

        if ($rol === 'docente_lider') {
            $grupos->conMateriaLiderDelDocente($usuarioId);
        } elseif ($rol === 'docente_materia') {
            $grupos->whereHas('equipos.proyectos', fn ($proyectos) => $proyectos->where(function ($scope) use ($usuarioId) {
                $scope->whereHas('docentes', fn ($docentes) => $docentes->where('usuarios.id', $usuarioId))
                    ->orWhereHas('asignaturas', fn ($asignaturas) => $asignaturas->where('asignaturas_proyecto.docente_id', $usuarioId));
            }));
        } elseif ($rol === 'estudiante') {
            $grupos->whereHas('alumnos', fn ($alumnos) => $alumnos->where('usuarios.id', $usuarioId));
        }

        $ids = (clone $grupos)->pluck('id');
        $equipos = Equipo::query()->whereIn('grupo_academico_id', $ids);
        $proyectos = Proyecto::query()->whereHas('equipo', fn ($query) => $query->whereIn('grupo_academico_id', $ids));

        if ($rol === 'coordinacion') {
            return [
                ['label' => 'Carreras operando', 'value' => (clone $grupos)->distinct()->count('carrera_id')],
                ['label' => 'Grupos', 'value' => $ids->count()],
                ['label' => 'Alumnos', 'value' => User::query()->whereIn('grupo_academico_id', $ids)->count()],
                ['label' => 'Guías configuradas', 'value' => GuiaIntegradora::query()->when($periodoId, fn ($query) => $query->where('periodo_id', $periodoId))->count()],
            ];
        }

        if ($rol === 'docente_materia') {
            $apartados = FirmaApartadoGuia::query()
                ->where('docente_id', $usuarioId)
                ->whereHas('apartadoGuia', fn ($apartado) => $apartado->where('requiere_codigo', false))
                ->when($periodoId, fn ($query) => $query->whereHas('apartadoGuia.guiaIntegradora', fn ($guia) => $guia->where('periodo_id', $periodoId)))
                ->pluck('apartado_guia_id');
            $entregas = Entrega::query()->whereIn('apartado_guia_id', $apartados)
                ->whereHas('proyecto', fn ($proyectos) => $this->soloProyectosAsignados($proyectos, $usuarioId));
            $revisadas = Revision::query()->where('revisor_id', $usuarioId)->whereHas('entrega', fn ($query) => $query->whereIn('apartado_guia_id', $apartados));

            return [
                ['label' => 'Apartados asignados', 'value' => $apartados->count()],
                ['label' => 'Entregas recibidas', 'value' => (clone $entregas)->count()],
                ['label' => 'Por revisar', 'value' => (clone $entregas)->whereDoesntHave('revisiones', fn ($query) => $query->where('revisor_id', $usuarioId))->count()],
                ['label' => 'Revisadas', 'value' => $revisadas->count()],
            ];
        }

        if ($rol === 'estudiante') {
            $resumen = $this->resumenEntregasEstudiante($usuarioId);

            return [
                ['label' => 'Validadas', 'value' => $resumen->where('estado', 'validada')->count()],
                ['label' => 'Rechazadas', 'value' => $resumen->where('estado', 'rechazada')->count()],
                ['label' => 'Sin entregar', 'value' => $resumen->where('estado', 'sin_entregar')->count()],
                ['label' => 'Pendientes de revisión', 'value' => $resumen->where('estado', 'pendiente')->count()],
            ];
        }

        return [
            ['label' => 'Grupos', 'value' => $ids->count()],
            ['label' => 'Alumnos', 'value' => User::query()->whereIn('grupo_academico_id', $ids)->count()],
            ['label' => 'Equipos', 'value' => (clone $equipos)->count()],
            ['label' => 'Proyectos', 'value' => $proyectos->count()],
        ];
    }

    /**
     * @return array<int, array{label: string, value: int, detail: string, tone: string}>
     */
    private function alertasOperativas(string $rol, int $usuarioId, ?int $periodoId): array
    {
        if ($rol === 'docente_materia') {
            $apartados = FirmaApartadoGuia::query()
                ->with('apartadoGuia:id,guia_integradora_id,fecha_limite')
                ->where('docente_id', $usuarioId)
                ->whereHas('apartadoGuia', fn ($apartado) => $apartado->where('requiere_codigo', false))
                ->when($periodoId, fn ($query) => $query->whereHas('apartadoGuia.guiaIntegradora', fn ($guia) => $guia->where('periodo_id', $periodoId)))
                ->get()->pluck('apartadoGuia')->filter();

            $vencidasSinEntrega = $apartados
                ->filter(fn ($apartado) => $apartado->fecha_limite?->isPast())
                ->sum(function ($apartado) use ($usuarioId): int {
                    $proyectosEsperados = Proyecto::query()
                        ->where('guia_integradora_id', $apartado->guia_integradora_id)
                        ->tap(fn ($query) => $this->soloProyectosAsignados($query, $usuarioId))
                        ->pluck('id');
                    $proyectosEntregados = Entrega::query()
                        ->where('apartado_guia_id', $apartado->id)
                        ->whereIn('proyecto_id', $proyectosEsperados)
                        ->distinct()->count('proyecto_id');

                    return max(0, $proyectosEsperados->count() - $proyectosEntregados);
                });

            $recibidasSinRevision = Entrega::query()
                ->whereIn('apartado_guia_id', $apartados->pluck('id'))
                ->whereHas('proyecto', fn ($proyectos) => $this->soloProyectosAsignados($proyectos, $usuarioId))
                ->whereDoesntHave('revisiones', fn ($query) => $query->where('revisor_id', $usuarioId))
                ->count();

            return [
                [
                    'label' => 'Vencidas sin entrega',
                    'value' => $vencidasSinEntrega,
                    'detail' => 'Equipos cuyo plazo terminó y no registraron ningún avance.',
                    'tone' => 'red',
                ],
                [
                    'label' => 'Recibidas sin revisar',
                    'value' => $recibidasSinRevision,
                    'detail' => 'Entregas disponibles que todavía requieren calificación o correcciones.',
                    'tone' => 'amber',
                ],
            ];
        }

        if ($rol !== 'coordinacion') {
            return [];
        }

        $grupos = GrupoAcademico::query()
            ->when($periodoId, fn ($query) => $query->where('periodo_id', $periodoId));
        $equipos = Equipo::query()
            ->when($periodoId, fn ($query) => $query->whereHas('grupoAcademico', fn ($grupo) => $grupo->where('periodo_id', $periodoId)));

        $actual = $periodoId ? Periodo::find($periodoId) : null;
        $cierre = $actual && $actual->estado === 'activo' && Carbon::parse($actual->fecha_fin)->endOfDay()->isPast()
            ? [['label' => 'Periodo pendiente de cierre', 'value' => 1, 'detail' => $actual->nombre.': la fecha de fin pasó. Revisa sus pendientes en Periodos.', 'tone' => 'amber']]
            : [];

        return [
            ...$cierre,
            [
                'label' => 'Grupos sin docente líder',
                'value' => (clone $grupos)->whereNull('lider_proyecto_id')->count(),
                'detail' => 'Requieren una asignación antes de organizar equipos.',
                'tone' => 'amber',
            ],
            [
                'label' => 'Grupos sin equipos',
                'value' => (clone $grupos)->doesntHave('equipos')->count(),
                'detail' => 'Todavía no iniciaron su organización de alumnos.',
                'tone' => 'blue',
            ],
            [
                'label' => 'Equipos sin proyecto',
                'value' => (clone $equipos)->doesntHave('proyectos')->count(),
                'detail' => 'Están formados, pero aún no tienen proyecto asignado.',
                'tone' => 'amber',
            ],
            [
                'label' => 'Guías en borrador',
                'value' => GuiaIntegradora::query()
                    ->when($periodoId, fn ($query) => $query->where('periodo_id', $periodoId))
                    ->where('estado', 'borrador')->count(),
                'detail' => 'Aún no están publicadas para el periodo.',
                'tone' => 'blue',
            ],
        ];
    }

    private function soloProyectosAsignados($query, int $usuarioId)
    {
        return $query->where(function ($scope) use ($usuarioId) {
            $scope->whereHas('docentes', fn ($docentes) => $docentes->where('usuarios.id', $usuarioId))
                ->orWhereHas('asignaturas', fn ($asignaturas) => $asignaturas->where('asignaturas_proyecto.docente_id', $usuarioId));
        });
    }

    private function resumenEntregasEstudiante(int $usuarioId)
    {
        $equipo = Equipo::query()
            ->whereHas('integrantes', fn ($query) => $query->where('usuarios.id', $usuarioId))
            ->where('estado', 'activo')->latest('id')->first();
        $proyecto = $equipo?->proyectos()->with('guiaIntegradora.apartados')->latest('id')->first();

        if (! $proyecto?->guiaIntegradora) {
            return collect();
        }

        $entregas = Entrega::query()
            ->with(['revisiones' => fn ($query) => $query->latest('revisado_en')])
            ->where('proyecto_id', $proyecto->id)
            ->orderByDesc('version')->get()
            ->unique('apartado_guia_id')->keyBy('apartado_guia_id');

        return $proyecto->guiaIntegradora->apartados
            ->sortBy('orden')->values()
            ->map(function ($apartado) use ($entregas, $proyecto): array {
                $apartado->fecha_limite = app(PlazosProyecto::class)->fecha($proyecto, $apartado);
                $entrega = $entregas->get($apartado->id);
                $revision = $entrega?->revisiones?->first();
                $estado = match (true) {
                    ! $entrega => 'sin_entregar',
                    $revision?->resultado === 'aprobada' => 'validada',
                    in_array($revision?->resultado, ['rechazada', 'correccion'], true) => 'rechazada',
                    default => 'pendiente',
                };

                return [
                    'apartado' => $apartado,
                    'entrega' => $entrega,
                    'revision' => $revision,
                    'estado' => $estado,
                ];
            });
    }
}
