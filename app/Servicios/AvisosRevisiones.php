<?php

namespace App\Servicios;

use App\Jobs\EnviarAvisoRevision;
use App\Models\AvisoRevision;
use App\Models\Entrega;
use App\Models\FirmaApartadoGuia;
use App\Models\Proyecto;
use App\Models\User;

class AvisosRevisiones
{
    public function pendiente(Entrega $entrega, User $docente): bool
    {
        $entrega->loadMissing(['apartado', 'proyecto.equipo.grupoAcademico', 'proyecto.guiaIntegradora.periodo']);
        $proyecto = $entrega->proyecto;
        $apartado = $entrega->apartado;
        if (! $proyecto || ! $apartado || $docente->estado !== 'activo'
            || ! $docente->loadMissing('role')->hasAnyRole('docente_lider', 'docente_materia')
            || ! app(PlazosProyecto::class)->habilitado($proyecto, $apartado)
            || (int) $proyecto->guia_integradora_id !== (int) $apartado->guia_integradora_id
            || Entrega::query()->where('proyecto_id', $proyecto->id)->where('apartado_guia_id', $apartado->id)
                ->where('version', '>', $entrega->version)->exists()
            || $entrega->revisiones()->where('revisor_id', $docente->id)->exists()) {
            return false;
        }

        if ($apartado->requiere_codigo) {
            return $docente->hasRole('docente_lider')
                && $proyecto->equipo->grupoAcademico()->conMateriaLiderDelDocente($docente->id)->exists();
        }

        return FirmaApartadoGuia::query()->where('apartado_guia_id', $apartado->id)->where('docente_id', $docente->id)->exists()
            && Proyecto::query()->whereKey($proyecto->id)->where(fn ($q) => $q
                ->whereHas('docentes', fn ($d) => $d->where('usuarios.id', $docente->id))
                ->orWhereHas('asignaturas', fn ($a) => $a->where('asignaturas_proyecto.docente_id', $docente->id)))->exists();
    }

    public function paraProyecto(Proyecto $proyecto, bool $manual): int
    {
        $proyecto->loadMissing(['guiaIntegradora.periodo', 'equipo.grupoAcademico']);
        $cantidad = 0;
        $entregas = Entrega::query()->with(['apartado.firmas.docente.role'])
            ->where('proyecto_id', $proyecto->id)->orderByDesc('version')->orderByDesc('id')->get()
            ->unique('apartado_guia_id');
        foreach ($entregas as $entrega) {
            if (! $manual && (! $entrega->entregado_en || $entrega->entregado_en->gt(now()->subDay()))) {
                continue;
            }
            $candidatos = $entrega->apartado->requiere_codigo
                ? User::query()->with('role')->whereKey($proyecto->equipo->grupoAcademico->lider_proyecto_id)->get()
                : $entrega->apartado->firmas->pluck('docente')->filter()->unique('id');
            foreach ($candidatos as $docente) {
                if (! $this->pendiente($entrega, $docente)) {
                    continue;
                }
                $clave = hash('sha256', implode('|', [$entrega->id, $docente->id, now()->toDateString()]));
                $aviso = AvisoRevision::query()->firstOrCreate(['clave' => $clave], [
                    'entrega_id' => $entrega->id, 'docente_id' => $docente->id,
                    'origen' => $manual ? 'manual' : 'automatico', 'programado_en' => now(), 'despachado_en' => now(),
                ]);
                if ($aviso->wasRecentlyCreated) {
                    EnviarAvisoRevision::dispatch($aviso->id)->afterCommit();
                    $cantidad++;
                }
            }
        }

        return $cantidad;
    }

    public function sincronizar(): int
    {
        $cantidad = 0;
        Proyecto::query()->where('estado', 'en_proceso')
            ->where(fn ($q) => $q
                ->whereHas('guiaIntegradora', fn ($g) => $g->where('estado', 'publicada')->whereHas('periodo', fn ($p) => $p->where('estado', 'activo')))
                ->orWhereHas('cierre', fn ($c) => $c->where('estado', 'prorroga')->whereHas('prorrogas', fn ($p) => $p
                    ->whereColumn('prorrogas_apartados.ronda', 'cierres_proyectos.ronda')->where('fecha_limite', '>', now()))))
            ->chunkById(50, function ($proyectos) use (&$cantidad): void {
                foreach ($proyectos as $proyecto) {
                    $cantidad += $this->paraProyecto($proyecto, false);
                }
            });
        AvisoRevision::query()->where('estado', 'pendiente')->where('programado_en', '<=', now())
            ->where('despachado_en', '<', now()->subMinutes(15))
            ->chunkById(100, function ($avisos) use (&$cantidad): void {
                foreach ($avisos as $aviso) {
                    $claimed = AvisoRevision::query()->whereKey($aviso->id)->where('estado', 'pendiente')
                        ->where('despachado_en', '<', now()->subMinutes(15))->update(['despachado_en' => now()]);
                    if ($claimed) {
                        EnviarAvisoRevision::dispatch($aviso->id);
                        $cantidad++;
                    }
                }
            });

        return $cantidad;
    }
}
