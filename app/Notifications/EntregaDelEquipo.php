<?php

namespace App\Notifications;

use App\Models\Entrega;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EntregaDelEquipo extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(public int $entregaId, public string $autor)
    {
        $this->afterCommit();
    }

    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function shouldSend(User $notifiable, string $channel): bool
    {
        return $notifiable->estado === 'activo' && $notifiable->hasRole('estudiante')
            && Entrega::query()->whereKey($this->entregaId)
                ->whereHas('equipo.integrantes', fn ($query) => $query->where('usuarios.id', $notifiable->id))->exists();
    }

    public function toMail(User $notifiable): MailMessage
    {
        $entrega = Entrega::with(['apartado', 'proyecto'])->findOrFail($this->entregaId);
        $actividad = $entrega->apartado->titulo;
        $contexto = ['proyecto_contexto' => $entrega->proyecto_id];
        $url = $entrega->apartado->requiere_codigo
            ? route('estudiante.codigo', $contexto)
            : route('estudiante.entregas', $contexto).'#entrega-'.$entrega->apartado_guia_id;

        return (new MailMessage)
            ->subject('Nueva entrega de tu equipo')
            ->greeting('Hola, '.$notifiable->nombre)
            ->line("{$this->autor} subió la versión {$entrega->version} de «{$actividad}» para el proyecto «{$entrega->proyecto->titulo}».")
            ->action('Consultar entrega', $url);
    }
}
