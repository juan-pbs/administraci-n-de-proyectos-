<?php

namespace App\Jobs;

use App\Models\AvisoRevision;
use App\Models\Entrega;
use App\Models\User;
use App\Notifications\RevisionPendiente;
use App\Servicios\AvisosRevisiones;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class EnviarAvisoRevision implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 60;

    public function __construct(public int $avisoId) {}

    public function backoff(): array
    {
        return [60, 180, 600];
    }

    public function handle(AvisosRevisiones $servicio): void
    {
        DB::transaction(function () use ($servicio): void {
            $aviso = AvisoRevision::query()->whereKey($this->avisoId)->lockForUpdate()->first();
            if (! $aviso || $aviso->estado !== 'pendiente') {
                return;
            }
            $entrega = Entrega::find($aviso->entrega_id);
            $docente = User::with('role')->find($aviso->docente_id);
            if (! $entrega || ! $docente || ! $servicio->pendiente($entrega, $docente)) {
                $aviso->update(['estado' => 'cancelado']);

                return;
            }
            $docente->notify(new RevisionPendiente($entrega));
            $aviso->update(['estado' => 'enviado', 'enviado_en' => now()]);
        });
    }

    public function failed(?\Throwable $exception): void
    {
        AvisoRevision::query()->whereKey($this->avisoId)->where('estado', 'pendiente')->update(['estado' => 'fallido']);
    }
}
