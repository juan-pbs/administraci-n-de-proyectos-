<?php

namespace App\Jobs;

use App\Models\ApartadoGuia;
use App\Models\AvisoAsignacion;
use App\Models\Proyecto;
use App\Models\User;
use App\Notifications\AsignacionAcademica;
use App\Servicios\AvisosAsignaciones;
use App\Servicios\PlazosProyecto;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class EnviarAvisoAsignacion implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 60;

    public function __construct(public int $avisoId) {}

    public function backoff(): array
    {
        return [60, 180, 600];
    }

    public function handle(AvisosAsignaciones $servicio): void
    {
        DB::transaction(function () use ($servicio) {
            $aviso = AvisoAsignacion::query()->whereKey($this->avisoId)->lockForUpdate()->first();
            if (! $aviso || $aviso->estado !== 'pendiente') {
                return;
            }
            $proyecto = Proyecto::find($aviso->proyecto_id);
            $apartado = ApartadoGuia::find($aviso->apartado_guia_id);
            $alumno = User::with('role')->find($aviso->estudiante_id);
            $recordatorio = str_starts_with($aviso->tipo, 'recordatorio_');
            if (! $proyecto || ! $apartado || ! $alumno || ! $servicio->vigente($proyecto, $alumno, $apartado)
                || $apartado->guia_integradora_id !== $proyecto->guia_integradora_id
                || $servicio->huella($proyecto, $apartado) !== $aviso->huella || app(PlazosProyecto::class)->fecha($proyecto, $apartado)?->isPast()
                || ($recordatorio && ! $servicio->necesitaRecordatorio($proyecto, $apartado))) {
                $aviso->update(['estado' => 'cancelado']);

                return;
            }
            // La notificación se envía dentro del job: los reintentos quedan en una sola cola.
            $alumno->notify(new AsignacionAcademica($proyecto, $apartado, $aviso->tipo));
            $aviso->update(['estado' => 'enviado', 'enviado_en' => now()]);
        });
    }

    public function failed(?\Throwable $exception): void
    {
        AvisoAsignacion::query()->whereKey($this->avisoId)->where('estado', 'pendiente')->update(['estado' => 'fallido']);
    }
}
