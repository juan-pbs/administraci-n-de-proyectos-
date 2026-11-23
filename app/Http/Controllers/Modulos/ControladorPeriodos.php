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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        $inicioMinimo = now()->startOfMonth()->toDateString();

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:periodos,nombre'],
            'fecha_inicio' => ['required', 'date', 'after_or_equal:'.$inicioMinimo, 'before_or_equal:2100-12-31'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio', 'before_or_equal:2100-12-31'],
            'estado' => ['sometimes', 'in:borrador'],
        ]);
        $this->validarNombreFechas($datos['nombre'], $datos['fecha_inicio'], $datos['fecha_fin']);

        Periodo::query()->create([...$datos, 'estado' => 'borrador']);

        return redirect()->route('modulos.show', 'periodos')->with('estado', 'Periodo guardado correctamente.');
    }

    public function actualizar(Request $request, Periodo $periodo): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $reglas = ['nombre' => ['required', 'string', 'max:255', Rule::unique('periodos', 'nombre')->ignore($periodo->id)]];
        if ($periodo->estado === 'borrador') {
            $reglas['fecha_inicio'] = ['required', 'date', 'before_or_equal:fecha_fin'];
            $reglas['fecha_fin'] = ['required', 'date', 'after_or_equal:fecha_inicio', 'before_or_equal:2100-12-31'];
        }
        $datos = $request->validate($reglas);
        $this->validarNombreFechas(
            $datos['nombre'], $datos['fecha_inicio'] ?? $periodo->fecha_inicio, $datos['fecha_fin'] ?? $periodo->fecha_fin,
        );
        $periodo->update($datos);

        return back()->with('estado', 'Datos del periodo actualizados correctamente.');
    }

    private function validarNombreFechas(string $nombre, string $inicio, string $fin): void
    {
        $meses = ['enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
            'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12];
        if (! preg_match('/\A\s*(?<inicio>[\pL]+)(?:\s+(?<anio_inicio>\d{4}))?\s*[-–]\s*(?<fin>[\pL]+)\s+(?<anio>\d{4})\s*\z/u', $nombre, $coincidencia)) {
            return;
        }
        $mesInicio = $meses[mb_strtolower($coincidencia['inicio'])] ?? null;
        $mesFin = $meses[mb_strtolower($coincidencia['fin'])] ?? null;
        if (! $mesInicio || ! $mesFin) return;
        $fechaInicio = Carbon::parse($inicio);
        $fechaFin = Carbon::parse($fin);
        $anioInicio = (int) ($coincidencia['anio_inicio'] ?: $coincidencia['anio']);
        $anioFin = $coincidencia['anio_inicio'] ? (int) $coincidencia['anio'] : null;
        if ($fechaInicio->month !== $mesInicio || $fechaFin->month !== $mesFin
            || $fechaInicio->year !== $anioInicio || ($anioFin !== null && $fechaFin->year !== $anioFin)) {
            throw ValidationException::withMessages(['nombre' => 'Los meses y el año del nombre deben coincidir con las fechas del periodo.']);
        }
    }

    public function pendientes(Request $request, Periodo $periodo): View
    {
        $this->autorizarDireccion($request);
        $usuario = $request->user()->loadMissing('role');

        return view('modulos.control-academico.pendientes-periodo', [
            'active' => 'periodos',
            'navegacion' => SistemaInterfaz::navegacionPara('coordinacion'),
            'roleName' => $usuario->role->nombre_visible,
            'periodo' => $periodo,
            'proyectosSinPdf' => $this->proyectosSinPdf($periodo)
                ->with(['equipo.grupoAcademico.carrera:id,clave', 'guiaIntegradora:id,nombre,version'])
                ->orderBy('titulo')->get(),
            'guiasBorrador' => $this->guiasBorrador($periodo)
                ->with(['asignatura.carrera:id,clave'])
                ->orderBy('nombre')->get(),
        ]);
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
            $periodo->proyectos_sin_pdf = $this->proyectosSinPdf($periodo)->count();
            $periodo->guias_borrador = $this->guiasBorrador($periodo)->count();
        }

        return [
            'periodos' => $periodos,
            'ejemploNombrePeriodo' => Periodo::nombreParaFechas(now()->startOfMonth(), now()->startOfMonth()->addMonths(3)->endOfMonth()),
            'metricasPeriodos' => [
                ['label' => 'Periodos', 'value' => (string) $periodos->count()],
                ['label' => 'Activos', 'value' => (string) $periodos->where('estado', 'activo')->count()],
                ['label' => 'Grupos asociados', 'value' => (string) $periodos->sum('grupos_count')],
            ],
        ];
    }

    private function proyectosSinPdf(Periodo $periodo): Builder
    {
        return Proyecto::query()->whereHas('equipo.grupoAcademico', fn ($q) => $q->where('periodo_id', $periodo->id))
            ->whereNotIn('id', DocumentoFinal::query()->select('proyecto_id'));
    }

    private function guiasBorrador(Periodo $periodo): Builder
    {
        return GuiaIntegradora::query()->where('periodo_id', $periodo->id)->where('estado', 'borrador');
    }
}
