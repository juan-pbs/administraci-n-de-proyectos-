<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\Periodo;
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
            'nombre' => ['required', 'string', 'max:255'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['required', 'string', 'max:30'],
        ]);

        Periodo::query()->updateOrCreate(['nombre' => $datos['nombre']], $datos);

        return redirect()->route('modulos.show', 'periodos')->with('estado', 'Periodo guardado correctamente.');
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
