@props(['titulo', 'mensaje'])

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }} | Proyectos integradores UTVM</title>
</head>
<body style="margin:0;padding:0;background:#eef6fb;color:#334155;font-family:Arial,Helvetica,sans-serif;line-height:1.6;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#eef6fb;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;background:#ffffff;border:1px solid #dbe5ed;border-collapse:separate;">
                    <tr><td style="height:6px;background:#21A366;font-size:0;line-height:0;">&nbsp;</td></tr>
                    <tr>
                        <td style="padding:24px 32px;border-bottom:1px solid #e2e8f0;">
                            {{-- Recorte del logotipo oficial: solo UTVM y su árbol. --}}
                            <img src="{{ $mensaje->embed(public_path('assets/utvm/utvm-correo.png')) }}" alt="UTVM" width="177" height="68" style="display:block;width:177px;height:68px;border:0;">
                            <p style="margin:12px 0 0;color:#21A366;font-size:11px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;">Sistema académico</p>
                            <p style="margin:2px 0 0;color:#0D376D;font-size:16px;font-weight:bold;">Administración de proyectos integradores</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;font-size:15px;">
                            <h1 style="margin:0 0 20px;color:#0D376D;font-size:23px;line-height:1.3;">{{ $titulo }}</h1>
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;border-top:1px solid #e2e8f0;background:#f8fafc;color:#64748b;font-size:12px;line-height:1.5;">
                            Universidad Tecnológica del Valle del Mezquital<br>
                            Sistema de administración de proyectos integradores
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
