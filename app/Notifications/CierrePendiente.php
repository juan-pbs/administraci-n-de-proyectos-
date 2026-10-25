<?php

namespace App\Notifications;

use App\Models\Proyecto;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CierrePendiente extends Notification
{
    public function __construct(public Proyecto $proyecto, public array $pendientes) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cierre pendiente del proyecto #'.$this->proyecto->id)
            ->view(['html' => 'correos.cierre-pendiente', 'text' => 'correos.texto.cierre-pendiente'], [
                'nombre' => $notifiable->nombre,
                'proyecto' => $this->proyecto->titulo,
                'equipo' => $this->proyecto->equipo->nombre,
                'guia' => $this->proyecto->guiaIntegradora->nombre,
                'pendientes' => $this->pendientes,
                'url' => route('docente-lider.cierres.mostrar', $this->proyecto),
            ]);
    }
}
