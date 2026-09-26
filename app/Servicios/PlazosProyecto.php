<?php

namespace App\Servicios;

use App\Models\ApartadoGuia;
use App\Models\CierreProyecto;
use App\Models\Proyecto;

class PlazosProyecto
{
    public function habilitado(Proyecto $proyecto, ApartadoGuia $apartado): bool
    {
        if ($this->cierre($proyecto)) {
            return $this->permite($proyecto, $apartado);
        }
        $guia = $proyecto->guiaIntegradora;

        return $proyecto->estado === 'en_proceso' && $guia->estado === 'publicada'
            && $guia->periodo->estado !== 'cerrado' && ($guia->periodoFin?->estado ?? '') !== 'cerrado';
    }

    public function fecha(Proyecto $proyecto, ApartadoGuia $apartado)
    {
        return $this->cierre($proyecto)?->prorrogas->where('apartado_guia_id', $apartado->id)->sortByDesc('ronda')->first()?->fecha_limite ?? $apartado->fecha_limite;
    }

    public function permite(Proyecto $proyecto, ApartadoGuia $apartado): bool
    {
        $cierre = $this->cierre($proyecto);

        return $cierre?->estado === 'prorroga' && $cierre->prorrogas->contains(fn ($prorroga) => (int) $prorroga->ronda === (int) $cierre->ronda && (int) $prorroga->apartado_guia_id === (int) $apartado->id && $prorroga->fecha_limite->isFuture());
    }

    public function cierre(Proyecto $proyecto): ?CierreProyecto
    {
        $proyecto->loadMissing('cierre.prorrogas');

        return $proyecto->cierre;
    }

    public function oProrroga($query, string $tabla = 'entregas'): void
    {
        $query->orWhereExists(fn ($q) => $q->selectRaw('1')->from('prorrogas_apartados as pa')
            ->join('cierres_proyectos as cp', 'cp.id', '=', 'pa.cierre_proyecto_id')
            ->whereColumn('cp.proyecto_id', "$tabla.proyecto_id")->whereColumn('pa.apartado_guia_id', "$tabla.apartado_guia_id")
            ->whereColumn('pa.ronda', 'cp.ronda')->where('cp.estado', 'prorroga')->where('pa.fecha_limite', '>', now()));
    }
}
