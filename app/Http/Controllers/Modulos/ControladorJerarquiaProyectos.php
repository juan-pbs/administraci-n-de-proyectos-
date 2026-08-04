<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\GrupoAcademico;
use App\Models\Periodo;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ControladorJerarquiaProyectos extends Controller
{
    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        abort_unless($usuario->hasRole('coordinacion'), 403);

        $periodos = Periodo::query()->orderByDesc('fecha_inicio')->get();
        $carreras = Carrera::query()->where('estado', 'activa')->orderBy('nombre')->get();
        $periodoId = (int) $request->integer('periodo_id', (int) optional($periodos->firstWhere('estado', 'activo') ?? $periodos->first())->id);
        $carreraId = (int) $request->integer('carrera_id', (int) optional($carreras->first())->id);

        $grupos = GrupoAcademico::query()
            ->with(['periodo:id,nombre', 'carrera:id,clave,nombre', 'liderProyecto:id,nombre,matricula', 'docenteMateriaLider:id,nombre,matricula', 'asignaturaLider:id,nombre,clave'])
            ->when($periodoId, fn ($query) => $query->where('periodo_id', $periodoId))
            ->when($carreraId, fn ($query) => $query->where('carrera_id', $carreraId))
            ->orderBy('grado')->orderBy('grupo')->get();

        return view('modulos.gestion-proyectos.jerarquia', [
            'active' => 'jerarquia-proyectos',
            'navegacion' => SistemaInterfaz::navegacionPara($usuario->role->nombre),
            'roleName' => $usuario->role->nombre_visible,
            'grupos' => $grupos,
            'periodos' => $periodos,
            'carreras' => $carreras,
            'periodoId' => $periodoId,
            'carreraId' => $carreraId,
            'lideres' => User::query()
                ->whereHas('role', fn ($q) => $q->where('nombre', 'docente_lider'))
                ->when($carreraId, fn ($query) => $query->where(function ($docentes) use ($carreraId) {
                    $docentes->where('carrera_id', $carreraId)
                        ->orWhereHas('carrerasComoDocente', fn ($carreras) => $carreras
                            ->where('carreras.id', $carreraId)
                            ->where('docentes_carrera.activo', true));
                }))
                ->orderBy('nombre')->get(),
            'docentesMateria' => User::query()
                ->whereHas('role', fn ($q) => $q->where('nombre', 'docente_materia'))
                ->whereHas('asignaturasComoDocente', fn ($asignaturas) => $asignaturas
                    ->where('docentes_asignatura.periodo_id', $periodoId)
                    ->where('docentes_asignatura.activo', true)
                    ->when($carreraId, fn ($query) => $query->where('asignaturas.carrera_id', $carreraId)))
                ->orderBy('nombre')->get(),
            'asignaturas' => Asignatura::query()
                ->where('estado', 'activo')
                ->when($carreraId, fn ($query) => $query->where('carrera_id', $carreraId))
                ->orderBy('grado')->orderBy('nombre')->get(),
        ]);
    }

    public function asignarLider(Request $request): RedirectResponse
    {
        $usuario = $request->user()->loadMissing('role');
        abort_unless($usuario->hasRole('coordinacion'), 403);
        $datos = $request->validate([
            'grupo_academico_id' => ['required', 'exists:grupos_academicos,id'],
            'lider_proyecto_id' => ['required', 'exists:usuarios,id'],
            'docente_materia_lider_id' => ['required', 'exists:usuarios,id'],
            'asignatura_lider_id' => ['required', 'exists:asignaturas,id'],
        ]);
        $grupo = GrupoAcademico::query()->findOrFail($datos['grupo_academico_id']);
        $lider = User::query()->with('role')->findOrFail($datos['lider_proyecto_id']);
        abort_unless($lider->hasRole('docente_lider'), 422);
        $docenteMateria = User::query()->with('role')->findOrFail($datos['docente_materia_lider_id']);
        abort_unless($docenteMateria->hasRole('docente_materia'), 422);
        $asignatura = Asignatura::query()->findOrFail($datos['asignatura_lider_id']);
        abort_unless((int) $asignatura->carrera_id === (int) $grupo->carrera_id && (int) $asignatura->grado === (int) $grupo->grado, 422);
        abort_unless(
            $docenteMateria->asignaturasComoDocente()
                ->where('asignaturas.id', $asignatura->id)
                ->where('docentes_asignatura.periodo_id', $grupo->periodo_id)
                ->where('docentes_asignatura.activo', true)
                ->exists(),
            422,
            'El docente seleccionado no imparte esta materia en el periodo del grupo.'
        );
        $grupo->update([
            'lider_proyecto_id' => $lider->id,
            'docente_materia_lider_id' => $docenteMateria->id,
            'asignatura_lider_id' => $asignatura->id,
        ]);
        return back()->with('estado', 'Líder de proyecto y materia líder asignados al grupo.');
    }
}
