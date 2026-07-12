<?php

namespace App\Correos;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContrasenaInicial extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $matricula,
        public string $contrasenaTemporal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Acceso al sistema de proyectos integradores',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'correos.contrasena-inicial',
        );
    }
}
