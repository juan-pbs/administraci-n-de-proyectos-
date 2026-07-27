<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\EncargoProyecto;
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

        abort_unless(SistemaInterfaz::puedeVer($rol, 'asignaturas'), 403);

        return view('modulos.control-academico.asignaturas', [
            'active' => 'asignaturas',
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => SistemaInterfaz::pagina('asignaturas'),
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
            ...$this->datos($usuario),
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

        if ($request->user()->hasRole('encargado_proyectos')) {
            abort_unless($datos['grado'] && $this->tieneEncargo($request->user(), (int) $datos['carrera_id'], (int) $datos['grado']), 403);
        }

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
        $this->autorizarAsignatura($request, $asignatura);

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

        $asignatura = Asignatura::query()->findOrFail($datos['asignatura_id']);
        $this->autorizarAsignatura($request, $asignatura);

        $asignatura->docentes()->updateExistingPivot($datos['docente_id'], [
            'activo' => false,
            'actualizado_en' => now(),
        ]);

        return redirect()->route('modulos.show', 'asignaturas')->with('estado', 'Docente retirado de la asignatura correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(User $usuario): array
    {
        $asignaturas = Asignatura::query()
            ->with(['carrera:id,nombre,clave', 'docentes:id,nombre,matricula,carrera_id'])
            ->when($usuario->hasRole('encargado_proyectos'), fn ($query) => $query->whereExists(function ($subquery) use ($usuario) {
                $subquery->selectRaw('1')
                    ->from('encargos_proyecto')
                    ->whereColumn('encargos_proyecto.carrera_id', 'asignaturas.carrera_id')
                    ->whereColumn('encargos_proyecto.cuatrimestre', 'asignaturas.grado')
                    ->where('encargos_proyecto.encargado_id', $usuario->id)
                    ->where('encargos_proyecto.activo', true);
            }))
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

    private function autorizarAsignatura(Request $request, Asignatura $asignatura): void
    {
        if ($request->user()->hasRole('encargado_proyectos')) {
            abort_unless($asignatura->grado !== null && $asignatura->carrera_id !== null && $this->tieneEncargo($request->user(), (int) $asignatura->carrera_id, (int) $asignatura->grado), 403);
        }
    }

    private function tieneEncargo(User $usuario, int $carreraId, int $grado): bool
    {
        return EncargoProyecto::query()
            ->where('encargado_id', $usuario->id)
            ->where('carrera_id', $carreraId)
            ->where('cuatrimestre', $grado)
            ->where('activo', true)
            ->exists();
    }
}
