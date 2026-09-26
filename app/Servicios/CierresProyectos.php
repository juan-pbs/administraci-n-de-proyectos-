<?php

namespace App\Servicios;

use App\Jobs\EnviarAvisoCierre;
use App\Models\AvisoCierre;
use App\Models\CierreProyecto;
use App\Models\DocumentoFinal;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CierresProyectos
{
    public function resumen(Proyecto $proyecto): array
    {
        $datos = app(DocumentosGuias::class)->datos($proyecto, false);
        $pendientes = [];
        foreach ($datos['guia']->apartados->sortBy('orden') as $apartado) {
            $fila = $datos['filas'][$apartado->id];
            if (! $fila['entrega']) {
                $pendientes[] = ['apartado_id' => $apartado->id, 'apartado' => $apartado->titulo, 'tipo' => 'entrega', 'detalle' => 'Falta la entrega del equipo de alumnos.'];

                continue;
            }
            $evaluadores = collect($fila['firmantes'])->where('requerida', true)->pluck('docente.id')->filter();
            $correcciones = $fila['entrega']->revisiones->whereIn('revisor_id', $evaluadores)->whereIn('resultado', ['correccion', 'rechazada']);
            if ($correcciones->isNotEmpty()) {
                $pendientes[] = ['apartado_id' => $apartado->id, 'apartado' => $apartado->titulo, 'tipo' => 'correccion', 'detalle' => 'El equipo debe atender las correcciones o el rechazo de la última entrega.'];
            }
            foreach ($fila['firmantes'] as $firmante) {
                if ($firmante['requerida'] && ! $firmante['revision']) {
                    $pendientes[] = ['apartado_id' => $apartado->id, 'apartado' => $apartado->titulo, 'tipo' => 'firma', 'detalle' => 'Falta la aprobación firmada vigente de '.($firmante['docente']?->nombre ?? $firmante['etiqueta']).'.'];
                }
            }
        }
        if (! $pendientes && $datos['pendientes']) {
            $pendientes[] = ['apartado_id' => null, 'apartado' => 'Configuración de la guía', 'tipo' => 'configuracion', 'detalle' => implode(' ', $datos['pendientes'])];
        }
        if (! $pendientes && ! DocumentoFinal::where('proyecto_id', $proyecto->id)->exists()) {
            $pendientes[] = ['apartado_id' => null, 'apartado' => 'Documento final', 'tipo' => 'pdf', 'detalle' => 'Las entregas y firmas están completas; falta emitir y guardar el PDF final.'];
        }

        return $pendientes;
    }

    public function registrar(Proyecto $proyecto): ?CierreProyecto
    {
        return DB::transaction(function () use ($proyecto) {
            $this->bloquearPeriodos($proyecto);
            $proyecto = Proyecto::whereKey($proyecto->id)->lockForUpdate()->firstOrFail();
            $proyecto->load('guiaIntegradora.periodo', 'guiaIntegradora.periodoFin');
            $periodo = $proyecto->guiaIntegradora->periodoFin ?? $proyecto->guiaIntegradora->periodo;
            $cierre = CierreProyecto::where('proyecto_id', $proyecto->id)->lockForUpdate()->first();
            if ($cierre && in_array($cierre->estado, ['cerrado', 'completo'], true)) {
                return $cierre;
            }
            if ($cierre?->estado === 'prorroga' && $cierre->prorrogas()->where('ronda', $cierre->ronda)->where('fecha_limite', '>', now())->exists()) {
                return $cierre;
            }
            if (! $cierre && ($proyecto->guiaIntegradora->estado === 'borrador' || ($periodo->estado !== 'cerrado' && ! Carbon::parse($periodo->fecha_fin)->endOfDay()->isPast()))) {
                return null;
            }
            $pendientes = $this->resumen($proyecto);
            if (! $pendientes) {
                $cierre?->update(['estado' => 'completo', 'pendientes' => []]);

                return $cierre;
            }
            if (! $cierre) {
                $cierre = CierreProyecto::create(['proyecto_id' => $proyecto->id, 'pendientes' => $pendientes, 'detectado_en' => now()]);
            } else {
                $cierre->update(['estado' => 'pendiente', 'pendientes' => $pendientes, 'detectado_en' => now()]);
            }
            $lider = $proyecto->equipo->grupoAcademico->liderProyecto;
            if ($lider?->estado === 'activo' && $lider->hasRole('docente_lider')) {
                AvisoCierre::firstOrCreate(['clave' => hash('sha256', $cierre->id.'|'.$cierre->ronda.'|'.$lider->id)], [
                    'cierre_proyecto_id' => $cierre->id, 'docente_id' => $lider->id, 'ronda' => $cierre->ronda,
                ]);
            }

            return $cierre;
        });
    }

    public function autorizar(User $docente, Proyecto $proyecto): void
    {
        abort_unless($docente->estado === 'activo' && $docente->hasRole('docente_lider') && $proyecto->equipo()->whereHas('grupoAcademico', fn ($q) => $q->conMateriaLiderDelDocente($docente->id))->exists(), 403);
    }

    public function decidir(User $docente, Proyecto $proyecto, array $datos): void
    {
        $this->autorizar($docente, $proyecto);
        DB::transaction(function () use ($docente, $proyecto, $datos) {
            $this->bloquearPeriodos($proyecto);
            $proyecto = Proyecto::whereKey($proyecto->id)->lockForUpdate()->firstOrFail();
            $this->autorizar($docente, $proyecto);
            $cierre = CierreProyecto::where('proyecto_id', $proyecto->id)->lockForUpdate()->first();
            $this->exigir($cierre && in_array($cierre->estado, ['pendiente', 'prorroga'], true), 'Este proyecto no tiene un cierre pendiente que puedas resolver.');
            $this->exigir((int) $datos['ronda'] === (int) $cierre->ronda, 'La situación cambió. Actualiza la página antes de decidir.');
            $pendientes = $this->resumen($proyecto);
            $apartados = array_map('intval', $datos['apartados'] ?? []);
            $fecha = isset($datos['fecha_limite']) ? Carbon::parse($datos['fecha_limite']) : null;
            if ($datos['decision'] === 'prorroga') {
                $validos = $proyecto->guiaIntegradora->apartados()->pluck('id')->all();
                $this->exigir($apartados && ! array_diff($apartados, $validos), 'Selecciona únicamente apartados de la guía de este proyecto.');
                $this->exigir($fecha && $fecha->isFuture(), 'La prórroga debe terminar en una fecha futura.');
                $ronda = $cierre->ronda + 1;
                foreach ($apartados as $apartado) {
                    $cierre->prorrogas()->create(['apartado_guia_id' => $apartado, 'ronda' => $ronda, 'docente_id' => $docente->id, 'fecha_limite' => $fecha]);
                }
                $cierre->update(['estado' => 'prorroga', 'ronda' => $ronda, 'pendientes' => $pendientes, 'decidido_por' => $docente->id, 'decidido_en' => now(), 'motivo' => $datos['motivo']]);
            } else {
                $cierre->update(['estado' => 'cerrado', 'pendientes' => $pendientes, 'decidido_por' => $docente->id, 'decidido_en' => now(), 'motivo' => $datos['motivo']]);
                $proyecto->update(['estado' => $pendientes ? 'cerrado_con_pendientes' : 'finalizado']);
            }
            DB::table('decisiones_cierre')->insert([
                'cierre_proyecto_id' => $cierre->id, 'docente_id' => $docente->id, 'ronda' => $cierre->ronda,
                'decision' => $datos['decision'], 'motivo' => $datos['motivo'], 'pendientes' => json_encode($pendientes, JSON_THROW_ON_ERROR),
                'apartados' => json_encode($apartados, JSON_THROW_ON_ERROR), 'fecha_limite' => $datos['decision'] === 'prorroga' ? $fecha : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            AvisoCierre::where('cierre_proyecto_id', $cierre->id)->where('estado', 'pendiente')->update(['estado' => 'cancelado']);
        }, 3);
    }

    public function sincronizar(?User $soloDocente = null): int
    {
        Proyecto::query()->where('estado', 'en_proceso')
            ->whereHas('guiaIntegradora', fn ($q) => $q->whereIn('estado', ['publicada', 'cerrada'])
                ->where(fn ($q) => $q->whereHas('periodoFin', fn ($p) => $p->where('estado', 'cerrado')->orWhere('fecha_fin', '<', today()->toDateString()))
                    ->orWhere(fn ($q) => $q->whereNull('periodo_fin_id')->whereHas('periodo', fn ($p) => $p->where('estado', 'cerrado')->orWhere('fecha_fin', '<', today()->toDateString())))))
            ->when($soloDocente, fn ($q) => $q->whereHas('equipo.grupoAcademico', fn ($g) => $g->conMateriaLiderDelDocente($soloDocente->id)))
            ->chunkById(50, function ($proyectos) {
                foreach ($proyectos as $proyecto) {
                    $this->registrar($proyecto);
                }
            });
        $cantidad = 0;
        AvisoCierre::where('estado', 'pendiente')->when($soloDocente, fn ($q) => $q->where('docente_id', $soloDocente->id))
            ->where(fn ($q) => $q->whereNull('despachado_en')->orWhere('despachado_en', '<', now()->subMinutes(15)))
            ->chunkById(50, function ($avisos) use (&$cantidad) {
                foreach ($avisos as $aviso) {
                    $claim = AvisoCierre::whereKey($aviso->id)->where('estado', 'pendiente')
                        ->where(fn ($q) => $q->whereNull('despachado_en')->orWhere('despachado_en', '<', now()->subMinutes(15)))
                        ->update(['despachado_en' => now()]);
                    if ($claim) {
                        EnviarAvisoCierre::dispatch($aviso->id)->afterCommit();
                        $cantidad++;
                    }
                }
            });

        return $cantidad;
    }

    public function bloquearPeriodos(Proyecto $proyecto): void
    {
        $guia = $proyecto->guiaIntegradora;
        Periodo::whereIn('id', array_filter([$guia->periodo_id, $guia->periodo_fin_id, $proyecto->equipo->grupoAcademico->periodo_id]))->orderBy('id')->lockForUpdate()->get();
    }

    private function exigir(bool $condicion, string $mensaje): void
    {
        if (! $condicion) {
            throw ValidationException::withMessages(['cierre' => $mensaje]);
        }
    }
}
