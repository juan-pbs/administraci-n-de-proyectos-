Universidad Tecnológica del Valle del Mezquital
Administración de proyectos integradores

Cierre pendiente del proyecto

Hola, {{ $nombre }}.

El plazo del proyecto {{ $proyecto }} terminó y su documento final tiene pendientes.
Equipo: {{ $equipo }}. Guía: {{ $guia }}.

Apartados pendientes:
@foreach ($pendientes as $pendiente)
- {{ $pendiente['apartado'] }}: {{ $pendiente['detalle'] }}
@endforeach

Como docente líder, decide si cierras con estos pendientes o concedes una prórroga indicando la fecha y los apartados que se abrirán.
Revisar cierre y prórrogas: {{ $url }}
