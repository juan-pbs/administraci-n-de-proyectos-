<?php

use App\Correos\CodigoRecuperacion;
use App\Correos\ContrasenaInicial;
use App\Models\ApartadoGuia;
use App\Models\Equipo;
use App\Models\GuiaIntegradora;
use App\Models\Proyecto;
use App\Models\User;
use App\Notifications\AsignacionAcademica;
use App\Notifications\CierrePendiente;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Facades\Mail;

function verificarCorreoInstitucional($sent, string $texto): void
{
    $correo = $sent->getOriginalMessage();
    $html = $correo->getHtmlBody();

    expect($html)->toContain('Universidad Tecnológica del Valle del Mezquital')
        ->toContain('Administración de proyectos integradores')
        ->toContain('#0D376D')
        ->toContain($texto);
    expect($correo->getTextBody())->toContain($texto);
    expect($correo->getAttachments())->toHaveCount(1);
    expect($html)->toContain('src="cid:'.$correo->getAttachments()[0]->getContentId().'"');
    expect($correo->getAttachments()[0]->getFilename())->toBe('utvm-correo.png');
    expect($correo->getAttachments()[0]->getMediaType())->toBe('image')
        ->and($correo->getAttachments()[0]->getMediaSubtype())->toBe('png');
}

test('los correos de acceso y recuperación incluyen el logo adjunto y versión de texto', function () {
    $acceso = Mail::to('alumno@example.com')->send(new ContrasenaInicial('Ana', '20260001', 'ClaveTemporal123'));
    verificarCorreoInstitucional($acceso, '20260001');
    expect($acceso->getOriginalMessage()->getHtmlBody())->toContain('ClaveTemporal123');

    $recuperacion = Mail::to('alumno@example.com')->send(new CodigoRecuperacion('12345678'));
    verificarCorreoInstitucional($recuperacion, '12345678');
});

test('los avisos académicos y de cierre conservan el formato institucional y escapan datos', function () {
    $usuario = new User(['nombre' => 'Ana <script>alert(1)</script>', 'correo' => 'alumno@example.com']);
    $proyecto = new Proyecto(['titulo' => 'Proyecto <script>alert(1)</script>']);
    $proyecto->id = 42;
    $proyecto->setRelation('cierre', null);
    $proyecto->setRelation('equipo', new Equipo(['nombre' => 'Equipo Alfa']));
    $proyecto->setRelation('guiaIntegradora', new GuiaIntegradora(['nombre' => 'Guía 2026']));
    $apartado = new ApartadoGuia(['titulo' => 'Entrega de avances', 'descripcion' => 'Sube la evidencia.']);
    $apartado->id = 7;

    $asignacion = app(MailChannel::class)->send($usuario, new AsignacionAcademica($proyecto, $apartado, 'nueva'));
    verificarCorreoInstitucional($asignacion, 'Entrega de avances');
    expect($asignacion->getOriginalMessage()->getHtmlBody())->not->toContain('<script>');

    $cierre = app(MailChannel::class)->send($usuario, new CierrePendiente($proyecto, [
        ['apartado' => 'Introducción', 'detalle' => 'Falta firma'],
    ]));
    verificarCorreoInstitucional($cierre, 'Falta firma');
    expect($cierre->getOriginalMessage()->getHtmlBody())->not->toContain('<script>');
});

test('los recordatorios de 72 y 24 horas incluyen el logo y el asunto correspondiente', function () {
    $usuario = new User(['nombre' => 'Ana', 'correo' => 'alumno@example.com']);
    $proyecto = new Proyecto(['titulo' => 'Proyecto integrador']);
    $proyecto->id = 42;
    $proyecto->setRelation('cierre', null);
    $apartado = new ApartadoGuia(['titulo' => 'Entrega final', 'descripcion' => 'Sube tu documento.']);
    $apartado->id = 7;

    foreach (['recordatorio_72', 'recordatorio_24'] as $tipo) {
        $correo = app(MailChannel::class)->send($usuario, new AsignacionAcademica($proyecto, $apartado, $tipo));

        verificarCorreoInstitucional($correo, 'Recordatorio de entrega');
        expect($correo->getOriginalMessage()->getSubject())->toBe('Recordatorio de entrega: Entrega final');
    }
});
