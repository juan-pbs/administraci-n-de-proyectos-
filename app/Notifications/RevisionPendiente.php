<?php

namespace App\Notifications;

use App\Models\Entrega;
use App\Servicios\TextoRecordatorios;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RevisionPendiente extends Notification
{
    public function __construct(public Entrega $entrega) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->entrega->loadMissing(['proyecto.equipo.grupoAcademico', 'apartado']);
        $proyecto = $this->entrega->proyecto;
        $apartado = $this->entrega->apartado;
        $textos = app(TextoRecordatorios::class);
        $texto = $textos->render($textos->plantilla($textos->docenteId($proyecto), 'revision_pendiente'), [
            'nombre' => $notifiable->nombre,
            'proyecto' => $proyecto->titulo,
            'actividad' => $apartado->titulo,
            'equipo' => $proyecto->equipo->nombre,
            'version' => $this->entrega->version,
        ]);

        return (new MailMessage)
            ->subject('Revisión pendiente: '.$apartado->titulo)
            ->view(['html' => 'correos.revision-pendiente', 'text' => 'correos.texto.revision-pendiente'], [
                'texto' => $texto, 'proyecto' => $proyecto->titulo, 'actividad' => $apartado->titulo,
                'equipo' => $proyecto->equipo->nombre, 'version' => $this->entrega->version,
                'url' => route($apartado->requiere_codigo ? 'docente-lider.codigo' : 'docente-materia.revisiones'),
            ]);
    }
}
