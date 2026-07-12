<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\Equipo;
use App\Models\GrupoAcademico;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ControladorEquipos extends Controller
{
    use AutorizaDireccion;

    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';

        return view('modulos.gestion-proyectos.equipos', [
            'active' => 'equipos',
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => SistemaInterfaz::pagina('equipos'),
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
            ...$this->datos(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'grupo_academico_id' => ['required', 'exists:grupos_academicos,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'lider_id' => ['nullable', 'exists:usuarios,id'],
        ]);

        Equipo::query()->updateOrCreate(
            ['grupo_academico_id' => $datos['grupo_academico_id'], 'nombre' => $datos['nombre']],
            ['lider_id' => $datos['lider_id'] ?? null, 'estado' => 'activo'],
        );

        return redirect()->route('modulos.show', 'equipos')->with('estado', 'Equipo guardado correctamente.');
    }

    public function asignarAlumno(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'equipo_id' => ['required', 'exists:equipos,id'],
            'estudiante_id' => ['required', 'exists:usuarios,id'],
        ]);

        $equipo = Equipo::query()->with('grupoAcademico')->findOrFail($datos['equipo_id']);
        $estudiante = User::query()->findOrFail($datos['estudiante_id']);

        $estudiante->forceFill([
            'carrera_id' => $equipo->grupoAcademico->carrera_id,
            'grupo_academico_id' => $equipo->grupo_academico_id,
        ])->save();

        $equipo->integrantes()->syncWithoutDetaching([
            $estudiante->id => ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        return redirect()->route('modulos.show', 'equipos')->with('estado', 'Alumno asignado al equipo correctamente.');
    }

    public function asignarAsesor(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'equipo_id' => ['required', 'exists:equipos,id'],
            'asesor_id' => ['required', 'exists:usuarios,id'],
            'principal' => ['nullable', 'boolean'],
        ]);

        $equipo = Equipo::query()->findOrFail($datos['equipo_id']);

        if ($request->boolean('principal')) {
            $equipo->asesores()
                ->pluck('usuarios.id')
                ->each(fn (int $asesorId) => $equipo->asesores()->updateExistingPivot($asesorId, [
                    'principal' => false,
                    'actualizado_en' => now(),
                ]));
        }

        $equipo->asesores()->syncWithoutDetaching([
            $datos['asesor_id'] => [
                'principal' => $request->boolean('principal'),
                'activo' => true,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ]);

        return redirect()->route('modulos.show', 'equipos')->with('estado', 'Asesor asignado al equipo correctamente.');
    }

    public function quitarAlumno(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'equipo_id' => ['required', 'exists:equipos,id'],
            'estudiante_id' => ['required', 'exists:usuarios,id'],
        ]);

        $equipo = Equipo::query()->findOrFail($datos['equipo_id']);
        $equipo->integrantes()->updateExistingPivot($datos['estudiante_id'], [
            'activo' => false,
            'actualizado_en' => now(),
        ]);

        if ((int) $equipo->lider_id === (int) $datos['estudiante_id']) {
            $equipo->forceFill(['lider_id' => null])->save();
        }

        return redirect()->route('modulos.show', 'equipos')->with('estado', 'Alumno retirado del equipo correctamente.');
    }

    public function quitarAsesor(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'equipo_id' => ['required', 'exists:equipos,id'],
            'asesor_id' => ['required', 'exists:usuarios,id'],
        ]);

        Equipo::query()->findOrFail($datos['equipo_id'])
            ->asesores()
            ->updateExistingPivot($datos['asesor_id'], [
                'activo' => false,
                'principal' => false,
                'actualizado_en' => now(),
            ]);

        return redirect()->route('modulos.show', 'equipos')->with('estado', 'Asesor retirado del equipo correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(): array
    {
        $equipos = Equipo::query()
            ->with(['grupoAcademico.carrera:id,nombre,clave', 'lider:id,nombre,matricula', 'integrantes:id,nombre,matricula', 'asesores:id,nombre,matricula'])
            ->withCount(['integrantes as integrantes_count', 'asesores as asesores_count'])
            ->orderBy('nombre')
            ->get();

        return [
            'equipos' => $equipos,
            'grupos' => GrupoAcademico::query()->with('carrera:id,clave')->orderBy('grado')->orderBy('grupo')->get(),
            'estudiantes' => User::query()->whereHas('role', fn ($query) => $query->where('nombre', 'estudiante'))->orderBy('nombre')->get(['id', 'nombre', 'matricula']),
            'docentes' => User::query()->whereHas('role', fn ($query) => $query->where('nombre', 'docente_asesor'))->orderBy('nombre')->get(['id', 'nombre', 'matricula']),
            'metricasEquipos' => [
                ['label' => 'Equipos', 'value' => (string) $equipos->count()],
                ['label' => 'Integrantes', 'value' => (string) $equipos->sum('integrantes_count')],
                ['label' => 'Asesores asignados', 'value' => (string) $equipos->sum('asesores_count')],
            ],
        ];
    }
}
