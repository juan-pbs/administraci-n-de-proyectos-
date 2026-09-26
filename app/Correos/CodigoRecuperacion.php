<?php

namespace App\Correos;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CodigoRecuperacion extends Mailable
{
    public function __construct(public string $codigo) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Código para recuperar tu contraseña');
    }

    public function content(): Content
    {
        return new Content(view: 'correos.codigo-recuperacion');
    }
}
