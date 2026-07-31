<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\Carrera;
use App\Models\GrupoAcademico;
use App\Models\Periodo;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ControladorCarrerasGrupos extends Controller
{
    use AutorizaDireccion;

    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';

        abort_unless(SistemaInterfaz::puedeVer($rol, 'carreras-grupos'), 403);

        return view('modulos.control-academico.carreras-grupos', [
            'active' => 'carreras-grupos',
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => SistemaInterfaz::pagina('carreras-grupos'),
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
            ...$this->datos($request),
        ]);
    }

    public function guardarCarrera(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'clave' => ['required', 'string', 'max:30', 'unique:carreras,clave'],
        ]);

        Carrera::query()->create([
            'nombre' => $datos['nombre'],
            'clave' => mb_strtoupper($datos['clave']),
            'estado' => 'activa',
        ]);

        return redirect()->route('modulos.show', 'carreras-grupos')->with('estado', 'Carrera registrada correctamente.');
    }

    public function guardarGrupo(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'periodo_id' => ['required', 'exists:periodos,id'],
            'carrera_id' => ['required', 'exists:carreras,id'],
            'grado' => ['required', 'integer', 'min:1', 'max:12'],
            'grupo' => ['required', 'string', 'max:10'],
        ]);

        $grupo = mb_strtoupper($datos['grupo']);

        GrupoAcademico::query()->updateOrCreate(
            [
                'periodo_id' => $datos['periodo_id'],
                'carrera_id' => $datos['carrera_id'],
                'grado' => $datos['grado'],
                'grupo' => $grupo,
            ],
            ['nombre' => $datos['grado'].$grupo],
        );

        return redirect()->route('modulos.show', 'carreras-grupos')->with('estado', 'Grupo académico guardado correctamente.');
    }

    public function asignarDocenteCarrera(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'carrera_id' => ['required', 'exists:carreras,id'],
            'docente_id' => ['required', 'exists:usuarios,id'],
        ]);

        $carrera = Carrera::query()->findOrFail($datos['carrera_id']);
        $carrera->docentes()->syncWithoutDetaching([
            $datos['docente_id'] => ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        return redirect()->route('modulos.show', 'carreras-grupos')->with('estado', 'Docente asignado a la carrera correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(Request $request): array
    {
        $periodos = Periodo::query()->orderByDesc('fecha_inicio')->get(['id', 'nombre', 'estado']);
        $periodoSeleccionado = $request->query('periodo_grupos')
            ?: $periodos->firstWhere('estado', 'activo')?->id
            ?: $periodos->first()?->id;
        $carrerasBase = Carrera::query()->orderBy('nombre')->get(['id', 'nombre', 'clave', 'estado']);
        $carreraSeleccionada = $request->query('carrera_grupos') ?: $carrerasBase->first()?->id;

        $filtros = [
            'busqueda' => trim((string) $request->query('busqueda_grupos', '')),
            'carrera_id' => $carreraSeleccionada,
            'periodo_id' => $periodoSeleccionado,
            'grado' => $request->query('grado_grupos'),
            'grupo' => $request->query('grupo_grupos'),
        ];

        $carreras = Carrera::query()
            ->with(['docentes:id,nombre,matricula', 'gruposAcademicos.periodo'])
            ->withCount([
                'gruposAcademicos as grupos_count' => fn ($query) => $query->where('periodo_id', $periodoSeleccionado),
                'usuarios as alumnos_count' => fn ($query) => $query
                    ->whereHas('role', fn ($role) => $role->where('nombre', 'estudiante'))
                    ->whereHas('grupoAcademico', fn ($grupo) => $grupo->where('periodo_id', $periodoSeleccionado)),
            ])
            ->orderBy('nombre')
            ->get();

        $consultaGrupos = GrupoAcademico::query()
            ->with(['carrera:id,nombre,clave', 'periodo:id,nombre,estado', 'liderProyecto:id,nombre,matricula'])
            ->withCount([
                'alumnos as alumnos_count' => fn ($query) => $query->whereHas('role', fn ($role) => $role->where('nombre', 'estudiante')),
                'equipos as equipos_count',
            ]);

        if ($filtros['busqueda'] !== '') {
            $busqueda = $filtros['busqueda'];
            $consultaGrupos->where(function ($query) use ($busqueda) {
                $query
                    ->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('grado', 'like', "%{$busqueda}%")
                    ->orWhere('grupo', 'like', "%{$busqueda}%")
                    ->orWhereHas('carrera', fn ($carrera) => $carrera
                        ->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('clave', 'like', "%{$busqueda}%"))
                    ->orWhereHas('periodo', fn ($periodo) => $periodo->where('nombre', 'like', "%{$busqueda}%"));
            });
        }

        $consultaGrupos
            ->when($filtros['carrera_id'], fn ($query) => $query->where('carrera_id', $filtros['carrera_id']))
            ->when($filtros['periodo_id'], fn ($query) => $query->where('periodo_id', $filtros['periodo_id']));

        if ($filtros['grado']) {
            $consultaGrupos->where('grado', $filtros['grado']);
        }

        if ($filtros['grupo']) {
            $consultaGrupos->where('grupo', $filtros['grupo']);
        }

        $gruposTabla = $consultaGrupos
            ->orderBy('grado')
            ->orderBy('grupo')
            ->get();

        $grupos = GrupoAcademico::query()
            ->with(['carrera:id,nombre,clave', 'periodo:id,nombre,estado'])
            ->orderBy('grado')
            ->orderBy('grupo')
            ->get();

        return [
            'carreras' => $carreras,
            'grupos' => $grupos,
            'gruposTabla' => $gruposTabla,
            'filtrosGrupos' => $filtros,
            'opcionesGrados' => $grupos->pluck('grado')->unique()->sort()->values(),
            'opcionesGrupos' => $grupos->pluck('grupo')->unique()->sort()->values(),
            'periodos' => $periodos,
            'docentes' => User::query()->whereHas('role', fn ($query) => $query->whereIn('nombre', ['docente_lider', 'docente_materia']))->orderBy('nombre')->get(['id', 'nombre', 'matricula', 'carrera_id']),
            'metricasCarreras' => [
                ['label' => 'Carreras activas', 'value' => (string) $carreras->count()],
                ['label' => 'Grupos en la carrera', 'value' => (string) $gruposTabla->count()],
                ['label' => 'Alumnos en la carrera', 'value' => (string) $gruposTabla->sum('alumnos_count')],
                ['label' => 'Docentes por carrera', 'value' => (string) $carreras->sum(fn ($carrera) => $carrera->docentes->count())],
            ],
        ];
    }
}
