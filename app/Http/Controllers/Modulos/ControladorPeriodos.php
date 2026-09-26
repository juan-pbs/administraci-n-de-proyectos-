<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\DocumentoFinal;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Servicios\CicloAcademico;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ControladorPeriodos extends Controller
{
    use AutorizaDireccion;

    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';

        abort_unless(SistemaInterfaz::puedeVer($rol, 'periodos'), 403);

        return view('modulos.control-academico.periodos', [
            'active' => 'periodos',
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => SistemaInterfaz::pagina('periodos'),
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
            ...$this->datos(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:periodos,nombre'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['sometimes', 'in:borrador'],
        ]);

        Periodo::query()->create([...$datos, 'estado' => 'borrador']);

        return redirect()->route('modulos.show', 'periodos')->with('estado', 'Periodo guardado correctamente.');
    }

    public function activar(Request $request, Periodo $periodo, CicloAcademico $ciclo): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $ciclo->activar($periodo);

        return back()->with('estado', 'Periodo activado correctamente.');
    }

    public function cerrar(Request $request, Periodo $periodo, CicloAcademico $ciclo): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $request->validate(['confirmar_cierre' => ['accepted']]);
        $ciclo->cerrar($periodo);

        return back()->with('estado', 'Periodo cerrado. Sus guías publicadas que terminan en este ciclo quedaron cerradas para consulta.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(): array
    {
        $periodos = Periodo::query()
            ->withCount(['gruposAcademicos as grupos_count'])
            ->orderByDesc('fecha_inicio')
            ->get();

        foreach ($periodos->where('estado', 'activo') as $periodo) {
            $periodo->proyectos_sin_pdf = Proyecto::query()->whereHas('equipo.grupoAcademico', fn ($q) => $q->where('periodo_id', $periodo->id))
                ->whereNotIn('id', DocumentoFinal::query()->select('proyecto_id'))->count();
            $periodo->guias_borrador = GuiaIntegradora::query()->where('periodo_id', $periodo->id)->where('estado', 'borrador')->count();
        }

        return [
            'periodos' => $periodos,
            'metricasPeriodos' => [
                ['label' => 'Periodos', 'value' => (string) $periodos->count()],
                ['label' => 'Activos', 'value' => (string) $periodos->where('estado', 'activo')->count()],
                ['label' => 'Grupos asociados', 'value' => (string) $periodos->sum('grupos_count')],
            ],
        ];
    }
}
