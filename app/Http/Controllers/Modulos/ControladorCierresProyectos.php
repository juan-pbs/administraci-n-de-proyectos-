<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\CierreProyecto;
use App\Models\Proyecto;
use App\Servicios\CierresProyectos;
use App\Soporte\SistemaInterfaz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ControladorCierresProyectos extends Controller
{
    public function indice(Request $request)
    {
        $usuario = $request->user()->loadMissing('role');
        abort_unless($usuario->hasRole('docente_lider') && $usuario->estado === 'activo', 403);
        $cierres = CierreProyecto::with('proyecto.equipo.grupoAcademico', 'prorrogas')
            ->whereHas('proyecto.equipo.grupoAcademico', fn ($q) => $q->conMateriaLiderDelDocente($usuario->id))
            ->orderByRaw("CASE WHEN estado = 'pendiente' THEN 0 WHEN estado = 'prorroga' THEN 1 ELSE 2 END")
            ->latest('id')->paginate(20);

        return response()->view('modulos.docente-lider.cierres', [...$this->base($usuario), 'cierres' => $cierres])->header('Cache-Control', 'no-store, private');
    }

    public function mostrar(Request $request, Proyecto $proyecto, CierresProyectos $servicio)
    {
        $usuario = $request->user()->loadMissing('role');
        $servicio->autorizar($usuario, $proyecto);
        $cierre = $proyecto->cierre()->with('prorrogas')->firstOrFail();

        return response()->view('modulos.docente-lider.cierre', [...$this->base($usuario),
            'proyecto' => $proyecto->load('guiaIntegradora.apartados', 'equipo.grupoAcademico'), 'cierre' => $cierre,
            'pendientes' => in_array($cierre->estado, ['cerrado', 'completo'], true) ? $cierre->pendientes : $servicio->resumen($proyecto),
            'decisiones' => DB::table('decisiones_cierre')->where('cierre_proyecto_id', $cierre->id)->latest('id')->get(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function decidir(Request $request, Proyecto $proyecto, CierresProyectos $servicio)
    {
        $servicio->autorizar($request->user(), $proyecto);
        $datos = $request->validate([
            'decision' => ['required', Rule::in(['cerrar', 'prorroga'])],
            'ronda' => ['required', 'integer', 'min:1'],
            'motivo' => ['required', 'string', 'max:2000'],
            'apartados' => ['exclude_unless:decision,prorroga', 'required', 'array', 'min:1', 'max:100'],
            'apartados.*' => ['required', 'integer', 'distinct'],
            'fecha_limite' => ['exclude_unless:decision,prorroga', 'required', 'date', 'after:now'],
            'confirmar_cierre' => ['exclude_unless:decision,cerrar', 'accepted'],
        ]);
        $servicio->decidir($request->user(), $proyecto, $datos);

        return redirect()->route('docente-lider.cierres.mostrar', $proyecto)->with('status', $datos['decision'] === 'prorroga' ? 'Prórroga registrada para los apartados seleccionados.' : 'Cierre registrado con constancia de los pendientes.');
    }

    private function base($usuario): array
    {
        return ['navegacion' => SistemaInterfaz::navegacionPara('docente_lider'), 'roleName' => $usuario->role->nombre_visible];
    }
}
