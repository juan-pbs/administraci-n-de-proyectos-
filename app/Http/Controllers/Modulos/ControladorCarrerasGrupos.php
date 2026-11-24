<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\Carrera;
use App\Models\GrupoAcademico;
use App\Models\Periodo;
use App\Models\User;
use App\Soporte\FormatosCapturaAcademica;
use App\Soporte\ProtegeHistorial;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        if (is_string($request->input('clave'))) {
            $request->merge(['clave' => strtoupper(trim($request->input('clave')))]);
        }

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120', FormatosCapturaAcademica::regla(FormatosCapturaAcademica::NOMBRE_CARRERA)],
            'clave' => ['required', 'string', 'max:20', FormatosCapturaAcademica::regla(FormatosCapturaAcademica::CLAVE_CARRERA), 'unique:carreras,clave'],
        ], [
            'nombre.regex' => FormatosCapturaAcademica::MENSAJE_NOMBRE_CARRERA,
            'clave.regex' => FormatosCapturaAcademica::MENSAJE_CLAVE_CARRERA,
        ]);

        Carrera::query()->create([
            'nombre' => $datos['nombre'],
            'clave' => $datos['clave'],
            'estado' => 'activa',
        ]);

        return redirect()->route('modulos.show', 'carreras-grupos')->with('estado', 'Carrera registrada correctamente.');
    }

    public function guardarGrupo(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'periodo_id' => ['required', 'exists:periodos,id'],
            'carrera_id' => ['required', Rule::exists('carreras', 'id')->where('estado', 'activa')],
            'grado' => ['required', 'integer', 'min:1', 'max:12'],
            'grupo' => ['required', 'string', 'size:1', 'regex:/\A[A-Ma-m]\z/'],
        ], [
            'grupo.regex' => 'El grupo debe ser una sola letra de A a M.',
            'grupo.size' => 'El grupo debe contener exactamente una letra.',
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

    public function actualizarCarrera(Request $request, Carrera $carrera): RedirectResponse
    {
        $this->autorizarDireccion($request);
        if (is_string($request->input('clave'))) {
            $request->merge(['clave' => strtoupper(trim($request->input('clave')))]);
        }
        $carrera->update($request->validate([
            'nombre' => ['required', 'string', 'max:120', FormatosCapturaAcademica::regla(FormatosCapturaAcademica::NOMBRE_CARRERA)],
            'clave' => ['required', 'string', 'max:20', FormatosCapturaAcademica::regla(FormatosCapturaAcademica::CLAVE_CARRERA), Rule::unique('carreras', 'clave')->ignore($carrera)],
            'estado' => ['required', 'in:activa,inactiva'],
        ], [
            'nombre.regex' => FormatosCapturaAcademica::MENSAJE_NOMBRE_CARRERA,
            'clave.regex' => FormatosCapturaAcademica::MENSAJE_CLAVE_CARRERA,
        ]));

        return back()->with('estado', 'Carrera actualizada correctamente.');
    }

    public function eliminarCarrera(Request $request, Carrera $carrera): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $request->validate(['confirmar_eliminacion' => ['accepted']]);
        $carrera = Carrera::whereKey($carrera->id)->lockForUpdate()->firstOrFail();
        ProtegeHistorial::sinReferencias($carrera->id, [
            'grupos_academicos' => 'carrera_id', 'usuarios' => 'carrera_id',
            'asignaturas' => 'carrera_id', 'encargos_proyecto' => 'carrera_id', 'docentes_carrera' => 'carrera_id',
        ], 'La carrera tiene registros vinculados. Puedes desactivarla para conservarlos.');
        $carrera->delete();

        return redirect()->route('modulos.carreras-grupos')->with('estado', 'Carrera eliminada.');
    }

    public function actualizarGrupo(Request $request, GrupoAcademico $grupo): RedirectResponse
    {
        $this->autorizarDireccion($request);
        if (is_string($request->input('grupo'))) {
            $request->merge(['grupo' => mb_strtoupper(trim($request->input('grupo')))]);
        }
        $datos = $request->validate([
            'periodo_id' => ['required', 'exists:periodos,id'],
            'carrera_id' => ['required', 'exists:carreras,id'],
            'grado' => ['required', 'integer', 'between:1,12'],
            'grupo' => ['required', 'string', 'regex:/\A[A-M]\z/'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);
        $grupo = GrupoAcademico::whereKey($grupo->id)->lockForUpdate()->firstOrFail();
        $grupo->fill($datos);
        if ($grupo->isDirty(['periodo_id', 'carrera_id', 'grado']) &&
            ($grupo->lider_proyecto_id || $grupo->equipos()->exists() || $grupo->alumnos()->exists())) {
            throw ValidationException::withMessages(['grupo' => 'Con alumnos, equipos o líder asignado solo puedes cambiar la letra y el estado del grupo.']);
        }
        $duplicado = GrupoAcademico::whereNot('id', $grupo->id)
            ->where($grupo->only(['periodo_id', 'carrera_id', 'grado', 'grupo']))->exists();
        if ($duplicado) {
            throw ValidationException::withMessages(['grupo' => 'Ya existe ese grupo para la carrera, el periodo y el cuatrimestre seleccionados.']);
        }
        $grupo->nombre = $grupo->grado.$grupo->grupo;
        $grupo->save();

        return redirect()->route('modulos.carreras-grupos', [
            'periodo_grupos' => $grupo->periodo_id, 'carrera_grupos' => $grupo->carrera_id,
        ])->with('estado', 'Grupo actualizado correctamente.');
    }

    public function eliminarGrupo(Request $request, GrupoAcademico $grupo): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $request->validate(['confirmar_eliminacion' => ['accepted']]);
        $grupo = GrupoAcademico::whereKey($grupo->id)->lockForUpdate()->firstOrFail();
        ProtegeHistorial::sinReferencias($grupo->id, ['usuarios' => 'grupo_academico_id', 'equipos' => 'grupo_academico_id'],
            'El grupo tiene alumnos o equipos vinculados. Puedes desactivarlo para conservarlos.');
        $grupo->delete();

        return back()->with('estado', 'Grupo eliminado.');
    }

    public function asignarDocenteCarrera(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'carrera_id' => ['required', Rule::exists('carreras', 'id')->where('estado', 'activa')],
            'docente_id' => ['required', 'exists:usuarios,id'],
        ]);

        $carrera = Carrera::query()->findOrFail($datos['carrera_id']);
        $carrera->docentes()->syncWithoutDetaching([
            $datos['docente_id'] => ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        return redirect()->route('modulos.show', 'carreras-grupos')->with('estado', 'Docente vinculado a la carrera. Para asignarlo como líder de un grupo, abre Jerarquía.');
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
                ['label' => 'Carreras activas', 'value' => (string) $carreras->where('estado', 'activa')->count()],
                ['label' => 'Grupos en la carrera', 'value' => (string) $gruposTabla->count()],
                ['label' => 'Alumnos en la carrera', 'value' => (string) $gruposTabla->sum('alumnos_count')],
                ['label' => 'Docentes por carrera', 'value' => (string) $carreras->sum(fn ($carrera) => $carrera->docentes->count())],
            ],
        ];
    }
}
