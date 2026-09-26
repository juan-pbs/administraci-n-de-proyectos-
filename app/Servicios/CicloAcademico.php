<?php

namespace App\Servicios;

use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CicloAcademico
{
    public function activar(Periodo $periodo): void
    {
        DB::transaction(function () use ($periodo) {
            $this->bloquearTransiciones();
            $periodo = Periodo::query()->whereKey($periodo->id)->lockForUpdate()->firstOrFail();
            $this->exigir($periodo->estado === 'borrador', 'Solo se puede activar un periodo en borrador.');
            $actual = Periodo::query()->where('estado', 'activo')->first();
            $this->exigir(! $actual, 'Ya existe un periodo activo: '.($actual?->nombre ?? '').'. Ciérralo antes de activar otro.');
            $periodo->update(['estado' => 'activo']);
        });
    }

    public function cerrar(Periodo $periodo): void
    {
        DB::transaction(function () use ($periodo) {
            $this->bloquearTransiciones();
            $guias = GuiaIntegradora::where(fn ($q) => $q->where('periodo_fin_id', $periodo->id)
                ->orWhere(fn ($q) => $q->whereNull('periodo_fin_id')->where('periodo_id', $periodo->id)))->get();
            $ids = $guias->pluck('periodo_id')->merge($guias->pluck('periodo_fin_id'))->push($periodo->id)->filter()->unique();
            Periodo::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            $periodo = Periodo::query()->whereKey($periodo->id)->lockForUpdate()->firstOrFail();
            $this->exigir($periodo->estado === 'activo', 'Solo se puede cerrar el periodo activo.');
            $periodo->update(['estado' => 'cerrado']);
            GuiaIntegradora::query()->where('estado', 'publicada')
                ->where(fn ($q) => $q->where('periodo_fin_id', $periodo->id)
                    ->orWhere(fn ($q) => $q->whereNull('periodo_fin_id')->where('periodo_id', $periodo->id)))
                ->update(['estado' => 'cerrada']);
            Proyecto::where('estado', 'en_proceso')->whereIn('guia_integradora_id', $guias->pluck('id'))
                ->chunkById(50, function ($proyectos) {
                    foreach ($proyectos as $proyecto) {
                        app(CierresProyectos::class)->registrar($proyecto);
                    }
                });
        });
    }

    public function publicar(GuiaIntegradora $guia): void
    {
        DB::transaction(function () use ($guia) {
            $this->bloquearTransiciones();
            $periodos = Periodo::query()->whereIn('id', array_filter([$guia->periodo_id, $guia->periodo_fin_id]))->orderBy('id')->lockForUpdate()->get();
            $this->exigir(! $periodos->contains('estado', 'cerrado'), 'No se puede publicar una guía en un periodo cerrado.');
            $guia = GuiaIntegradora::query()->whereKey($guia->id)->lockForUpdate()->firstOrFail();
            $this->exigir($guia->estado === 'borrador', 'Solo se puede publicar una guía en borrador.');
            $guia->load(['apartados.firmas.docente.role', 'apartados.asignaturasContribuyentes']);
            $this->exigir($guia->apartados->isNotEmpty(), 'Agrega al menos un apartado antes de publicar.');
            $this->exigir(abs((float) $guia->apartados->sum('ponderacion') - 100) < 0.005, 'Las ponderaciones de los apartados deben sumar 100%.');
            foreach ($guia->apartados as $apartado) {
                $this->exigir($apartado->requiere_documento || $apartado->requiere_codigo, $apartado->titulo.': define qué evidencia deben entregar los alumnos.');
                $firmas = $apartado->firmas->where('requerida', true);
                $this->exigir($firmas->isNotEmpty(), $apartado->titulo.': asigna al menos un evaluador requerido.');
                foreach ($firmas as $firma) {
                    $docente = $firma->docente;
                    $valido = $docente ? $docente->estado === 'activo' && $docente->hasAnyRole('docente_lider', 'docente_materia')
                        : ($firma->asignatura_id && User::query()->where('estado', 'activo')
                            ->whereHas('role', fn ($q) => $q->whereIn('nombre', ['docente_lider', 'docente_materia']))
                            ->whereHas('asignaturasComoDocente', fn ($q) => $q->where('asignaturas.id', $firma->asignatura_id)->where('docentes_asignatura.periodo_id', $guia->periodo_id)->where('docentes_asignatura.activo', true))->exists());
                    $this->exigir((bool) $valido, $apartado->titulo.': completa los evaluadores requeridos con docentes activos.');
                }
                if ($apartado->requiere_codigo) {
                    $this->exigir($firmas->contains(fn ($firma) => $firma->docente?->estado === 'activo' && $firma->docente->hasRole('docente_lider')), $apartado->titulo.': asigna un docente líder para evaluar el código.');
                }
                foreach ($apartado->asignaturasContribuyentes->where('pivot.requiere_firma', true) as $materia) {
                    $this->exigir($firmas->contains('asignatura_id', $materia->id), $apartado->titulo.': configura el evaluador de '.$materia->nombre.'.');
                }
            }
            $guia->update(['estado' => 'publicada']);
        });
    }

    private function bloquearTransiciones(): void
    {
        // Fila estable que serializa también el caso en el que aún no existe ningún activo.
        Role::query()->where('nombre', 'coordinacion')->lockForUpdate()->firstOrFail();
    }

    private function exigir(bool $condicion, string $mensaje): void
    {
        if (! $condicion) {
            throw ValidationException::withMessages(['ciclo' => $mensaje]);
        }
    }
}
