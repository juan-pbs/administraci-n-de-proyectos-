<?php

namespace App\Notifications;

use App\Models\ApartadoGuia;
use App\Models\Proyecto;
use App\Servicios\PlazosProyecto;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AsignacionAcademica extends Notification
{
    public function __construct(public Proyecto $proyecto, public ApartadoGuia $apartado, public string $tipo) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fecha = app(PlazosProyecto::class)->fecha($this->proyecto, $this->apartado);
        $titulo = match (true) {
            str_starts_with($this->tipo, 'recordatorio_') => 'Recordatorio de entrega',
            $this->tipo === 'cambio' => 'Asignación actualizada',
            default => 'Nueva asignación',
        };

        return (new MailMessage)->subject($titulo.': '.$this->apartado->titulo)->greeting('Hola, '.$notifiable->nombre)
            ->line($titulo.' para el proyecto '.$this->proyecto->titulo.'.')
            ->line('Actividad: '.$this->apartado->titulo)
            ->line($this->apartado->descripcion ?: 'Consulta los requisitos en el sistema.')
            ->line('Fecha límite: '.($fecha ? $fecha->format('d/m/Y H:i').' ('.config('app.timezone').')' : 'Sin fecha definida'))
            ->action('Consultar asignación', route($this->apartado->requiere_codigo ? 'estudiante.codigo' : 'estudiante.entregas', ['proyecto_contexto' => $this->proyecto->id]))
            ->line('Este aviso corresponde a tu equipo.');
    }
}
