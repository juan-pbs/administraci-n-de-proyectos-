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
use Illuminate\Support\Facades\DB;

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
            ->with(['periodo:id,nombre', 'carrera:id,clave,nombre', 'liderProyecto:id,nombre,matricula', 'asignaturaLider:id,nombre,clave'])
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
                ->where('estado', 'activo')
                ->when($carreraId, fn ($query) => $query->where(function ($docentes) use ($carreraId) {
                    $docentes->where('carrera_id', $carreraId)
                        ->orWhereHas('carrerasComoDocente', fn ($carreras) => $carreras
                            ->where('carreras.id', $carreraId)
                            ->where('docentes_carrera.activo', true));
                }))
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
            'asignatura_lider_id' => ['required', 'exists:asignaturas,id'],
        ]);
        $grupo = GrupoAcademico::query()->findOrFail($datos['grupo_academico_id']);
        $lider = User::query()->with('role')->findOrFail($datos['lider_proyecto_id']);
        abort_unless($lider->hasRole('docente_lider'), 422);
        $asignatura = Asignatura::query()->findOrFail($datos['asignatura_lider_id']);
        abort_unless((int) $asignatura->carrera_id === (int) $grupo->carrera_id && (int) $asignatura->grado === (int) $grupo->grado, 422);
        abort_unless($lider->estado === 'activo' && ($lider->carrera_id === $grupo->carrera_id || $lider->carrerasComoDocente()->whereKey($grupo->carrera_id)->exists()), 422);
        DB::transaction(function () use ($grupo, $lider, $asignatura): void {
            $clave = ['periodo_id' => $grupo->periodo_id, 'asignatura_id' => $asignatura->id, 'docente_id' => $lider->id];
            DB::table('docentes_asignatura')->insertOrIgnore([...$clave, 'activo' => true, 'creado_en' => now(), 'actualizado_en' => now()]);
            DB::table('docentes_asignatura')->where($clave)->update(['activo' => true, 'actualizado_en' => now()]);
            $grupo->update(['lider_proyecto_id' => $lider->id, 'asignatura_lider_id' => $asignatura->id]);
        });
        return back()->with('estado', 'Líder de proyecto y materia líder asignados al grupo.');
    }
}
