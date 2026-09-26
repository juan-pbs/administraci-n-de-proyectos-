<?php

namespace App\Jobs;

use App\Models\AvisoCierre;
use App\Models\CierreProyecto;
use App\Models\Proyecto;
use App\Models\User;
use App\Notifications\CierrePendiente;
use App\Servicios\CierresProyectos;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class EnviarAvisoCierre implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 60;

    public function __construct(public int $avisoId) {}

    public function backoff(): array
    {
        return [60, 180, 600];
    }

    public function handle(CierresProyectos $servicio): void
    {
        DB::transaction(function () use ($servicio) {
            $referencia = AvisoCierre::find($this->avisoId);
            $caso = $referencia ? CierreProyecto::with('proyecto')->find($referencia->cierre_proyecto_id) : null;
            if (! $caso) {
                return;
            }
            $servicio->bloquearPeriodos($caso->proyecto);
            Proyecto::whereKey($caso->proyecto_id)->lockForUpdate()->firstOrFail();
            CierreProyecto::whereKey($caso->id)->lockForUpdate()->firstOrFail();
            $aviso = AvisoCierre::whereKey($this->avisoId)->lockForUpdate()->first();
            if (! $aviso || $aviso->estado !== 'pendiente') {
                return;
            }
            $cierre = CierreProyecto::with('proyecto.equipo.grupoAcademico')->find($aviso->cierre_proyecto_id);
            $docente = User::with('role')->find($aviso->docente_id);
            $vigente = $cierre && $cierre->estado === 'pendiente' && (int) $cierre->ronda === (int) $aviso->ronda
                && $docente?->estado === 'activo' && $docente->hasRole('docente_lider')
                && $cierre->proyecto->equipo->grupoAcademico()->conMateriaLiderDelDocente($docente->id)->exists();
            if (! $vigente) {
                $aviso->update(['estado' => 'cancelado']);

                return;
            }
            $pendientes = $servicio->resumen($cierre->proyecto);
            if (! $pendientes) {
                $aviso->update(['estado' => 'cancelado']);

                return;
            }
            $docente->notify(new CierrePendiente($cierre->proyecto, $pendientes));
            $aviso->update(['estado' => 'enviado', 'enviado_en' => now()]);
        });
    }

    public function failed(?\Throwable $exception): void
    {
        AvisoCierre::whereKey($this->avisoId)->where('estado', 'pendiente')->update(['estado' => 'fallido']);
    }
}
