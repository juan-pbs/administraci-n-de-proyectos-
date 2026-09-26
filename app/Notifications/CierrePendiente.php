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
        $correo = (new MailMessage)->subject('Cierre pendiente del proyecto #'.$this->proyecto->id)
            ->greeting('Hola, '.e($notifiable->nombre))
            ->line('El plazo del proyecto '.e($this->proyecto->titulo).' terminó y su documento final tiene pendientes.')
            ->line('Equipo: '.e($this->proyecto->equipo->nombre).'. Guía: '.e($this->proyecto->guiaIntegradora->nombre).'.');
        foreach ($this->pendientes as $pendiente) {
            $correo->line(e($pendiente['apartado'].': '.$pendiente['detalle']));
        }

        return $correo->line('Como docente líder, decide si cierras con estos pendientes o concedes una prórroga indicando la fecha y los apartados que se abrirán.')
            ->action('Revisar cierre y prórrogas', route('docente-lider.cierres.mostrar', $this->proyecto));
    }
}
