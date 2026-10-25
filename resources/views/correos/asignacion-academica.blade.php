<x-correo-layout :titulo="$titulo" :mensaje="$message">
    <p style="margin:0 0 16px;">Hola, {{ $nombre }}.</p>
    <p style="margin:0 0 20px;">{{ $titulo }} para el proyecto <strong>{{ $proyecto }}</strong>.</p>
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 20px;border:1px solid #dbe5ed;border-collapse:collapse;">
        <tr><td style="padding:12px;background:#f1f5f9;color:#0D376D;font-weight:bold;">Actividad</td><td style="padding:12px;">{{ $actividad }}</td></tr>
        <tr><td style="padding:12px;background:#f1f5f9;color:#0D376D;font-weight:bold;">Fecha límite</td><td style="padding:12px;">{{ $fechaLimite }}</td></tr>
    </table>
    <p style="margin:0 0 20px;">{{ $descripcion }}</p>
    <p style="margin:0 0 20px;"><a href="{{ $url }}" style="display:inline-block;padding:11px 20px;background:#15529A;color:#ffffff;font-weight:bold;text-decoration:none;">Consultar asignación</a></p>
    <p style="margin:0;color:#64748b;font-size:14px;">Este aviso corresponde a tu equipo.</p>
</x-correo-layout>
