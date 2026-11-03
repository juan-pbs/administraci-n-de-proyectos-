Universidad Tecnológica del Valle del Mezquital
Administración de proyectos integradores

{{ $titulo }}

@if($textoRecordatorio)
{{ $textoRecordatorio }}
@else
Hola, {{ $nombre }}.

{{ $titulo }} para el proyecto {{ $proyecto }}.
@endif
Actividad: {{ $actividad }}
{{ $descripcion }}
Fecha límite: {{ $fechaLimite }}

Consultar asignación: {{ $url }}
Este aviso corresponde a tu equipo.
