<x-correo-layout titulo="Cierre pendiente del proyecto" :mensaje="$message">
    <p style="margin:0 0 16px;">Hola, {{ $nombre }}.</p>
    <p style="margin:0 0 16px;">El plazo del proyecto <strong>{{ $proyecto }}</strong> terminó y su documento final tiene pendientes.</p>
    <p style="margin:0 0 16px;"><strong>Equipo:</strong> {{ $equipo }}<br><strong>Guía:</strong> {{ $guia }}</p>
    <p style="margin:0 0 8px;color:#0D376D;font-weight:bold;">Apartados pendientes</p>
    <ul style="margin:0 0 20px;padding-left:22px;">
        @foreach ($pendientes as $pendiente)
            <li style="margin-bottom:8px;"><strong>{{ $pendiente['apartado'] }}:</strong> {{ $pendiente['detalle'] }}</li>
        @endforeach
    </ul>
    <p style="margin:0 0 20px;">Como docente líder, decide si cierras con estos pendientes o concedes una prórroga indicando la fecha y los apartados que se abrirán.</p>
    <p style="margin:0;"><a href="{{ $url }}" style="display:inline-block;padding:11px 20px;background:#15529A;color:#ffffff;font-weight:bold;text-decoration:none;">Revisar cierre y prórrogas</a></p>
</x-correo-layout>
