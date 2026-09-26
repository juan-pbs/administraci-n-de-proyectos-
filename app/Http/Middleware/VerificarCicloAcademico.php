<?php

namespace App\Http\Middleware;

use App\Models\ApartadoGuia;
use App\Models\Entrega;
use App\Models\Equipo;
use App\Models\GrupoAcademico;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Servicios\ContextoEstudiante;
use App\Servicios\PlazosProyecto;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerificarCicloAcademico
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethodSafe() || $request->routeIs('periodos.*', 'guias.publicar', 'guias.preview-apartado', 'documentos.generar', 'docente.firma.*', 'docente-lider.cierres.*', 'docente-lider.estado-guias.preparar-cierre')) {
            return $next($request);
        }

        return DB::transaction(function () use ($request, $next) {
            $id = fn (string $campo) => is_scalar($request->input($campo)) ? filter_var($request->input($campo), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
            $periodos = array_filter([$id('periodo_id'), $id('periodo_fin_id')]);
            $grupo = GrupoAcademico::find($id('grupo_academico_id'));
            $equipo = Equipo::find($id('equipo_id'));
            $proyecto = $request->route('proyecto') ?? Proyecto::find($id('proyecto_id'));
            $entrega = $request->route('entrega');
            if ($entrega instanceof Entrega) {
                $proyecto = $entrega->proyecto;
            }
            if ($request->routeIs('estudiante.*') && $request->user()?->hasRole('estudiante')) {
                [, $equipo, $proyecto] = app(ContextoEstudiante::class)->resolver($request);
            }
            $apartado = $request->route('apartado') ?? ($entrega instanceof Entrega ? $entrega->apartado : ApartadoGuia::find($id('apartado_guia_id')));
            $guia = $request->route('guia') ?? GuiaIntegradora::find($id('guia_integradora_id'));
            if ($apartado instanceof ApartadoGuia) {
                $guia = $apartado->guiaIntegradora;
            }
            if ($proyecto instanceof Proyecto) {
                $guia = $proyecto->guiaIntegradora;
                $equipo = $proyecto->equipo;
            }
            if ($equipo instanceof Equipo) {
                $grupo = $equipo->grupoAcademico;
            }
            if ($grupo instanceof GrupoAcademico) {
                $periodos[] = $grupo->periodo_id;
            }
            if ($guia instanceof GuiaIntegradora) {
                $periodos[] = $guia->periodo_id;
                $periodos[] = $guia->periodo_fin_id ?? $guia->periodo_id;
            }
            if ($request->routeIs('usuarios.alumnos.confirmar') && is_string($request->input('preview'))) {
                $periodos[] = $request->session()->get('preview_alumnos.'.$request->input('preview').'.periodo_id');
            }
            $estados = Periodo::query()->whereIn('id', array_unique(array_filter($periodos)))->orderBy('id')->lockForUpdate()->get();
            $operacion = $request->routeIs('estudiante.entregas.guardar', 'estudiante.codigo.guardar', 'docente-materia.revisiones.guardar', 'docente-materia.comentarios.guardar', 'docente-lider.codigo.revisar', 'docente-lider.codigo.comentar');
            if ($request->routeIs('estudiante.codigo.guardar') && $proyecto instanceof Proyecto) {
                $apartado = $proyecto->guiaIntegradora->apartados()->where('requiere_codigo', true)->orderBy('orden')->first();
            }
            if ($operacion && $proyecto instanceof Proyecto && $apartado instanceof ApartadoGuia) {
                Proyecto::whereKey($proyecto->id)->lockForUpdate()->firstOrFail();
                $plazos = app(PlazosProyecto::class);
                if ($plazos->cierre($proyecto)) {
                    $this->exigir($plazos->permite($proyecto, $apartado), 'Este apartado está cerrado o su prórroga terminó. El docente líder debe autorizar su apertura.');

                    return $next($request);
                }
            }
            if ($guia instanceof GuiaIntegradora) {
                $guia->refresh();
                $this->exigir($guia->estado !== 'cerrada', 'La guía está cerrada y solo admite consulta.');
                if ($request->routeIs('proyectos.guardar', 'estudiante.*', 'docente-materia.revisiones.guardar', 'docente-materia.comentarios.guardar', 'docente-lider.codigo.revisar', 'docente-lider.codigo.comentar')) {
                    $this->exigir($guia->estado === 'publicada', 'Publica la guía antes de asignarla, enviar entregas o realizar evaluaciones.');
                }
            }
            $this->exigir(! $estados->contains('estado', 'cerrado'), 'El periodo está cerrado y solo admite consulta.');

            return $next($request);
        });
    }

    private function exigir(bool $condicion, string $mensaje): void
    {
        if (! $condicion) {
            throw ValidationException::withMessages(['ciclo' => $mensaje]);
        }
    }
}
