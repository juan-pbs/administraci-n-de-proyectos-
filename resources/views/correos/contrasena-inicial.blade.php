<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acceso al sistema</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.5;">
    <h1 style="color: #0D376D; font-size: 22px;">Acceso al sistema de proyectos integradores</h1>

    <p>Hola, {{ $nombre }}.</p>

    <p>Se generó tu acceso al sistema. Usa los siguientes datos para iniciar sesión:</p>

    <table style="border-collapse: collapse; margin: 18px 0;">
        <tr>
            <td style="border: 1px solid #cbd5e1; padding: 8px 12px; font-weight: bold;">Matrícula / clave</td>
            <td style="border: 1px solid #cbd5e1; padding: 8px 12px;">{{ $matricula }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #cbd5e1; padding: 8px 12px; font-weight: bold;">Contraseña temporal</td>
            <td style="border: 1px solid #cbd5e1; padding: 8px 12px;">{{ $contrasenaTemporal }}</td>
        </tr>
    </table>

    <p>En tu primer inicio de sesión el sistema te solicitará actualizar la contraseña.</p>
</body>
</html>
