<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\EncargoProyecto;
use App\Models\GrupoAcademico;
use App\Models\Periodo;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ControladorJerarquiaProyectos extends Controller
{
    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        abort_unless($usuario->hasAnyRole('direccion_coordinacion', 'encargado_proyectos'), 403);

        $encargos = EncargoProyecto::query()
            ->with(['encargado:id,nombre,matricula', 'periodo:id,nombre', 'carrera:id,nombre,clave'])
            ->when($usuario->hasRole('encargado_proyectos'), fn ($query) => $query->where('encargado_id', $usuario->id))
            ->where('activo', true)->orderByDesc('periodo_id')->get();

        $grupos = GrupoAcademico::query()
            ->with(['periodo:id,nombre', 'carrera:id,clave,nombre', 'liderProyecto:id,nombre,matricula', 'asignaturaLider:id,nombre,clave'])
            ->when($usuario->hasRole('encargado_proyectos'), function ($query) use ($usuario) {
                $query->whereExists(function ($subquery) use ($usuario) {
                    $subquery->selectRaw('1')->from('encargos_proyecto')
                        ->whereColumn('encargos_proyecto.periodo_id', 'grupos_academicos.periodo_id')
                        ->whereColumn('encargos_proyecto.carrera_id', 'grupos_academicos.carrera_id')
                        ->whereColumn('encargos_proyecto.cuatrimestre', 'grupos_academicos.grado')
                        ->where('encargos_proyecto.encargado_id', $usuario->id)->where('encargos_proyecto.activo', true);
                });
            })->orderBy('grado')->orderBy('grupo')->get();

        return view('modulos.gestion-proyectos.jerarquia', [
            'active' => 'jerarquia-proyectos',
            'navegacion' => SistemaInterfaz::navegacionPara($usuario->role->nombre),
            'roleName' => $usuario->role->nombre_visible,
            'puedeCrearEncargos' => $usuario->hasRole('direccion_coordinacion'),
            'encargos' => $encargos,
            'grupos' => $grupos,
            'periodos' => Periodo::query()->orderByDesc('fecha_inicio')->get(),
            'carreras' => Carrera::query()->where('estado', 'activa')->orderBy('nombre')->get(),
            'encargados' => User::query()->whereHas('role', fn ($q) => $q->where('nombre', 'encargado_proyectos'))->orderBy('nombre')->get(),
            'lideres' => User::query()->whereHas('role', fn ($q) => $q->where('nombre', 'lider_proyecto'))->orderBy('nombre')->get(),
            'asignaturas' => Asignatura::query()->where('estado', 'activo')->orderBy('nombre')->get(),
        ]);
    }

    public function asignarEncargado(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('direccion_coordinacion'), 403);
        $datos = $request->validate([
            'encargado_id' => ['required', Rule::exists('usuarios', 'id')->where(fn ($q) => $q->whereIn('rol_id', function ($r) { $r->select('id')->from('roles')->where('nombre', 'encargado_proyectos'); }))],
            'periodo_id' => ['required', 'exists:periodos,id'], 'carrera_id' => ['required', 'exists:carreras,id'],
            'cuatrimestre' => ['required', 'integer', 'between:1,12'],
        ]);
        EncargoProyecto::query()->updateOrCreate($datos, ['activo' => true]);
        return back()->with('estado', 'Encargado de proyectos asignado al cuatrimestre correctamente.');
    }

    public function asignarLider(Request $request): RedirectResponse
    {
        $usuario = $request->user()->loadMissing('role');
        abort_unless($usuario->hasAnyRole('direccion_coordinacion', 'encargado_proyectos'), 403);
        $datos = $request->validate([
            'grupo_academico_id' => ['required', 'exists:grupos_academicos,id'],
            'lider_proyecto_id' => ['required', 'exists:usuarios,id'],
            'asignatura_lider_id' => ['required', 'exists:asignaturas,id'],
        ]);
        $grupo = GrupoAcademico::query()->findOrFail($datos['grupo_academico_id']);
        if ($usuario->hasRole('encargado_proyectos')) {
            abort_unless(EncargoProyecto::query()->where('encargado_id', $usuario->id)->where('periodo_id', $grupo->periodo_id)
                ->where('carrera_id', $grupo->carrera_id)->where('cuatrimestre', $grupo->grado)->where('activo', true)->exists(), 403);
        }
        $lider = User::query()->with('role')->findOrFail($datos['lider_proyecto_id']);
        abort_unless($lider->hasRole('lider_proyecto'), 422);
        $asignatura = Asignatura::query()->findOrFail($datos['asignatura_lider_id']);
        abort_unless((int) $asignatura->carrera_id === (int) $grupo->carrera_id && (int) $asignatura->grado === (int) $grupo->grado, 422);
        $grupo->update(['lider_proyecto_id' => $lider->id, 'asignatura_lider_id' => $asignatura->id]);
        return back()->with('estado', 'Líder de proyecto y materia líder asignados al grupo.');
    }
}
