<x-correo-layout titulo="Acceso al sistema" :mensaje="$message">
    <p style="margin:0 0 16px;">Hola, {{ $nombre }}.</p>
    <p style="margin:0 0 20px;">Se generó tu acceso al sistema. Usa estos datos para iniciar sesión:</p>
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 20px;border:1px solid #dbe5ed;border-collapse:collapse;">
        <tr>
            <td style="padding:12px;background:#f1f5f9;border-bottom:1px solid #dbe5ed;color:#0D376D;font-weight:bold;">Matrícula / clave</td>
            <td style="padding:12px;border-bottom:1px solid #dbe5ed;word-break:break-word;">{{ $matricula }}</td>
        </tr>
        <tr>
            <td style="padding:12px;background:#f1f5f9;color:#0D376D;font-weight:bold;">Contraseña</td>
            <td style="padding:12px;word-break:break-word;">{{ $contrasenaInicial }}</td>
        </tr>
    </table>
    <p style="margin:0 0 20px;">Puedes cambiar esta contraseña desde el sistema cuando lo necesites.</p>
    <p style="margin:0;"><a href="{{ route('login') }}" style="display:inline-block;padding:11px 20px;background:#15529A;color:#ffffff;font-weight:bold;text-decoration:none;">Iniciar sesión</a></p>
</x-correo-layout>
