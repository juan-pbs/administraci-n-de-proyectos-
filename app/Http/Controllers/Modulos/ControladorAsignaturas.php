<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Periodo;
use App\Models\User;
use App\Soporte\ProtegeHistorial;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $asignatura = Asignatura::query()->create([...$this->validarDatos($request), 'estado' => 'activo']);

        return redirect()->route('modulos.show', [
            'modulo' => 'asignaturas', 'periodo_asignaturas' => $request->input('periodo_asignaturas'),
            'carrera_asignaturas' => $asignatura->carrera_id, 'grado_asignaturas' => $asignatura->grado,
            'asignar_docente' => 1, 'asignatura_id' => $asignatura->id,
        ])->with('estado', 'Asignatura guardada. Selecciona un docente en el formulario abierto para asignarlo al periodo.');
    }

    public function actualizar(Request $request, Asignatura $asignatura): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $asignatura = Asignatura::whereKey($asignatura->id)->lockForUpdate()->firstOrFail();
        $asignatura->fill($this->validarDatos($request, $asignatura));
        if ($asignatura->isDirty(['grado', 'carrera_id'])) {
            $this->exigirSinVinculos($asignatura, 'Para cambiar la carrera o el cuatrimestre usa una asignatura sin vínculos.');
        }
        $asignatura->save();

        return redirect()->route('modulos.asignaturas', [
            'carrera_asignaturas' => $asignatura->carrera_id, 'grado_asignaturas' => $asignatura->grado,
            'periodo_asignaturas' => $request->input('periodo_asignaturas'),
        ])->with('estado', 'Asignatura actualizada.');
    }

    public function eliminar(Request $request, Asignatura $asignatura): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $request->validate(['confirmar_eliminacion' => ['accepted']]);
        $asignatura = Asignatura::whereKey($asignatura->id)->lockForUpdate()->firstOrFail();
        $this->exigirSinVinculos($asignatura, 'La asignatura tiene vínculos académicos y no se puede eliminar.');
        $asignatura->delete();

        return back()->with('estado', 'Asignatura eliminada.');
    }

    private function validarDatos(Request $request, ?Asignatura $asignatura = null): array
    {
        $carrera = Rule::exists('carreras', 'id')->where(fn ($q) => $q->where('estado', 'activa')
            ->when($asignatura, fn ($q) => $q->orWhere('id', $asignatura->carrera_id)));

        return $request->validate([
            'carrera_id' => ['required', $carrera],
            'nombre' => ['required', 'string', 'max:120', 'regex:/\A[\pL\pM\pN .,:;()\/+&-]+\z/u'],
            'clave' => ['nullable', 'string', 'max:30', 'regex:/\A[A-Za-z0-9._-]+\z/', Rule::unique('asignaturas', 'clave')->ignore($asignatura?->id)],
            'grado' => ['required', 'integer', 'min:1', 'max:12'],
            'estado' => ['sometimes', 'in:activo,inactivo'],
        ], [
            'nombre.regex' => 'El nombre admite letras, números, espacios y estos signos: . , : ; ( ) / + & -',
            'clave.regex' => 'La clave solo admite letras, números, puntos, guiones y guiones bajos.',
        ]);
    }

    private function exigirSinVinculos(Asignatura $asignatura, string $mensaje): void
    {
        ProtegeHistorial::sinReferencias($asignatura->id, [
            'docentes_asignatura' => 'asignatura_id', 'guias_integradoras' => 'asignatura_id',
            'grupos_academicos' => 'asignatura_lider_id', 'asignaturas_apartado_guia' => 'asignatura_id',
            'firmas_apartado_guia' => 'asignatura_id', 'asignaturas_proyecto' => 'asignatura_id',
        ], $mensaje);
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
        abort_unless($asignatura->estado === 'activo' && $asignatura->carrera?->estado === 'activa', 422, 'La asignatura o su carrera está inactiva.');
        abort_unless((int) $docente->carrera_id === (int) $asignatura->carrera_id, 422);

        DB::table('docentes_asignatura')->updateOrInsert(
            [
                'periodo_id' => $datos['periodo_id'],
                'asignatura_id' => $asignatura->id,
                'docente_id' => $docente->id,
            ],
            ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
        );

        return redirect()->route('modulos.show', [
            'modulo' => 'asignaturas', 'periodo_asignaturas' => $datos['periodo_id'],
            'carrera_asignaturas' => $asignatura->carrera_id, 'grado_asignaturas' => $asignatura->grado,
        ])->with('estado', 'Docente vinculado a la asignatura correctamente.');
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
