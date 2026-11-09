<?php

namespace App\Notifications;

use App\Models\ApartadoGuia;
use App\Models\Proyecto;
use App\Servicios\PlazosProyecto;
use App\Servicios\TextoRecordatorios;
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
        $textoRecordatorio = null;
        if (str_starts_with($this->tipo, 'recordatorio_')) {
            $textos = app(TextoRecordatorios::class);
            $textoRecordatorio = $textos->render($textos->plantilla($textos->docenteId($this->proyecto), $this->tipo), [
                'nombre' => $notifiable->nombre,
                'proyecto' => $this->proyecto->titulo,
                'actividad' => $this->apartado->titulo,
                'fecha_limite' => $fecha ? $fecha->format('d/m/Y H:i') : 'Sin fecha definida',
            ]);
        }

        return (new MailMessage)
            ->subject($titulo.': '.$this->apartado->titulo)
            ->view(['html' => 'correos.asignacion-academica', 'text' => 'correos.texto.asignacion-academica'], [
                'titulo' => $titulo,
                'nombre' => $notifiable->nombre,
                'proyecto' => $this->proyecto->titulo,
                'actividad' => $this->apartado->titulo,
                'descripcion' => $this->apartado->descripcion ?: 'Consulta los requisitos en el sistema.',
                'fechaLimite' => $fecha ? $fecha->format('d/m/Y H:i') : 'Sin fecha definida',
                'url' => route($this->apartado->requiere_codigo ? 'estudiante.codigo' : 'estudiante.entregas', ['proyecto_contexto' => $this->proyecto->id]),
                'textoRecordatorio' => $textoRecordatorio,
            ]);
    }
}
