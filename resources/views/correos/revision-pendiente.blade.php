<x-correo-layout titulo="Revisión pendiente" :mensaje="$message">
    <p style="margin:0 0 20px;white-space:pre-line;">{{ $texto }}</p>
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 20px;border:1px solid #dbe5ed;border-collapse:collapse;">
        <tr><td style="padding:12px;background:#f1f5f9;color:#0D376D;font-weight:bold;">Proyecto</td><td style="padding:12px;">{{ $proyecto }}</td></tr>
        <tr><td style="padding:12px;background:#f1f5f9;color:#0D376D;font-weight:bold;">Equipo</td><td style="padding:12px;">{{ $equipo }}</td></tr>
        <tr><td style="padding:12px;background:#f1f5f9;color:#0D376D;font-weight:bold;">Actividad</td><td style="padding:12px;">{{ $actividad }} · Versión {{ $version }}</td></tr>
    </table>
    <p style="margin:0;"><a href="{{ $url }}" style="display:inline-block;padding:11px 20px;background:#15529A;color:#ffffff;font-weight:bold;text-decoration:none;">Revisar entrega</a></p>
</x-correo-layout>
