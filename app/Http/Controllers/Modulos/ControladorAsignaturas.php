<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Periodo;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControladorAsignaturas extends Controller
{
    use AutorizaDireccion;

    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';

        abort_unless(SistemaInterfaz::puedeVer($rol, 'asignaturas'), 403);

        return view('modulos.control-academico.asignaturas', [
            'active' => 'asignaturas',
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => SistemaInterfaz::pagina('asignaturas'),
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
            ...$this->datos($request),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('coordinacion'), 403);

        $datos = $request->validate([
            'carrera_id' => ['required', 'exists:carreras,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'clave' => ['nullable', 'string', 'max:50', 'unique:asignaturas,clave'],
            'grado' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        Asignatura::query()->create([...$datos, 'estado' => 'activo']);

        return redirect()->route('modulos.show', 'asignaturas')->with('estado', 'Asignatura guardada correctamente.');
    }

    public function asignarDocente(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('coordinacion'), 403);

        $datos = $request->validate([
            'asignatura_id' => ['required', 'exists:asignaturas,id'],
            'docente_id' => ['required', 'exists:usuarios,id'],
            'periodo_id' => ['required', 'exists:periodos,id'],
        ]);

        $docente = User::query()
            ->whereKey($datos['docente_id'])
            ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['docente_lider', 'docente_materia']))
            ->firstOrFail();

        $asignatura = Asignatura::query()->findOrFail($datos['asignatura_id']);
        abort_unless((int) $docente->carrera_id === (int) $asignatura->carrera_id, 422);

        DB::table('docentes_asignatura')->updateOrInsert(
            [
                'periodo_id' => $datos['periodo_id'],
                'asignatura_id' => $asignatura->id,
                'docente_id' => $docente->id,
            ],
            ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
        );

        return redirect()->route('modulos.show', 'asignaturas')->with('estado', 'Docente vinculado a la asignatura correctamente.');
    }

    public function quitarDocente(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('coordinacion'), 403);

        $datos = $request->validate([
            'asignatura_id' => ['required', 'exists:asignaturas,id'],
            'docente_id' => ['required', 'exists:usuarios,id'],
            'periodo_id' => ['required', 'exists:periodos,id'],
        ]);

        DB::table('docentes_asignatura')
            ->where('periodo_id', $datos['periodo_id'])
            ->where('asignatura_id', $datos['asignatura_id'])
            ->where('docente_id', $datos['docente_id'])
            ->update(['activo' => false, 'actualizado_en' => now()]);

        return redirect()->route('modulos.show', 'asignaturas')->with('estado', 'Docente retirado de la asignatura correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(Request $request): array
    {
        $periodos = Periodo::query()->orderByDesc('fecha_inicio')->get(['id', 'nombre', 'estado']);
        $carreras = Carrera::query()->orderBy('nombre')->get(['id', 'nombre', 'clave']);
        $periodoSeleccionado = $request->query('periodo_asignaturas')
            ?: $periodos->firstWhere('estado', 'activo')?->id
            ?: $periodos->first()?->id;
        $carreraSeleccionada = $request->query('carrera_asignaturas') ?: $carreras->first()?->id;
        $gradoSeleccionado = $request->query('grado_asignaturas') ?: Asignatura::query()
            ->where('carrera_id', $carreraSeleccionada)->orderBy('grado')->value('grado');
        $busqueda = trim((string) $request->query('busqueda_asignaturas', ''));

        $asignaturas = Asignatura::query()
            ->with('carrera:id,nombre,clave')
            ->where('carrera_id', $carreraSeleccionada)
            ->when($gradoSeleccionado, fn ($query) => $query->where('grado', $gradoSeleccionado))
            ->when($busqueda !== '', fn ($query) => $query->where(fn ($scope) => $scope
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('clave', 'like', "%{$busqueda}%")))
            ->orderBy('nombre')
            ->get();
        $docentes = User::query()
            ->with(['carrera:id,nombre,clave', 'role:id,nombre,nombre_visible'])
            ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['docente_lider', 'docente_materia']))
            ->where('carrera_id', $carreraSeleccionada)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'matricula', 'carrera_id', 'rol_id']);
        $asignaciones = DB::table('docentes_asignatura')
            ->join('usuarios', 'usuarios.id', '=', 'docentes_asignatura.docente_id')
            ->where('docentes_asignatura.periodo_id', $periodoSeleccionado)
            ->where('docentes_asignatura.activo', true)
            ->whereIn('docentes_asignatura.asignatura_id', $asignaturas->pluck('id'))
            ->get([
                'docentes_asignatura.asignatura_id',
                'docentes_asignatura.docente_id',
                'usuarios.nombre',
                'usuarios.matricula',
            ])
            ->groupBy('asignatura_id');
        $grados = Asignatura::query()->where('carrera_id', $carreraSeleccionada)->whereNotNull('grado')->distinct()->orderBy('grado')->pluck('grado');

        return [
            'asignaturas' => $asignaturas,
            'carreras' => $carreras,
            'periodos' => $periodos,
            'docentes' => $docentes,
            'asignaciones' => $asignaciones,
            'grados' => $grados,
            'filtrosAsignaturas' => [
                'periodo_id' => $periodoSeleccionado,
                'carrera_id' => $carreraSeleccionada,
                'grado' => $gradoSeleccionado,
                'busqueda' => $busqueda,
            ],
            'metricasAsignaturas' => [
                ['label' => 'Asignaturas', 'value' => (string) $asignaturas->count()],
                ['label' => 'Cuatrimestre', 'value' => (string) ($gradoSeleccionado ?: '-')],
                ['label' => 'Docentes asignados', 'value' => (string) $asignaciones->flatten(1)->count()],
            ],
        ];
    }

}
