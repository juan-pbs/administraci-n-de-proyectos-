<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ControladorAsignaturas extends Controller
{
    use AutorizaDireccion;

    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';

        return view('modulos.control-academico.asignaturas', [
            'active' => 'asignaturas',
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => SistemaInterfaz::pagina('asignaturas'),
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
            ...$this->datos(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole('direccion_coordinacion', 'encargado_proyectos'), 403);

        $datos = $request->validate([
            'carrera_id' => ['required', 'exists:carreras,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'clave' => ['nullable', 'string', 'max:50', 'unique:asignaturas,clave'],
            'grado' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        Asignatura::query()->create([...$datos, 'estado' => 'activo']);

        return redirect()->route('modulos.show', 'asignaturas')->with('estado', 'Asignatura guardada correctamente.');
    }

    public function asignarDocente(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole('direccion_coordinacion', 'encargado_proyectos'), 403);

        $datos = $request->validate([
            'asignatura_id' => ['required', 'exists:asignaturas,id'],
            'docente_id' => ['required', 'exists:usuarios,id'],
        ]);

        $docente = User::query()
            ->whereKey($datos['docente_id'])
            ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['lider_proyecto', 'docente_materia', 'docente_asesor']))
            ->firstOrFail();

        $asignatura = Asignatura::query()->findOrFail($datos['asignatura_id']);
        $asignatura->docentes()->syncWithoutDetaching([
            $docente->id => ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        return redirect()->route('modulos.show', 'asignaturas')->with('estado', 'Docente vinculado a la asignatura correctamente.');
    }

    public function quitarDocente(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole('direccion_coordinacion', 'encargado_proyectos'), 403);

        $datos = $request->validate([
            'asignatura_id' => ['required', 'exists:asignaturas,id'],
            'docente_id' => ['required', 'exists:usuarios,id'],
        ]);

        Asignatura::query()
            ->findOrFail($datos['asignatura_id'])
            ->docentes()
            ->updateExistingPivot($datos['docente_id'], [
                'activo' => false,
                'actualizado_en' => now(),
            ]);

        return redirect()->route('modulos.show', 'asignaturas')->with('estado', 'Docente retirado de la asignatura correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(): array
    {
        $asignaturas = Asignatura::query()
            ->with(['carrera:id,nombre,clave', 'docentes:id,nombre,matricula,carrera_id'])
            ->orderBy('nombre')
            ->get();
        $docentes = User::query()
            ->with('carrera:id,nombre,clave')
            ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['lider_proyecto', 'docente_materia', 'docente_asesor']))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'matricula', 'carrera_id']);

        return [
            'asignaturas' => $asignaturas,
            'carreras' => Carrera::query()->orderBy('nombre')->get(['id', 'nombre', 'clave']),
            'docentes' => $docentes,
            'metricasAsignaturas' => [
                ['label' => 'Asignaturas', 'value' => (string) $asignaturas->count()],
                ['label' => 'Carreras vinculadas', 'value' => (string) $asignaturas->pluck('carrera_id')->filter()->unique()->count()],
                ['label' => 'Docentes vinculados', 'value' => (string) $asignaturas->sum(fn (Asignatura $asignatura) => $asignatura->docentes->count())],
            ],
        ];
    }
}
